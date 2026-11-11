<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\CreateStudentAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Auth\LoginHistory;
use App\Models\Auth\Permission;
use App\Models\Core\Branch;
use App\Models\Core\Company;
use App\Models\Core\Setting;
use App\Models\User;
use App\Services\Auth\PasswordTokenIssuer;
use App\Services\BranchContext;
use App\Services\TelegramService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshTokenRepository;

class AuthController extends Controller
{
    public function __construct(
        private TelegramService $telegramService,
        private PasswordTokenIssuer $tokenIssuer,
    ) {}

    private function branchesFor(User $user)
    {
        $ids = app(BranchContext::class)->allowedBranchIds($user);

        return Branch::whereKey($ids->all())->orderBy('id')->get();
    }

    private function permissionsFor(int $userId)
    {
        // Super users get every permission, so the frontend shows everything
        if (User::whereKey($userId)->value('is_super')) {
            return Permission::query()->pluck('name')->values();
        }

        $rolePermissions = User::query()
            ->leftJoin('role_user as ru', 'ru.user_id', 'users.id')
            ->leftJoin('permission_role as rp', 'rp.role_id', 'ru.role_id')
            ->leftJoin('permissions as p', 'p.id', 'rp.permission_id')
            ->where('users.id', $userId)
            ->pluck('p.name', 'p.id'); // <-- Pluck name as value, id as key

        $userPermissions = User::query()
            ->leftJoin('permission_user as pu', 'pu.user_id', 'users.id')
            ->leftJoin('permissions as p', 'p.id', 'pu.permission_id')
            ->where('users.id', $userId)
            ->pluck('p.name', 'p.id'); // <-- Pluck name as value, id as key

        // merge() combines the lists and handles duplicates (by key).
        // values() strips the keys and gives you just the names.
        return $rolePermissions->merge($userPermissions)->values();
    }

    public function login(LoginRequest $request)
    {
        $user = User::query()
            ->whereUsername($request->username)
            ->orWhere('email', $request->username)
            ->with('branch')
            ->first();

        // Wrong input = 422, so the browser does not show it as a server crash
        if (! $user) {
            LoginHistory::recordFailed($request->username, $request, 'user_not_found');

            return $this->loginRefused('ពុំមានគណនីក្នុងប្រព័ន្ធ!', 422);
        }
        if (! Hash::check($request->password, $user->password)) {
            LoginHistory::record($user, $request, successful: false, reason: 'wrong_password');

            return $this->loginRefused('អ្នកបានបញ្ចូលលេខសម្ងាត់ខុស!', 422);
        }
        if ($user->is_active == false) {
            LoginHistory::record($user, $request, successful: false, reason: 'account_disabled');

            return $this->loginRefused('គណនីរបស់អ្នកត្រូវបានបិទ!', 403);
        }

        try {
            return $this->startSession($user, $request->password, $request);
        } catch (\Throwable $th) {
            return $this->loginRefused($th->getMessage(), 500);
        }
    }

    private function loginRefused(string $message, int $status)
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }

    /**
     * Student sign-up: create the account (role `student`) and log in right away.
     */
    public function register(RegisterRequest $request, CreateStudentAccount $createStudent)
    {
        $user = $createStudent->handle(
            $request->validated('name_en'),
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('name_kh'),
        );

        try {
            return $this->startSession($user->load('branch'), $request->validated('password'), $request, 201);
        } catch (\Throwable $th) {
            // The account exists; the student can still log in by hand
            Log::error('Sign-up login failed: '.$th->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Your account is ready. Please log in.',
            ], 500);
        }
    }

    /**
     * Login tokens + the user's start page and branch (same reply for login and sign-up).
     */
    private function startSession(User $user, string $password, Request $request, int $status = 200)
    {
        $tokens = $this->tokenIssuer->issue($user->email, $password, $request->header('X-CLIENT-ID'));

        $user->last_login = Carbon::now();
        $user->saveQuietly();

        $history = LoginHistory::record($user, $request, successful: true);
        $this->sendLoginAlert($user, $history, $request);

        return response()->json([
            'status' => true,
            'is_login' => true,
            'data' => [
                'access_token' => [
                    'value' => $tokens->access_token,
                    'expiry' => Carbon::now()->addSeconds($tokens->expires_in)->valueOf(),
                ],
                'refresh_token' => $this->refreshTokenPayload($tokens->refresh_token ?? null),
                'default_part' => $user->default_part,
                'default_branch' => $this->defaultBranchFor($user),
            ],
        ], $status);
    }

    private function defaultBranchFor(User $user): int|string
    {
        if ($user->manage_branch == 1) {
            return $user->branch_id;
        }

        if (in_array($user->manage_branch, [2, 3]) && $this->branchesFor($user)->count() <= 1) {
            return $user->branch_id;
        }

        return '*';
    }

    /**
     * Telegram message to the company group for each login.
     * Skipped for roles in config('roles.no_login_alert') — e.g. students.
     */
    private function sendLoginAlert(User $user, LoginHistory $history, Request $request): void
    {
        if ($user->hasRole(config('roles.no_login_alert', []))) {
            return;
        }

        $settings = Cache::remember('system_setting_value', 86400, function () {
            return Setting::whereIn('key', ['default_login_alert_telegram', 'company_name', 'telegram_bot_token'])->pluck('value', 'key');
        });

        $companyName = $settings['company_name'] ?? '';
        $groupId = $settings['default_login_alert_telegram'] ?? null;
        $botToken = $settings['telegram_bot_token'] ?? null;

        if (! $groupId || ! $botToken) {
            return;
        }

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        $b = fn ($value) => '<b>'.htmlspecialchars((string) ($value ?? '-'), ENT_NOQUOTES, 'UTF-8').'</b>';

        $codeMsg = '🔐 ចូលប្រើប្រាស់ប្រព័ន្ធ ['.$b($companyName).']';
        $codeMsg .= "\n";
        $codeMsg .= "\n---ព័ត៌មានអ្នកប្រើប្រាស់---";
        $codeMsg .= "\n";
        $codeMsg .= "\nឈ្មោះ ".$b($user->name_kh);
        $codeMsg .= "\nលេខសំគាល់ ".$b($user->code);
        $codeMsg .= "\nសាខា ".$b($user->branch?->name_kh);
        $codeMsg .= "\n";
        $codeMsg .= "\n---ព័ត៌មានឧបករណ៍---";
        $codeMsg .= "\n";
        $codeMsg .= "\n📱 ឧបករណ៍: ".$b(ucfirst($history->device_type).' / '.$history->platform);
        $codeMsg .= "\n🌐 កម្មវិធីរុករក: ".$b($history->browser);
        $codeMsg .= "\n📍 ទីតាំង: ".$b(($history->city ?? '-').', '.($history->country ?? '-'));
        $codeMsg .= "\n🔢 IP: ".$b($history->ip_address);
        if ($lat && $lng) {
            $mapLink = "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
            $codeMsg .= "\n🔢 Google Map: ".'<a href="'.htmlspecialchars($mapLink, ENT_QUOTES, 'UTF-8').'">'.$b('មើលទីតាំង').'</a>';
        }
        $codeMsg .= "\n🕐 ពេលវេលា: ".$b(Carbon::parse($history->logged_in_at)->format('d-m-Y H:i:s'));

        if ($history->is_new_device) {
            $codeMsg .= "\n⚠️ ចូលប្រើពីឧបករណ៍ថ្មី!"; // login from a new device
        }

        try {
            $this->telegramService->sendMessage($codeMsg, [$groupId], $botToken, 'HTML');
        } catch (\Throwable $e) {
            Log::warning('Login Telegram alert failed: '.$e->getMessage());
        }
    }

    public function refresh(RefreshTokenRequest $request)
    {
        try {
            $client = new Client;

            $response = $client->request(
                'POST',
                config('services.passport.oauth_url').'/oauth/token',
                [
                    'form_params' => [
                        'grant_type' => 'refresh_token',
                        'client_id' => $request->header('X-CLIENT-ID') ?? '',
                        'client_secret' => config('services.passport.client_secret'),
                        'refresh_token' => $request->refreshToken,
                        'scope' => '',
                    ],
                ]
            );

            $response = json_decode($response->getBody());

            // Same shape as login, so the app stores both tokens the same way
            return response()->json([
                'status' => true,
                'data' => [
                    'access_token' => [
                        'value' => $response->access_token,
                        'expiry' => Carbon::now()->addSeconds($response->expires_in)->valueOf(),
                    ],
                    'refresh_token' => $this->refreshTokenPayload($response->refresh_token),
                ],
            ]);
        } catch (\Throwable $th) {
            // Expired / revoked refresh token: 401 so the app logs the user out
            Log::info('Token refresh failed: '.$th->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Session expired. Please log in again.',
            ], 401);
        }
    }

    /**
     * Refresh token with its expiry (ms), matching Passport::refreshTokensExpireIn.
     */
    private function refreshTokenPayload(?string $refreshToken): ?array
    {
        if (! $refreshToken) {
            return null;
        }

        return [
            'value' => $refreshToken,
            'expiry' => Carbon::now()->add(Passport::refreshTokensExpireIn())->valueOf(),
        ];
    }

    public function logout()
    {
        try {
            $user = Auth::user();
            // Get and revoke the current token
            $token = $user->token();
            if ($token) {
                $token->revoke(); // Mark the token as revoked
                // Also kill its refresh token, or it could start a new session
                app(RefreshTokenRepository::class)->revokeRefreshTokensByAccessTokenId($token->id);
            }

            return response()->json([
                'status' => true,
                'message' => 'User logout.',
            ]);
        } catch (\Exception $e) {
            Log::error('Logout failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred during logout.',
            ], 500);
        }
    }

    public function bootstrap(Request $request)
    {
        try {
            $key = config('app.custom_encryption_key');
            if (str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }
            $encrypter = new Encrypter($key, config('app.cipher'));

            $authUser = $request->user();
            if (! $authUser) {
                return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
            }
            $user = User::query()
                ->with([
                    'mainUser.role',
                    'subUsers.role',
                    'siblings.role',
                    'branch',
                    'role',
                    'village.commune.district.province',
                ])
                ->findOrFail(Auth::id());

            $user->last_login = Carbon::now();
            $user->saveQuietly();

            $branches = $this->branchesFor($user);        // ← uses the same helper now
            $permissions = $this->permissionsFor($user->id); // ← pass id
            $company = Company::find(app(BranchContext::class)->companyId($user), ['name_kh']);

            return response()->json([
                'a' => 1, // status
                'b' => $encrypter->encryptString($user),
                'c' => $encrypter->encryptString($branches),
                'd' => $encrypter->encryptString($permissions),
                'e' => $encrypter->encryptString(json_encode($company)),
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Switch to a linked account only: the caller's own sub-user, their main
     * user, or a sub-user sharing the same main user.
     */
    public function changeUser(Request $request)
    {
        try {
            $caller = $request->user();
            $target = User::where('is_active', true)->find($request->user_id);

            if (! $target || ! $this->isLinkedAccount($caller, $target)) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found or access denied.',
                ], 403);
            }

            $result = $target->createToken('user-switch-token');

            return response()->json([
                'status' => true,
                'data' => [
                    'access_token' => [
                        'value' => $result->accessToken,
                        'expiry' => Carbon::parse($result->token->expires_at)->valueOf(),
                    ],
                    'refresh_token' => null,
                    'default_part' => $target->default_part,
                    'branch_id' => $target->branch_id,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    private function isLinkedAccount(User $caller, User $target): bool
    {
        if ($caller->id === $target->id) {
            return false;
        }

        return $target->parent_user_id === $caller->id
            || $caller->parent_user_id === $target->id
            || ($caller->parent_user_id !== null && $caller->parent_user_id === $target->parent_user_id);
    }
}

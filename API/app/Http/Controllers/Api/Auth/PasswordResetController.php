<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\RefreshTokenRepository;

/**
 * Forgot password: email a reset link, then set a new password with it.
 * Mail settings come from .env (MAIL_*); the link opens FRONTEND_URL.
 */
class PasswordResetController extends Controller
{
    /**
     * Always the same reply, so nobody can use this form to find out
     * which emails have an account.
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        // Disabled accounts get no email (they could not log in anyway)
        if (User::where('email', $email)->where('is_active', true)->exists()) {
            Password::sendResetLink(['email' => $email]);
        }

        return response()->json([
            'status' => true,
            'message' => 'If this email has an account, we sent a link to reset the password.',
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $this->logOutEverywhere($user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'status' => false,
                'message' => 'This link is not valid any more. Please ask for a new one.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Your password is changed. Please log in.',
        ]);
    }

    /**
     * The old password may be known to someone else: end all sessions.
     */
    private function logOutEverywhere(User $user): void
    {
        $refreshTokens = app(RefreshTokenRepository::class);

        $user->tokens()->where('revoked', false)->each(function ($token) use ($refreshTokens) {
            $token->revoke();
            $refreshTokens->revokeRefreshTokensByAccessTokenId($token->id);
        });
    }
}

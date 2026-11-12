<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\PasswordTokenIssuer;
use App\Services\TelegramService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.admin.email' => 'owner@example.test', 'app.admin.password' => 'secret-from-env']);
        $this->seed(DatabaseSeeder::class);

        // No real OAuth server in tests: hand out a fake token
        $this->app->instance(PasswordTokenIssuer::class, new class extends PasswordTokenIssuer
        {
            public function issue(string $email, string $password, ?string $clientId): object
            {
                return (object) ['access_token' => 'fake-access', 'refresh_token' => 'fake-refresh', 'expires_in' => 3600];
            }
        });
    }

    private function form(array $changes = []): array
    {
        return array_merge([
            'name_en' => 'Sok Dara',
            'email' => 'sok.dara@example.test',
            'password' => 'english123',
            'password_confirmation' => 'english123',
        ], $changes);
    }

    public function test_student_can_sign_up_and_is_logged_in(): void
    {
        $this->postJson('/api/web/register', $this->form())
            ->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.access_token.value', 'fake-access')
            ->assertJsonPath('data.refresh_token.value', 'fake-refresh')
            ->assertJsonPath('data.default_part', 'student');

        $user = User::where('email', 'sok.dara@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse($user->hasRole(['admin', 'teacher']));
        $this->assertTrue(Hash::check('english123', $user->password));
        $this->assertNotNull($user->last_login);
        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'successful' => true]);
    }

    public function test_khmer_name_is_optional(): void
    {
        $this->postJson('/api/web/register', $this->form())->assertCreated();
        $this->assertSame('Sok Dara', User::where('email', 'sok.dara@example.test')->value('name_kh'));

        $this->postJson('/api/web/register', $this->form(['email' => 'dara@example.test', 'name_kh' => 'សុខ ដារ៉ា']))->assertCreated();
        $this->assertSame('សុខ ដារ៉ា', User::where('email', 'dara@example.test')->value('name_kh'));
    }

    public function test_email_already_used_is_rejected(): void
    {
        $this->postJson('/api/web/register', $this->form())->assertCreated();

        $this->postJson('/api/web/register', $this->form(['email' => '  SOK.Dara@Example.test ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'This email already has an account. Please log in.']);

        $this->assertSame(1, User::where('email', 'sok.dara@example.test')->count());
    }

    public function test_bad_input_is_rejected(): void
    {
        $this->postJson('/api/web/register', $this->form(['name_en' => '', 'email' => 'not-an-email']))
            ->assertJsonValidationErrors(['name_en', 'email']);

        $this->postJson('/api/web/register', $this->form(['password' => 'onlyletters', 'password_confirmation' => 'onlyletters']))
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/web/register', $this->form(['password_confirmation' => 'different123']))
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'sok.dara@example.test']);
    }

    public function test_spam_bot_filling_the_hidden_field_is_rejected(): void
    {
        $this->postJson('/api/web/register', $this->form(['website' => 'http://spam.test']))
            ->assertJsonValidationErrors('website');

        $this->assertDatabaseMissing('users', ['email' => 'sok.dara@example.test']);
    }

    public function test_sign_up_is_limited_per_minute(): void
    {
        $limit = config('roles.signup_per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson('/api/web/register', ['email' => 'x'])->assertUnprocessable();
        }

        $this->postJson('/api/web/register', $this->form())->assertTooManyRequests();
    }

    public function test_existing_admin_login_still_works_and_sends_the_alert(): void
    {
        $this->fakeTelegramSettings();
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('sendMessage')->once();
        $this->app->instance(TelegramService::class, $telegram);

        $this->postJson('/api/web/login', ['username' => 'admin', 'password' => 'secret-from-env'])
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.access_token.value', 'fake-access');
    }

    public function test_student_login_by_email_sends_no_telegram_alert(): void
    {
        $this->postJson('/api/web/register', $this->form())->assertCreated();

        $this->fakeTelegramSettings();
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldNotReceive('sendMessage');
        $this->app->instance(TelegramService::class, $telegram);

        $this->postJson('/api/web/login', ['username' => 'sok.dara@example.test', 'password' => 'english123'])
            ->assertOk()
            ->assertJsonPath('data.default_part', 'student');
    }

    public function test_wrong_password_is_still_rejected(): void
    {
        $this->postJson('/api/web/login', ['username' => 'admin', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonPath('status', false);

        $this->postJson('/api/web/login', ['username' => 'nobody@example.test', 'password' => 'whatever123'])
            ->assertUnprocessable();
    }

    private function fakeTelegramSettings(): void
    {
        Cache::put('system_setting_value', collect([
            'default_login_alert_telegram' => '-100123',
            'telegram_bot_token' => 'test-bot-token',
            'company_name' => 'Rien',
        ]));
    }
}

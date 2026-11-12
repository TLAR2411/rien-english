<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\CreateStudentAccount;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.admin.password' => 'secret-from-env', 'app.frontend_url' => 'https://rien.test']);
        $this->seed(DatabaseSeeder::class);
        Notification::fake();

        $this->student = app(CreateStudentAccount::class)->handle('Sok Dara', 'sok.dara@example.test', 'english123');
    }

    public function test_reset_email_is_sent_with_a_link_to_the_website(): void
    {
        $this->postJson('/api/web/forgot-password', ['email' => '  SOK.Dara@Example.test '])
            ->assertOk()
            ->assertJsonPath('status', true);

        Notification::assertSentTo($this->student, ResetPasswordNotification::class, function ($notification) {
            $mail = $notification->toMail($this->student);

            return str_starts_with($mail->actionUrl, 'https://rien.test/reset-password?token=')
                && str_contains($mail->actionUrl, 'email=sok.dara%40example.test');
        });
    }

    public function test_unknown_email_gets_the_same_reply_and_no_email(): void
    {
        $known = $this->postJson('/api/web/forgot-password', ['email' => 'sok.dara@example.test'])->json();
        $unknown = $this->postJson('/api/web/forgot-password', ['email' => 'nobody@example.test'])->assertOk()->json();

        $this->assertSame($known, $unknown);
        Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    }

    public function test_disabled_account_gets_no_email(): void
    {
        $this->student->forceFill(['is_active' => false])->save();

        $this->postJson('/api/web/forgot-password', ['email' => 'sok.dara@example.test'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_bad_email_is_rejected(): void
    {
        $this->postJson('/api/web/forgot-password', ['email' => 'not-an-email'])
            ->assertJsonValidationErrors(['email' => 'Please enter a valid email.']);
    }

    public function test_password_can_be_changed_with_a_valid_link(): void
    {
        $token = Password::createToken($this->student);

        $this->postJson('/api/web/reset-password', $this->resetForm($token))
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertTrue(Hash::check('newpass456', $this->student->fresh()->password));
    }

    public function test_link_works_only_once(): void
    {
        $token = Password::createToken($this->student);

        $this->postJson('/api/web/reset-password', $this->resetForm($token))->assertOk();
        $this->postJson('/api/web/reset-password', $this->resetForm($token, ['password' => 'again789', 'password_confirmation' => 'again789']))
            ->assertUnprocessable();

        $this->assertTrue(Hash::check('newpass456', $this->student->fresh()->password));
    }

    public function test_wrong_or_expired_link_does_not_change_the_password(): void
    {
        $this->postJson('/api/web/reset-password', $this->resetForm('made-up-token'))
            ->assertUnprocessable()
            ->assertJsonPath('status', false);

        // A real token, but for another email
        $token = Password::createToken($this->student);
        $this->postJson('/api/web/reset-password', $this->resetForm($token, ['email' => 'admin@admin.com']))
            ->assertUnprocessable();

        // A real token, but too old (config/auth.php → expire = 60 minutes)
        $this->travel(61)->minutes();
        $this->postJson('/api/web/reset-password', $this->resetForm($token))->assertUnprocessable();

        $this->assertTrue(Hash::check('english123', $this->student->fresh()->password));
    }

    public function test_weak_or_different_passwords_are_rejected(): void
    {
        $token = Password::createToken($this->student);

        $this->postJson('/api/web/reset-password', $this->resetForm($token, ['password' => 'short', 'password_confirmation' => 'short']))
            ->assertJsonValidationErrors('password');

        $this->postJson('/api/web/reset-password', $this->resetForm($token, ['password_confirmation' => 'different123']))
            ->assertJsonValidationErrors(['password' => 'The two passwords are not the same.']);

        $this->assertTrue(Hash::check('english123', $this->student->fresh()->password));
    }

    public function test_old_sessions_are_logged_out_after_reset(): void
    {
        $this->student->createToken('old-session');
        $token = Password::createToken($this->student);

        $this->postJson('/api/web/reset-password', $this->resetForm($token))->assertOk();

        $this->assertSame(0, $this->student->tokens()->where('revoked', false)->count());
    }

    public function test_forgot_password_is_limited_per_minute(): void
    {
        $limit = config('roles.password_reset_per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson('/api/web/forgot-password', ['email' => 'x'])->assertUnprocessable();
        }

        $this->postJson('/api/web/forgot-password', ['email' => 'sok.dara@example.test'])->assertTooManyRequests();
    }

    private function resetForm(string $token, array $changes = []): array
    {
        return array_merge([
            'token' => $token,
            'email' => 'sok.dara@example.test',
            'password' => 'newpass456',
            'password_confirmation' => 'newpass456',
        ], $changes);
    }
}

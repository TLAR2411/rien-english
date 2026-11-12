<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The "reset your password" email. The button opens the website's
 * /reset-password page (FRONTEND_URL in .env), not a Laravel page.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset your password')
            ->greeting('Hello '.($notifiable->name_en ?: $notifiable->name_kh).',')
            ->line('We got a request to reset the password for your account.')
            ->action('Set a new password', $this->resetUrl($notifiable))
            ->line("This link works for {$minutes} minutes, one time only.")
            ->line('If you did not ask for this, you can ignore this email. Your password stays the same.')
            ->line('សូមចុចប៊ូតុងខាងលើ ដើម្បីកំណត់ពាក្យសម្ងាត់ថ្មី។ បើអ្នកមិនបានស្នើសុំទេ សូមមិនបាច់ធ្វើអ្វីទេ។');
    }

    protected function resetUrl($notifiable): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}

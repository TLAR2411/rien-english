<?php

namespace App\Services;

use App\Models\Hr\Employee;
use App\Models\Hr\UserAccessRequest;
use App\Models\Core\Setting;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Ask the admins to make a login account for an employee: one pending
     * request per employee (shown on the admin "User Requests" page), plus a
     * Telegram notice to the user-request group when one is configured.
     *
     * @param array $input ['employee_id' => int]
     */
    public function requestUser(array $input)
    {
        $alreadyPending = UserAccessRequest::where('employee_id', $input['employee_id'])
            ->where('status', true)
            ->exists();
        if ($alreadyPending) {
            return;
        }

        UserAccessRequest::create([
            'employee_id' => $input['employee_id'],
        ]);

        $this->notifyRequest($input['employee_id']);
    }

    /**
     * Telegram notice for a new request. Never fails the caller: creating the
     * employee must not depend on Telegram being reachable.
     */
    private function notifyRequest(int $employeeId): void
    {
        try {
            $settings = Setting::whereIn('key', ['default_request_user_group_id', 'telegram_bot_token'])->pluck('value', 'key');
            $groupId = $settings['default_request_user_group_id'] ?? null;
            $botToken = $settings['telegram_bot_token'] ?? null;
            if (!$groupId || !$botToken) {
                return;
            }

            $employee = Employee::with(['branch', 'position'])->find($employeeId);
            if (!$employee) {
                return;
            }

            $esc = fn($v) => htmlspecialchars((string)($v ?? '-'), ENT_NOQUOTES, 'UTF-8');
            $b = fn($v) => '<b>' . $esc($v) . '</b>';

            $message = "📝 " . $b("សំណើបង្កើតអ្នកប្រើប្រាស់") . "\n";
            $message .= "━━━━━━━━━━━━━━━\n";
            $message .= "បុគ្គលិក៖ " . $b($employee->name_kh) . "\n";
            $message .= "អត្តលេខ៖ " . $b($employee->code) . "\n";
            $message .= "តួនាទី៖ " . $esc($employee->position?->name_kh) . "\n";
            $message .= "សាខា៖ " . $esc($employee->branch?->name_kh) . "\n";
            $message .= "━━━━━━━━━━━━━━━\n";
            $message .= "សូមបង្កើតគណនីប្រើប្រាស់សម្រាប់បុគ្គលិកនេះ";

            app(TelegramService::class)->sendMessage($message, [$groupId], $botToken, 'HTML');
        } catch (\Throwable $e) {
            Log::warning('User request Telegram notice failed: ' . $e->getMessage());
        }
    }
}

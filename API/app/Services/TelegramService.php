<?php

namespace App\Services;

class TelegramService
{
    /**
     * Fetches and calculates detailed information for a given loan.
     *
     * @param int $loanId
     * @return array
     */
    public function sendMessage($message, $groupIds = null, $botToken = null, $parseMode = 'Markdown')
    {
        $api_url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $sent_all_successfully = true;

        foreach ($groupIds ?? [] as $group) {
            $data = [
                'chat_id' => $group,
                'text' => $message,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false || $http_status !== 200) {
                error_log("Failed to send message to group {$group}. HTTP {$http_status}, cURL: " . curl_error($ch) . ", body: " . (string)$response);
                $sent_all_successfully = false;
            }

            curl_close($ch);
        }

        return $sent_all_successfully;
    }
}

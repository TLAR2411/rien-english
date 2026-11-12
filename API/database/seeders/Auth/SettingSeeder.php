<?php

namespace Database\Seeders\Auth;

use App\Models\Core\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = env('DEFAULT_USER_PASSWORD') ?: Str::password(16);
        if (! env('DEFAULT_USER_PASSWORD')) {
            $this->command?->warn("Generated default user password (shown once): {$defaultPassword}");
        }

        $insert = [
            [
                'name' => 'Company Name',
                'key' => 'company_name',
                'value' => 'My Company',
            ],
            [
                'name' => 'Company Email',
                'key' => 'company_email',
                'value' => '@example.com',
            ],
            [
                'name' => 'Default Password',
                'key' => 'default_password',
                'value' => $defaultPassword,
            ],
            [
                'name' => 'Default Sub User Password',
                'key' => 'default_sub_user_password',
                'value' => Str::uuid(),
            ],
            // Telegram: leave empty to turn the alerts off
            [
                'name' => 'Default Login Alert Telegram',
                'key' => 'default_login_alert_telegram',
                'value' => '',
            ],
            [
                'name' => 'Telegram Bot Token',
                'key' => 'telegram_bot_token',
                'value' => '',
            ],
            [
                'name' => 'Default Request User Group ID',
                'key' => 'default_request_user_group_id',
                'value' => '',
            ],
        ];

        foreach ($insert as $key => $value) {
            Setting::create($value);
        }
    }
}

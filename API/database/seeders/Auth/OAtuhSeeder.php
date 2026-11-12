<?php

namespace Database\Seeders\Auth;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;

class OAtuhSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $id = config('services.passport.client_id');
        $secret = config('services.passport.client_secret');

        if (! $id || ! $secret) {
            $this->command?->warn('PASSPORT_CLIENT_ID or PASSPORT_CLIENT_SECRET is empty. Run: php artisan passport:client --password');

            return;
        }

        DB::table('oauth_clients')->insert([
            'id' => $id,
            'user_id' => null,
            'name' => 'Laravel Password Grant Client',
            'secret' => $secret,
            'provider' => 'users',
            'redirect' => 'http://localhost',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Needed by change-user to issue tokens
        app(ClientRepository::class)->createPersonalAccessClient(null, 'Learning English Personal Access Client', 'http://localhost');
    }
}

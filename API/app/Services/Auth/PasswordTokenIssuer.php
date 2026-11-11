<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;

/**
 * Gets an access + refresh token for an email and password from Passport
 * (the "password" grant). Used by login and sign-up.
 * Tests swap this class for a fake, so they need no running OAuth server.
 */
class PasswordTokenIssuer
{
    /**
     * @return object{access_token: string, refresh_token: ?string, expires_in: int}
     */
    public function issue(string $email, string $password, ?string $clientId): object
    {
        $response = (new Client)->request('POST', config('services.passport.oauth_url').'/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $clientId ?? '',
                'client_secret' => config('services.passport.client_secret'),
                'username' => $email,
                'password' => $password,
            ],
        ]);

        return json_decode($response->getBody());
    }
}

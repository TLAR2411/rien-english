<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Passport will use when
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Passport Database Connection
    |--------------------------------------------------------------------------
    |
    | By default, Passport's models will utilize your application's default
    | database connection. If you wish to use a different connection you
    | may specify the configured name of the database connection here.
    |
    */

    'connection' => env('PASSPORT_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    |
    | By default, Passport uses auto-incrementing primary keys when assigning
    | IDs to clients. However, if Passport is installed using the provided
    | --uuids switch, this will be set to "true" and UUIDs will be used.
    |
    */

    'client_uuids' => true,

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    |
    | If you enable client hashing, you should set the personal access client
    | ID and unhashed secret within your environment file. The values will
    | get used while issuing fresh personal access tokens to your users.
    |
    */

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | access_minutes: short, because the app renews it silently with the
    |   refresh token. Keeps a stolen token useful only for a short time.
    | refresh_days: how long a user can stay away and still come back logged
    |   in. Every renewal gives a new refresh token, so people who use the
    |   app at least once in this window never have to log in again.
    | personal_hours: tokens from "switch user". They can't be renewed, so
    |   this is the longest a switched session lasts (one working day).
    |
    */

    'lifetimes' => [
        'access_minutes' => (int) env('PASSPORT_ACCESS_MINUTES', 60),
        'refresh_days' => (int) env('PASSPORT_REFRESH_DAYS', 30),
        'personal_hours' => (int) env('PASSPORT_PERSONAL_HOURS', 12),
    ],

];

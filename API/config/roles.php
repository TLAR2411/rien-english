<?php

/*
|--------------------------------------------------------------------------
| Roles — who can do what
|--------------------------------------------------------------------------
| Change who can open a part of the system here, then run:
|   php artisan db:seed --class="Database\Seeders\Auth\RoleSeeder"
*/

return [

    // Role every new sign-up gets (see App\Actions\Auth\CreateStudentAccount)
    'student' => 'student',

    // Logins by these roles send no Telegram login alert (too many students)
    'no_login_alert' => ['student'],

    // Sign-up tries allowed per minute from one internet address (IP)
    'signup_per_minute' => (int) env('SIGNUP_PER_MINUTE', 5),

    // "Forgot password" + "set new password" tries per minute from one IP
    'password_reset_per_minute' => (int) env('PASSWORD_RESET_PER_MINUTE', 5),

    // Route groups (see App\Support\AccessGroup)
    'groups' => [
        // May open /admin (learning content: levels, courses, lessons, quizzes)
        'admin_area' => ['admin', 'teacher'],

        // May manage the system: users, roles, branches, settings, logs
        'admin_only' => ['admin'],

        // Student features: mark lessons completed (later: quizzes, progress)
        'student_area' => ['student'],
    ],

    // Permissions each role gets from RoleSeeder.
    // '*' = every permission. Otherwise a list of permission groups or names
    // (see database/seeders/PermissionSeeder.php).
    'permissions' => [
        'admin' => '*',
        'teacher' => [
            'admin-allow-part',
            'levels',
            'courses',
            'lessons',
            'quizzes',
        ],
    ],

];

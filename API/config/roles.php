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

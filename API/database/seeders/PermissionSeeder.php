<?php

namespace Database\Seeders;

use App\Models\Auth\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    private $attributes = [
        'view',
        'add',
        'edit',
        'delete',
        'force-delete',
        'restore',
    ];

    // Plain add / view / edit / delete (no restore) — used by the learning screens
    private const CRUD = ['view', 'add', 'edit', 'delete'];

    private function insert($group, $attribute = null, $isAttribute = true)
    {
        // If $attribute is not null, merge it with the default $this->attribute
        if (! is_null($attribute)) {
            $attribute = array_unique(array_merge(($isAttribute == true ? $this->attributes : []), $attribute));
        } else {
            $attribute = ($isAttribute == true ? $this->attributes : []);
        }

        foreach ($attribute as $item) {
            // firstOrCreate so the seeder can be re-run without duplicates
            Permission::firstOrCreate(
                ['name' => $item.'-'.$group],
                [
                    'group' => $group,
                    'display_name' => $item,
                ]
            );
        }
    }

    public function run(): void
    {
        // One permission per system part (see WEB/src/composables/useSystemParts.js)
        $this->insert('allow-part', [
            'admin',
        ], false);

        $this->insert('branches');
        $this->insert('users', [
            'change-active',
            'change-password',
            'change-permission',
        ]);

        $this->insert('activity-log');

        $this->insert('roles');
        $this->insert('permissions');
        $this->insert('villages');
        $this->insert('communes');
        $this->insert('districts');
        $this->insert('provinces');

        // Admin General Settings page (company info, default passwords, Telegram)
        $this->insert('settings', ['view', 'edit'], false);

        // Learning content (admin + teacher, see config/roles.php)
        $this->insert('levels', self::CRUD, false);
        $this->insert('courses', self::CRUD, false);
        $this->insert('lessons', self::CRUD, false);
        $this->insert('quizzes', self::CRUD, false);
    }
}

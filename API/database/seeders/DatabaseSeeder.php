<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\Address\AddressSeeder;
use Database\Seeders\Auth\OAtuhSeeder;
use Database\Seeders\Auth\PositionSeeder;
use Database\Seeders\Auth\RoleSeeder;
use Database\Seeders\Auth\SettingSeeder;
use Database\Seeders\Auth\UserSeeder;
use Database\Seeders\Core\BranchSeeder;
use Database\Seeders\Core\CompanySeeder;
use Database\Seeders\Core\DepartmentSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            OAtuhSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            BranchSeeder::class,
            SettingSeeder::class,

            AddressSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            UserSeeder::class,
        ]);
    }
}

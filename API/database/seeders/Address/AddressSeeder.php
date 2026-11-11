<?php

namespace Database\Seeders\Address;

use DB;
use File;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sqlFiles = [

            "provinces.sql",
            "districts.sql",
            "communes.sql",
            "villages.sql",
        ];

        foreach ($sqlFiles as $file) {
            $path = database_path('seeders/Address/' . $file);
            if (File::exists($path)) {
                $sql = File::get($path);
                DB::unprepared($sql);
                $this->command->info("Successfully seeded: {$file}");
            } else {
                $this->command->error("File not found: {$path}");
            }
        }
    }
}

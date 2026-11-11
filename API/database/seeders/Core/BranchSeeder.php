<?php

namespace Database\Seeders\Core;

use App\Models\Core\Branch;
use App\Models\Core\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::forceCreate([
            'company_id' => Company::first()->id,
            'name_kh' => 'សាខាទី១',
            'name_en' => 'Branch 1',
            'abbr' => 'B1',
            'is_head' => true,
            'region' => 0,
            'start_date' => now(),
        ]);
    }
}

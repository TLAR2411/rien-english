<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // department_id: see DepartmentSeeder (1 Management, 2 HR, 3 IT)
        $positions = [
            [
                'name_kh' => "អ្នកអភិវឌ្ឍន៍កម្មវិធី",
                'name_en' => "Developer",
                'abbr' => "DEV",
                'level' => 70,
                'department_id' => 3,
            ],
            [
                'name_kh' => "នាយកប្រតិបត្តិ",
                'name_en' => "Chief Executive Officer",
                'abbr' => "CEO",
                'level' => 99,
                'department_id' => 1,
            ],
            [
                'name_kh' => "ប្រធាននាយកដ្ឋានធនធានមនុស្ស",
                'name_en' => "Head of Human Resources Department",
                'abbr' => "HHR",
                'level' => 89,
                'department_id' => 2,
            ],
            [
                'name_kh' => "ប្រធាននាយកដ្ឋានបច្ចេកវិទ្យាពត៍មាន",
                'name_en' => "Head of Information Technology Department",
                'abbr' => "CIO",
                'level' => 89,
                'department_id' => 3,
            ],
            [
                'name_kh' => "មន្ត្រីធនធានមនុស្ស",
                'name_en' => "HR Officer",
                'abbr' => "HRO",
                'level' => 30,
                'department_id' => 2,
            ],
        ];
        foreach ($positions as $item) {
            Position::create($item);
        }
    }
}

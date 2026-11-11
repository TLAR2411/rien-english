<?php

namespace Database\Seeders\Core;

use App\Models\Core\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            // 1
            [
                'name_kh' => "គណៈគ្រប់គ្រង",
                'name_en' => "Management"
            ],
            // 2
            [
                'name_kh' => "នាយកដ្ឋានធនធានមនុស្ស",
                'name_en' => "Human Resources Department"
            ],
            // 3
            [
                'name_kh' => "នាយកដ្ឋានបច្ចេកវិទ្យាពត៍មាន",
                'name_en' => "Department of Information Technology"
            ],
        ];
        foreach ($departments as $item) {
            Department::create($item);
        }
    }
}

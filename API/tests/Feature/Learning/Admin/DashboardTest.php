<?php

namespace Tests\Feature\Learning\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_students_and_teachers(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $this->makeUser($branch, [], 'student');
        $this->makeUser($branch, [], 'student');
        Passport::actingAs($this->makeUser($branch, [], 'teacher'));

        $this->getJson('/api/web/admin/dashboard')
            ->assertOk()
            ->assertJson(['status' => true, 'data' => ['students' => 2, 'teachers' => 1]]);
    }
}

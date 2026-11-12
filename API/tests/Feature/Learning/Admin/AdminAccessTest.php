<?php

namespace Tests\Feature\Learning\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_must_log_in(): void
    {
        $this->getJson('/api/web/admin/dashboard')->assertUnauthorized();
    }

    public static function adminAreaRoles(): array
    {
        return [['admin', 200], ['teacher', 200], ['student', 403], ['staff', 403]];
    }

    #[DataProvider('adminAreaRoles')]
    public function test_only_admin_and_teacher_open_the_admin_area(string $role, int $status): void
    {
        Passport::actingAs($this->userWithRole($role));

        $this->getJson('/api/web/admin/dashboard')->assertStatus($status);
    }

    public function test_managing_users_is_admin_only(): void
    {
        Passport::actingAs($this->grant($this->userWithRole('teacher'), 'view-users'));
        $this->getJson('/api/web/users-list')->assertForbidden();

        Passport::actingAs($this->grant($this->userWithRole('admin'), 'view-users'));
        $this->getJson('/api/web/users-list')->assertOk();
    }

    public function test_student_cannot_use_system_routes_even_with_a_permission(): void
    {
        Passport::actingAs($this->grant($this->userWithRole('student'), 'view-branches'));

        $this->getJson('/api/web/branches-list')->assertForbidden();
    }
}

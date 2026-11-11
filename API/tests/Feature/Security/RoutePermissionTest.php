<?php

namespace Tests\Feature\Security;

use App\Models\Auth\Position;
use App\Models\Core\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoutePermissionTest extends TestCase
{
    use RefreshDatabase;

    public static function guardedRoutes(): array
    {
        return [
            ['post', 'users-permission'],
            ['post', 'users-change-password'],
            ['post', 'users-change-username-email'],
            ['post', 'users-link-user'],
            ['get', 'users-show'],
            ['post', 'cache-clear'],
            ['post', 'log-viewer-list'],
            ['post', 'export-excel'],
            ['patch', 'branches-disable/1'],
            ['patch', 'departments/1'],
            ['patch', 'employees-disable/1'],
            ['patch', 'employees-terminate/1'],
            ['patch', 'provinces-disable/1'],
            ['patch', 'districts-disable/1'],
            ['patch', 'communes-disable/1'],
            ['patch', 'villages-disable/1'],
        ];
    }

    #[DataProvider('guardedRoutes')]
    public function test_route_needs_a_permission(string $method, string $uri): void
    {
        Passport::actingAs($this->makeUser($this->makeBranch($this->makeCompany())));

        $this->json($method, "/api/web/{$uri}")->assertForbidden();
    }

    public function test_cannot_escalate_by_setting_own_permissions(): void
    {
        $user = $this->makeUser($this->makeBranch($this->makeCompany()));
        Passport::actingAs($user);

        $this->postJson('/api/web/users-permission', ['id' => $user->id, 'permissions' => [1]])->assertForbidden();
        $this->assertSame(0, $user->permissions()->count());
    }

    public function test_only_a_super_user_can_change_a_super_users_password(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $super = $this->makeUser($branch, ['is_super' => true]);
        $staff = $this->grant($this->makeUser($branch), 'change-password-users');
        Passport::actingAs($staff);

        $this->postJson('/api/web/users-change-password', ['id' => $super->id, 'password' => 'new-password-1'])->assertForbidden();

        $colleague = $this->makeUser($branch);
        $this->postJson('/api/web/users-change-password', ['id' => $colleague->id, 'password' => 'new-password-1'])->assertOk();
    }

    public function test_creating_a_user_cannot_set_is_super_or_wider_branch_access(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $staff = $this->grant($this->makeUser($branch), 'add-users');
        Passport::actingAs($staff);
        $department = Department::forceCreate(['name_kh' => 'General', 'name_en' => 'General']);
        $position = Position::forceCreate(['name_kh' => 'Staff', 'name_en' => 'Staff', 'abbr' => 'STF', 'department_id' => $department->id]);
        $payload = [
            'name_kh' => 'New', 'name_en' => 'New Person', 'dob' => '2000-01-01', 'gender' => 'male',
            'role_id' => $staff->role_id, 'join_date' => '2026-01-01',
            'position_id' => $position->id,
        ];
        $this->withHeader('X-Branch-Id', $branch->id);

        $this->postJson('/api/web/users', [...$payload, 'manage_branch' => 3])->assertForbidden();

        $this->postJson('/api/web/users', [...$payload, 'manage_branch' => 1, 'is_super' => true])->assertOk();
        $this->assertDatabaseHas('users', ['name_kh' => 'New', 'is_super' => false, 'branch_id' => $branch->id]);
    }

    public function test_a_new_employee_goes_to_the_header_branch(): void
    {
        $company = $this->makeCompany();
        $branch = $this->makeBranch($company);
        $other = $this->makeBranch($company);
        Passport::actingAs($this->grant($this->makeUser($branch, ['manage_branch' => 3]), 'add-employees'));
        $payload = ['name_kh' => 'New', 'name_en' => 'New Person', 'gender' => 'male', 'choose_branch_id' => $other->id];

        $this->postJson('/api/web/employees', $payload, ['X-Branch-Id' => '*'])
            ->assertUnprocessable()->assertJsonValidationErrors('branch');

        $this->postJson('/api/web/employees', $payload, ['X-Branch-Id' => $branch->id])->assertOk();
        $this->assertDatabaseHas('employees', ['name_kh' => 'New', 'branch_id' => $branch->id]);
    }

    public function test_a_user_made_from_an_employee_stays_in_the_employees_branch(): void
    {
        $company = $this->makeCompany();
        $branch = $this->makeBranch($company);
        $other = $this->makeBranch($company);
        $staff = $this->grant($this->makeUser($branch, ['manage_branch' => 3]), 'add-users', 'add-employees');
        Passport::actingAs($staff);
        $department = Department::forceCreate(['name_kh' => 'General', 'name_en' => 'General']);
        $position = Position::forceCreate(['name_kh' => 'Staff', 'name_en' => 'Staff', 'abbr' => 'STF', 'department_id' => $department->id]);
        $this->postJson('/api/web/employees', ['name_kh' => 'Emp', 'name_en' => 'Emp Person', 'gender' => 'male'], ['X-Branch-Id' => $other->id])->assertOk();

        $this->postJson('/api/web/users', [
            'name_kh' => 'Emp', 'name_en' => 'Emp Person', 'dob' => '2000-01-01', 'gender' => 'male',
            'employee_id' => \App\Models\Hr\Employee::first()->id, 'role_id' => $staff->role_id,
            'join_date' => '2026-01-01', 'position_id' => $position->id, 'manage_branch' => 1,
        ], ['X-Branch-Id' => '*'])->assertOk();

        $this->assertDatabaseHas('users', ['name_kh' => 'Emp', 'branch_id' => $other->id]);
    }

    public function test_public_proxy_refuses_other_hosts(): void
    {
        $this->get('/h2c-proxy?url=' . urlencode('http://169.254.169.254/latest/meta-data/'))->assertForbidden();
        $this->get('/h2c-proxy?url=' . urlencode('file:///etc/passwd'))->assertStatus(400);
    }
}

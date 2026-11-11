<?php

namespace Tests\Feature\Security;

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
        $payload = [
            'name_kh' => 'New', 'name_en' => 'New Person', 'dob' => '2000-01-01', 'gender' => 'male',
            'role_id' => $staff->role_id, 'join_date' => '2026-01-01',
        ];
        $this->withHeader('X-Branch-Id', $branch->id);

        $this->postJson('/api/web/users', [...$payload, 'manage_branch' => 3])->assertForbidden();

        $this->postJson('/api/web/users', [...$payload, 'manage_branch' => 1, 'is_super' => true])->assertOk();
        $this->assertDatabaseHas('users', ['name_kh' => 'New', 'is_super' => false, 'branch_id' => $branch->id]);
    }

    public function test_public_proxy_refuses_other_hosts(): void
    {
        $this->get('/h2c-proxy?url='.urlencode('http://169.254.169.254/latest/meta-data/'))->assertForbidden();
        $this->get('/h2c-proxy?url='.urlencode('file:///etc/passwd'))->assertStatus(400);
    }
}

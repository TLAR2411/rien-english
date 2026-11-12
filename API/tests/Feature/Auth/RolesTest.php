<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\CreateStudentAccount;
use App\Models\Auth\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.admin.email' => 'owner@example.test', 'app.admin.password' => 'secret-from-env']);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_learning_roles_exist(): void
    {
        foreach (['admin', 'teacher', 'student'] as $name) {
            $this->assertDatabaseHas('roles', ['name' => $name]);
        }
    }

    public function test_admin_is_seeded_from_env_values(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->assertSame('owner@example.test', $admin->email);
        $this->assertTrue(Hash::check('secret-from-env', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_new_student_account_gets_the_student_role(): void
    {
        $user = app(CreateStudentAccount::class)->handle('Sok Dara', 'Sok.Dara@Example.test', 'password-123');

        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse($user->hasRole(['admin', 'teacher']));
        $this->assertSame(Role::where('name', 'student')->value('id'), $user->role_id);
        $this->assertSame('student', $user->default_part);
        $this->assertSame('sok.dara@example.test', $user->email);
        $this->assertSame('sokdara', $user->username);
        $this->assertTrue(Hash::check('password-123', $user->password));
        $this->assertDatabaseHas('user_branches', ['user_id' => $user->id, 'branch_id' => $user->branch_id]);
    }

    public function test_student_usernames_stay_unique(): void
    {
        $action = app(CreateStudentAccount::class);

        $first = $action->handle('Dara', 'dara@one.test', 'password-123');
        $second = $action->handle('Dara', 'dara@two.test', 'password-123');

        $this->assertSame('dara', $first->username);
        $this->assertSame('dara2', $second->username);
    }
}

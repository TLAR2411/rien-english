<?php

namespace Tests;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\Core\Branch;
use App\Models\Core\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Stops before any trait can touch the database: RefreshDatabase wipes
     * every table, so it must never run on a real database.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException("Refusing to run tests on database [{$database}]. Its name must end in _test.");
        }

        return $app;
    }

    protected function makeCompany(): Company
    {
        return Company::forceCreate(['name_kh' => 'Owner '.Str::random(8)]);
    }

    protected function makeBranch(Company $company, array $attributes = []): Branch
    {
        return Branch::forceCreate([
            'name_kh' => 'Building',
            'abbr' => strtoupper(Str::random(3)),
            'company_id' => $company->id,
            ...$attributes,
        ]);
    }

    /**
     * A user with one role (default `admin`) and no permissions yet — use grant() to add some.
     */
    protected function makeUser(Branch $branch, array $attributes = [], string $role = 'admin'): User
    {
        $role = Role::firstOrCreate(['name' => $role], ['display_name' => Str::headline($role)]);
        $username = 'user'.Str::lower(Str::random(10));

        $user = User::forceCreate([
            'branch_id' => $branch->id,
            'name_kh' => 'Staff',
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => 'not-used',
            'role_id' => $role->id,
            ...$attributes,
        ]);
        $user->addRole($role);

        return $user->refresh();
    }

    /** A user with the given role in a new company + branch. */
    protected function userWithRole(string $role): User
    {
        return $this->makeUser($this->makeBranch($this->makeCompany()), [], $role);
    }

    protected function grant(User $user, string ...$permissions): User
    {
        foreach ($permissions as $name) {
            $user->givePermission(Permission::firstOrCreate(
                ['name' => $name],
                ['group' => 'test', 'display_name' => $name]
            ));
        }

        return $user;
    }
}

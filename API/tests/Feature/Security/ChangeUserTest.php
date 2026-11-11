<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ChangeUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_switch_to_an_unlinked_user(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $admin = $this->makeUser($branch, ['is_super' => true]);
        Passport::actingAs($this->makeUser($branch));

        $this->postJson('/api/web/change-user', ['user_id' => $admin->id])->assertForbidden();
    }

    public function test_cannot_switch_to_another_users_sub_account(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $parent = $this->makeUser($branch);
        $sub = $this->makeUser($branch, ['type' => 'sub', 'parent_user_id' => $parent->id]);
        Passport::actingAs($this->makeUser($branch));

        $this->postJson('/api/web/change-user', ['user_id' => $sub->id])->assertForbidden();
    }

    public function test_can_switch_between_linked_accounts(): void
    {
        $this->artisan('passport:client', ['--personal' => true, '--name' => 'test', '--no-interaction' => true]);

        $branch = $this->makeBranch($this->makeCompany());
        $parent = $this->makeUser($branch);
        $sub = $this->makeUser($branch, ['type' => 'sub', 'parent_user_id' => $parent->id]);
        $sibling = $this->makeUser($branch, ['type' => 'sub', 'parent_user_id' => $parent->id]);

        Passport::actingAs($parent);
        $this->postJson('/api/web/change-user', ['user_id' => $sub->id])->assertOk();

        Passport::actingAs($sub);
        $this->postJson('/api/web/change-user', ['user_id' => $parent->id])->assertOk();
        $this->postJson('/api/web/change-user', ['user_id' => $sibling->id])->assertOk();
    }
}

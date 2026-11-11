<?php

namespace Tests\Feature\Security;

use App\Models\Auth\Position;
use App\Models\Auth\UserBranch;
use App\Models\Core\Department;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manage_branch_modes_stay_inside_the_users_company(): void
    {
        $company = $this->makeCompany();
        $a = $this->makeBranch($company);
        $b = $this->makeBranch($company);
        $c = $this->makeBranch($company);
        $disabled = $this->makeBranch($company, ['is_active' => false]);
        $otherOwner = $this->makeBranch($this->makeCompany());

        $one = $this->makeUser($a, ['manage_branch' => 1]);
        $listed = $this->makeUser($a, ['manage_branch' => 2]);
        UserBranch::create(['user_id' => $listed->id, 'branch_id' => $b->id]);
        $all = $this->makeUser($a, ['manage_branch' => 3]);
        $except = $this->makeUser($a, ['manage_branch' => 4]);
        UserBranch::create(['user_id' => $except->id, 'branch_id' => $b->id]);

        $context = app(BranchContext::class);

        $this->assertSame([$a->id], $context->allowedBranchIds($one)->all());
        $this->assertSame([$a->id, $b->id], $context->allowedBranchIds($listed)->all());
        $this->assertSame([$a->id, $b->id, $c->id], $context->allowedBranchIds($all)->all());
        $this->assertSame([$a->id, $c->id], $context->allowedBranchIds($except)->all());
        $this->assertFalse($context->allows($all, $disabled->id));
        $this->assertFalse($context->allows($all, $otherOwner->id));
    }

    public function test_header_for_a_forbidden_branch_is_rejected(): void
    {
        $company = $this->makeCompany();
        $own = $this->makeBranch($company);
        $other = $this->makeBranch($company);
        Passport::actingAs($this->makeUser($own));

        $this->getJson('/api/web/branches-all', ['X-Branch-Id' => $other->id])->assertForbidden();
        $this->getJson('/api/web/branches-all', ['X-Branch-Id' => $own->id])->assertOk();
    }

    public function test_star_only_returns_allowed_rows(): void
    {
        $company = $this->makeCompany();
        $own = $this->makeBranch($company);
        $other = $this->makeBranch($company);
        $colleague = $this->makeUser($own);
        $stranger = $this->makeUser($other);
        $user = $this->makeUser($own);
        Passport::actingAs($user);

        $ids = collect($this->getJson('/api/web/users-all', ['X-Branch-Id' => '*'])->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($colleague->id));
        $this->assertFalse($ids->contains($stranger->id));
    }

    public function test_where_branch_returns_nothing_without_a_logged_in_user(): void
    {
        $this->makeUser($this->makeBranch($this->makeCompany()));

        $this->assertSame(0, User::query()->whereBranch('*')->count());
    }

    public function test_branch_list_and_show_hide_other_owners(): void
    {
        $own = $this->makeBranch($this->makeCompany());
        $otherOwner = $this->makeBranch($this->makeCompany());
        Passport::actingAs($this->makeUser($own, ['manage_branch' => 3]));

        $ids = collect($this->getJson('/api/web/branches-all')->json('data'))->pluck('id');

        $this->assertSame([$own->id], $ids->all());
        $this->getJson('/api/web/branches-show?id=' . $otherOwner->id)->assertStatus(500);
    }

    public function test_under_users_stay_inside_the_owner_and_respect_filters(): void
    {
        $department = Department::forceCreate(['name_kh' => 'General', 'name_en' => 'General']);
        $position = Position::forceCreate([
            'name_kh' => 'Lead', 'name_en' => 'Lead', 'abbr' => 'LD', 'level' => 30, 'department_id' => $department->id,
        ]);

        $company = $this->makeCompany();
        $own = $this->makeBranch($company);
        $head = $this->makeBranch($company, ['is_head' => true]);
        $otherHead = $this->makeBranch($this->makeCompany(), ['is_head' => true]);

        $this->makeUser($own); // keeps id 1, which the query always leaves out
        $lead = $this->makeUser($own, ['position_id' => $position->id]);
        $headLead = $this->makeUser($head, ['position_id' => $position->id]);
        $disabled = $this->makeUser($head, ['position_id' => $position->id, 'is_active' => false]);
        $stranger = $this->makeUser($otherHead, ['position_id' => $position->id]);
        Passport::actingAs($this->makeUser($own));

        $ids = collect($this->getJson('/api/web/users-all-under-users?choose_branch_id=' . $own->id)->assertOk()->json('data'))->pluck('id');

        $this->assertEqualsCanonicalizing([$lead->id, $headLead->id], $ids->all());
        $this->assertFalse($ids->contains($disabled->id));
        $this->assertFalse($ids->contains($stranger->id));
    }

    public function test_session_routes_skip_the_branch_check(): void
    {
        $company = $this->makeCompany();
        $own = $this->makeBranch($company);
        $previous = $this->makeBranch($company);
        Passport::actingAs($this->makeUser($own));

        $response = $this->postJson('/api/web/change-user', ['user_id' => 0], ['X-Branch-Id' => $previous->id]);

        $this->assertSame('User not found or access denied.', $response->json('message'));
    }
}

<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Course;
use App\Models\Learning\Level;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LevelAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    public function test_teacher_can_create_a_level(): void
    {
        $this->postJson('/api/web/admin/levels', ['code' => 'a1', 'name' => 'Beginner', 'name_km' => 'កម្រិតដំបូង'])
            ->assertCreated()
            ->assertJsonPath('data.code', 'A1');

        $this->assertDatabaseHas('levels', ['code' => 'A1', 'sort_order' => 1]);
    }

    public function test_level_code_must_be_unique(): void
    {
        Level::factory()->create(['code' => 'A1']);

        $this->postJson('/api/web/admin/levels', ['code' => 'A1', 'name' => 'Again'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_teacher_can_update_a_level(): void
    {
        $level = Level::factory()->create(['code' => 'A1']);

        $this->putJson("/api/web/admin/levels/{$level->id}", ['code' => 'A1', 'name' => 'Starter'])->assertOk();

        $this->assertSame('Starter', $level->fresh()->name);
    }

    public function test_empty_level_can_be_deleted_but_not_one_with_courses(): void
    {
        $empty = Level::factory()->create();
        $used = Course::factory()->create()->level;

        $this->deleteJson("/api/web/admin/levels/{$empty->id}")->assertOk();
        $this->deleteJson("/api/web/admin/levels/{$used->id}")->assertUnprocessable();

        $this->assertModelMissing($empty);
        $this->assertModelExists($used);
    }

    public function test_levels_can_be_moved_up_and_down(): void
    {
        [$a, $b, $c] = Level::factory()->count(3)->create()->all();

        $this->patchJson("/api/web/admin/levels/{$c->id}/move", ['direction' => 'up'])->assertOk();

        $this->assertSame([$a->id, $c->id, $b->id], Level::ordered()->pluck('id')->all());
    }

    public function test_list_is_paginated_for_the_table(): void
    {
        Level::factory()->count(3)->create();

        $this->getJson('/api/web/admin/levels?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.meta.total', 3);
    }

    public function test_student_cannot_manage_levels(): void
    {
        Passport::actingAs($this->userWithRole('student'));

        $this->postJson('/api/web/admin/levels', ['code' => 'A1', 'name' => 'Beginner'])->assertForbidden();
    }
}

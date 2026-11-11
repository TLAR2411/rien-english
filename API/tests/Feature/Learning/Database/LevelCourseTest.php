<?php

namespace Tests\Feature\Learning\Database;

use App\Models\Learning\Course;
use App\Models\Learning\Level;
use Database\Seeders\Learning\LevelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelCourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_levels_with_one_free_grammar_course_each(): void
    {
        $this->seed(LevelSeeder::class);
        $this->seed(LevelSeeder::class); // safe to run twice

        $this->assertSame(['A1', 'A2', 'B1', 'B2', 'C1'], Level::ordered()->pluck('code')->all());
        $this->assertSame(5, Course::count());

        $a1 = Level::where('code', 'A1')->first();
        $this->assertSame('Grammar A1', $a1->courses->first()->title);
        $this->assertTrue($a1->courses->first()->is_free);
        $this->assertSame('grammar-a1', $a1->courses->first()->slug);
    }

    public function test_new_course_goes_to_the_end_of_its_level_and_gets_a_unique_slug(): void
    {
        $level = Level::factory()->create();
        $first = Course::factory()->for($level)->create(['title' => 'Verbs']);
        $second = Course::factory()->for($level)->create(['title' => 'Verbs']);

        $this->assertSame([1, 2], [$first->sort_order, $second->sort_order]);
        $this->assertSame(['verbs', 'verbs-2'], [$first->slug, $second->slug]);
        $this->assertTrue($second->level->is($level));
    }

    public function test_published_scope_hides_drafts(): void
    {
        Course::factory()->create();
        Course::factory()->draft()->create();

        $this->assertSame(1, Course::published()->count());
    }
}

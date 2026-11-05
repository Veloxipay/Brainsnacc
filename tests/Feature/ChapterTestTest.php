<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\QuestionAttempt;
use App\Models\TestAttempt;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChapterTestTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $test;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CourseSeeder::class);
        $this->test = Lesson::where('title', 'Chapter 6 · Test 1')
            ->whereHas('module.unit.level', fn ($q) => $q->where('short_name', 'Year 8'))->firstOrFail();
        $this->test->update(['status' => 'published', 'body' => 'Chapter test']);
        $topics = ['Ratios', 'Rates', 'Scale drawings'];
        foreach (range(1, 15) as $i) {
            $this->test->questions()->create([
                'type' => 'mcq', 'difficulty' => ['easy', 'medium', 'hard'][$i % 3], 'topic' => $topics[$i % 3],
                'prompt' => "Q{$i}", 'options' => ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'], 'answer' => 'B',
                'explanation' => 'Because.', 'steps' => ['First', 'Then'], 'mistakes' => ['A' => 'Step 1: wrong'], 'sort' => $i,
            ]);
        }
    }

    private function student(): User
    {
        return User::factory()->create(['role' => User::ROLE_STUDENT, 'level_id' => Level::where('short_name', 'Year 8')->value('id')]);
    }

    public function test_course_map_lists_four_tests_per_chapter(): void
    {
        $this->assertSame(176, Lesson::where('kind', 'test')->where('title', 'like', 'Chapter % · Test %')->count());
        $this->assertSame(22, Lesson::where('kind', 'test')->where('title', 'like', 'Advanced % · Test %')->count());
        $selective = Lesson::where('kind', 'test')->whereHas('module.unit.subject', fn ($q) => $q->where('slug', 'selective'));
        $this->assertSame(12, (clone $selective)->count());
        $this->assertSame([30], (clone $selective)->pluck('minutes')->unique()->values()->all());
        $this->actingAs($this->student())->get(route('course', ['subject' => 'maths', 'level' => Level::where('short_name', 'Year 8')->value('id')]))
            ->assertSee('Chapter 6 · Test 1')->assertSee('Chapter 6 · Test 4');
    }

    public function test_sitting_a_test_shows_no_hints_and_marks_at_the_end(): void
    {
        $u = $this->student();
        $this->actingAs($u)->get(route('lessons.show', $this->test))->assertRedirect(route('tests.show', $this->test));
        $this->get(route('tests.show', $this->test))->assertOk()->assertSee('Q15')
            ->assertDontSee('Show a hint')->assertDontSee('Check answer');

        $answers = [];
        foreach ($this->test->questions as $q) {
            $answers[$q->id] = $q->topic === 'Rates' ? 'A' : 'B';
        }
        $this->post(route('tests.submit', $this->test), ['answers' => $answers, 'started' => now()->subMinutes(20)->timestamp])->assertRedirect();
        $attempt = TestAttempt::firstOrFail();
        $this->assertSame(10, $attempt->score);
        $this->assertSame([0, 5], $attempt->by_topic['Rates']);
        $this->assertSame(15, QuestionAttempt::where('help_used', 0)->count());

        $this->get(route('tests.result', [$this->test, $attempt]))->assertOk()
            ->assertSee('10 / 15')->assertSee('Rates')->assertSee('Step 1: wrong');
    }

    public function test_results_are_private_and_parents_see_scores(): void
    {
        $u = $this->student();
        $this->actingAs($u)->post(route('tests.submit', $this->test), ['answers' => []]);
        $attempt = TestAttempt::firstOrFail();
        $this->assertSame(0, $attempt->score);

        $this->actingAs($this->student())->get(route('tests.result', [$this->test, $attempt]))->assertForbidden();

        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        DB::table('student_supervisors')->insert(['supervisor_id' => $parent->id, 'student_id' => $u->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($parent)->get(route('family.students.show', $u))->assertSee('Chapter tests')->assertSee('0/15');
    }
}

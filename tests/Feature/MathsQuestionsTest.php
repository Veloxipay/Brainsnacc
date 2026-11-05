<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\StepCheck;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Database\Seeders\LessonContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MathsQuestionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CourseSeeder::class);
        $this->seed(LessonContentSeeder::class);
        config(['services.anthropic.key' => null]);
    }

    private function year8(): User
    {
        return User::factory()->create(['role' => User::ROLE_STUDENT, 'level_id' => Level::where('short_name', 'Year 8')->value('id')]);
    }

    public function test_year_8_dashboard_shows_maths_only(): void
    {
        $this->actingAs($this->year8())->get('/dashboard')
            ->assertOk()->assertSee('Maths')->assertDontSee('Chemistry</h2>', false);
    }

    public function test_challenge_questions_have_levels_easy_first(): void
    {
        $lesson = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail();
        $this->assertSame(20, $lesson->questions->count());
        $this->assertSame('easy', $lesson->questions->sortBy('sort')->first()->difficulty);
        $this->assertSame('challenge', $lesson->questions->sortBy('sort')->last()->difficulty);

        $this->actingAs($this->year8())->get(route('lessons.show', $lesson))
            ->assertOk()->assertSee('Easy')->assertSee('Challenge');
    }

    public function test_wrong_answer_asks_for_steps_and_guide_points_to_the_step(): void
    {
        $student = $this->year8();
        $lesson = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail();
        $q = $lesson->questions->first(fn ($q) => str_contains($q->prompt, 'shared in the ratio 4 : 5'));
        $wrong = collect($q->options)->search('$160');

        $this->actingAs($student)->post(route('questions.answer', $q), ['answer' => $wrong])
            ->assertRedirect();
        $this->get(route('lessons.show', $lesson))->assertSee('What steps did you follow?');

        $this->post(route('questions.steps', $q), ['answer' => $wrong, 'steps' => "360 / 9 = 40\n4 x 40 = 160"])
            ->assertRedirect();
        $check = StepCheck::firstOrFail();
        $this->assertSame('guide', $check->source);
        $this->assertStringContainsString('smaller share', $check->feedback);
        $this->get(route('lessons.show', $lesson))->assertSee('smaller share');
    }

    public function test_steps_cannot_be_checked_for_the_right_answer(): void
    {
        $q = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail()->questions->first();
        $this->actingAs($this->year8())->post(route('questions.steps', $q), ['answer' => $q->answer, 'steps' => 'my steps'])
            ->assertStatus(422);
    }

    public function test_hints_are_free_never_show_the_last_step_and_mark_the_answer_as_helped(): void
    {
        $student = $this->year8();
        $lesson = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail();
        $q = $lesson->questions->first(fn ($q) => count($q->steps ?? []) === 3);
        $this->actingAs($student);
        foreach (range(1, 4) as $i) {
            $this->post(route('questions.hint', $q))->assertRedirect();
        }
        $page = $this->get(route('lessons.show', $lesson))->assertSee($q->steps[0])->assertSee($q->steps[1]);
        $page->assertDontSee($q->steps[2]);
        $page->assertSee('the last step is yours');

        $this->post(route('questions.answer', $q), ['answer' => $q->answer]);
        $this->assertSame(2, \App\Models\QuestionAttempt::latest('id')->value('help_used'));
        $q2 = $lesson->questions->firstWhere('id', '!=', $q->id);
        $this->post(route('questions.answer', $q2), ['answer' => $q2->answer]);
        $this->assertSame(0, \App\Models\QuestionAttempt::latest('id')->value('help_used'));
    }

    public function test_explain_the_question_works_without_ai_and_counts_as_help(): void
    {
        $student = $this->year8();
        $lesson = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail();
        $q = $lesson->questions->first();
        $this->actingAs($student)->post(route('questions.explain', $q));
        $this->get(route('lessons.show', $lesson))->assertSee('What this question is asking');
        $this->post(route('questions.answer', $q), ['answer' => $q->answer]);
        $this->assertSame(1, \App\Models\QuestionAttempt::latest('id')->value('help_used'));
    }

    public function test_equation_questions_link_to_the_tutor(): void
    {
        $y7 = User::factory()->create(['role' => User::ROLE_STUDENT, 'level_id' => Level::where('short_name', 'Year 7')->value('id')]);
        $lesson = Lesson::where('title', 'Year 7 Maths challenge')->firstOrFail();
        $this->actingAs($y7)->get(route('lessons.show', $lesson))->assertSee('Work it out line by line');
        $q = $lesson->questions->first(fn ($q) => str_contains($q->prompt, 'x/3'));
        $this->assertSame('x/3 − 2 = 5', \App\Support\QuestionHelp::tutorEquation($q));
    }

    public function test_two_right_on_your_own_suggests_the_next_level(): void
    {
        $student = $this->year8();
        $lesson = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail();
        $easy = $lesson->questions->where('difficulty', 'easy')->values();
        $this->actingAs($student)->post(route('questions.answer', $easy[0]), ['answer' => $easy[0]->answer]);
        $this->post(route('questions.answer', $easy[1]), ['answer' => $easy[1]->answer])
            ->assertSessionHas("result", function ($r) { return str_contains($r["suggest"]["text"] ?? "", "Medium"); });
    }

    public function test_parent_sees_unaided_accuracy(): void
    {
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        $child = $this->year8();
        \Illuminate\Support\Facades\DB::table('student_supervisors')->insert(['supervisor_id' => $parent->id, 'student_id' => $child->id, 'created_at' => now(), 'updated_at' => now()]);
        $q = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail()->questions->first();
        \App\Models\QuestionAttempt::create(['user_id' => $child->id, 'question_id' => $q->id, 'answer' => $q->answer, 'is_correct' => true, 'help_used' => 0]);
        $this->actingAs($parent)->get(route('family.students.show', $child))->assertOk()->assertSee('Without hints: 100%');
    }

    public function test_two_wrong_at_a_level_suggests_an_easier_one(): void
    {
        $student = $this->year8();
        $lesson = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail();
        $hard = $lesson->questions->where('difficulty', 'hard')->values();
        $wrong = fn ($q) => collect(array_keys($q->options))->first(fn ($l) => $l !== $q->answer);
        $this->actingAs($student)->post(route('questions.answer', $hard[0]), ['answer' => $wrong($hard[0])]);
        $this->post(route('questions.answer', $hard[1]), ['answer' => $wrong($hard[1])])
            ->assertSessionHas('result', fn ($r) => str_contains($r['suggest']['text'] ?? '', 'Medium one first'));
    }

    public function test_question_diagram_is_shown_with_its_description(): void
    {
        $lesson = Lesson::where('title', 'Year 8 Maths challenge')->firstOrFail();
        $q = $lesson->questions->first();
        $q->update(['diagram' => 'diagrams/test/angle.svg', 'diagram_alt' => 'An angle of 65 degrees marked x']);

        $this->actingAs($this->year8())->get(route('lessons.show', $lesson))
            ->assertOk()->assertSee('diagrams/test/angle.svg', false)->assertSee('An angle of 65 degrees marked x');
    }
}

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
}

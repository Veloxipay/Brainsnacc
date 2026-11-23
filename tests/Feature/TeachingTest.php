<?php

namespace Tests\Feature;

use App\Models\HelpRequest;
use App\Models\Homework;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\SessionNote;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $lesson;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CourseSeeder::class);
        $level = Level::where('short_name', 'Year 8')->firstOrFail();
        $this->lesson = Lesson::where('kind', 'lesson')->whereHas('module.unit', fn ($q) => $q->where('level_id', $level->id))->firstOrFail();
        $this->lesson->update(['status' => 'published', 'body' => 'Lesson body']);
        $this->lesson->questions()->create([
            'type' => 'mcq', 'difficulty' => 'easy', 'prompt' => 'What is 2 + 2?',
            'options' => ['A' => '3', 'B' => '4'], 'answer' => 'B', 'explanation' => 'Count.', 'steps' => ['Add'], 'sort' => 1,
        ]);
        $this->teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->student = User::factory()->create(['role' => User::ROLE_STUDENT, 'level_id' => $level->id]);
        $this->teacher->students()->attach($this->student->id);
    }

    public function test_homework_is_set_shown_and_handed_in(): void
    {
        $this->actingAs($this->teacher)->post(route('family.homework.store', $this->student), [
            'lesson_id' => $this->lesson->id, 'due_on' => now()->addDays(2)->toDateString(), 'note' => 'Do all of it',
        ])->assertRedirect();
        $hw = Homework::firstOrFail();
        $this->assertNull($hw->completed_at);

        $this->actingAs($this->student)->get(route('dashboard'))->assertSee('Homework from your tutor')->assertSee($this->lesson->title);
        $this->post(route('lessons.complete', $this->lesson))->assertSessionHas('status', 'Homework handed in. Well done!');
        $this->assertNotNull($hw->fresh()->completed_at);
    }

    public function test_only_linked_tutors_can_set_homework_or_notes(): void
    {
        $stranger = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $this->actingAs($stranger)->post(route('family.homework.store', $this->student), ['lesson_id' => $this->lesson->id])->assertNotFound();
        $this->actingAs($stranger)->post(route('family.notes.store', $this->student), ['taught_on' => now()->toDateString(), 'body' => 'x'])->assertNotFound();
        $this->assertSame(0, Homework::count() + SessionNote::count());
    }

    public function test_session_notes_show_on_the_student_page(): void
    {
        $this->actingAs($this->teacher)->post(route('family.notes.store', $this->student), [
            'taught_on' => now()->toDateString(), 'body' => 'Covered fractions. Revise 2C.',
        ])->assertRedirect();
        $this->get(route('family.students.show', $this->student))->assertOk()
            ->assertSee('Covered fractions. Revise 2C.')->assertSee('Since the last session');
    }

    public function test_student_can_ask_their_tutor_and_tutor_resolves_it(): void
    {
        $q = $this->lesson->questions()->first();
        $this->actingAs($this->student)->get(route('lessons.show', $this->lesson))->assertSee('Ask my tutor about this');
        $this->post(route('ask-tutor'), ['question_id' => $q->id, 'message' => 'Why B?'])->assertRedirect();
        $this->post(route('ask-tutor'), ['question_id' => $q->id, 'message' => 'Why B really?']);
        $this->assertSame(1, HelpRequest::count());

        $this->actingAs($this->teacher)->get(route('family'))->assertSee('1 question to go through');
        $this->get(route('family.students.show', $this->student))->assertSee('Why B really?')->assertSee('What is 2 + 2?');
        $this->post(route('family.help.resolve', HelpRequest::first()))->assertRedirect();
        $this->assertNotNull(HelpRequest::first()->resolved_at);
    }

    public function test_student_without_a_tutor_does_not_see_ask_button(): void
    {
        $alone = User::factory()->create(['role' => User::ROLE_STUDENT, 'level_id' => $this->student->level_id]);
        $this->actingAs($alone)->get(route('lessons.show', $this->lesson))->assertDontSee('Ask my tutor about this');
    }

    public function test_teach_me_runs_and_checks_spoken_answers(): void
    {
        $q = $this->lesson->questions()->first();
        $this->actingAs($this->student)->get(route('teach.show', $this->lesson))->assertOk()
            ->assertSee('Watch me do one')->assertSee('Quick test');
        $this->postJson(route('teach.check', $q), ['said' => 'I got four', 'mode' => 'try'])
            ->assertOk()->assertJson(['result' => 'correct', 'answer' => '4']);
        $this->postJson(route('teach.check', $q), ['said' => 'banana', 'mode' => 'try'])->assertJson(['result' => 'unmatched']);
        $this->postJson(route('teach.check', $q), ['letter' => 'A', 'mode' => 'test'])->assertJson(['result' => 'wrong', 'given' => '3']);
        $this->assertSame(2, \App\Models\QuestionAttempt::where('user_id', $this->student->id)->count());
    }
}

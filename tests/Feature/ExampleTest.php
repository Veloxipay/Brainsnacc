<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\User;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CourseSeeder::class);
    }

    private function student(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'level_id' => Level::where('short_name', 'Year 12')->value('id'),
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_student_sees_dashboard_course_map_and_lesson(): void
    {
        $student = $this->student();
        $lesson = Lesson::where('title', 'Galvanic cells')->firstOrFail();

        $this->actingAs($student)->get('/')->assertRedirect(route('dashboard'));
        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertSee('Galvanic cells');
        $this->actingAs($student)->get(route('course', 'chemistry'))->assertOk()->assertSee('Galvanic cells');
        $this->actingAs($student)->get(route('lessons.show', $lesson))->assertOk()->assertSee('salt bridge');
    }

    public function test_answering_a_question_records_the_attempt(): void
    {
        $student = $this->student();
        $question = Lesson::where('title', 'Galvanic cells')->firstOrFail()->questions()->first();

        $this->actingAs($student)
            ->post(route('questions.answer', $question), ['answer' => $question->answer])
            ->assertRedirect();

        $this->assertDatabaseHas('question_attempts', [
            'user_id' => $student->id,
            'question_id' => $question->id,
            'is_correct' => true,
        ]);
    }

    public function test_students_cannot_open_admin_pages(): void
    {
        $this->actingAs($this->student())->get(route('admin.lessons.index'))->assertForbidden();
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get(route('admin.lessons.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('family'))->assertOk();
    }
}

<?php

namespace Tests\Feature;

use App\Models\AiCall;
use App\Models\HelpEvent;
use App\Models\Level;
use App\Models\MisconceptionObservation;
use App\Models\SkillRecord;
use App\Models\TutorCheck;
use App\Models\TutorSession;
use App\Models\User;
use App\Support\Ai\AiModel;
use App\Tutor\Math\Misconceptions;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TutorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CourseSeeder::class);
        config(['services.anthropic.key' => null]);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => User::ROLE_STUDENT, 'level_id' => Level::where('short_name', 'Year 8')->value('id')]);
    }

    private function startHomework(User $u, string $q): TutorSession
    {
        $this->actingAs($u)->post(route('tutor.read'), ['question' => $q])->assertRedirect(route('tutor.confirm'));
        $this->get(route('tutor.confirm'))->assertOk()->assertSee('Is this your question?');
        $read = session('pending')['read_as'];
        $this->post(route('tutor.store'), ['read_as' => $read])->assertRedirect();

        return TutorSession::latest('id')->firstOrFail();
    }

    public function test_home_and_confirm_show_how_the_question_was_read(): void
    {
        $u = $this->student();
        $this->actingAs($u)->get(route('tutor.home'))->assertOk()->assertSee('What would you like help with today?');
        $this->post(route('tutor.read'), ['question' => 'Solve: 2(3x-4)=10']);
        $this->get(route('tutor.confirm'))->assertSee('2(3x − 4) = 10');
    }

    public function test_unclear_input_is_never_guessed(): void
    {
        $this->actingAs($this->student())->post(route('tutor.read'), ['question' => '2(3x - 4 = 10'])
            ->assertRedirect()->assertSessionHas('read_error');
        $this->assertSame(0, TutorSession::count());
    }

    /** Scenario A: equivalent correct working is accepted; a wrong expansion is caught and explained. */
    public function test_scenario_a_accepts_valid_working_and_explains_the_distributive_mistake(): void
    {
        $u = $this->student();
        $s = $this->startHomework($u, '2(3x - 4) = 10');

        $this->post(route('tutor.lines', $s), ['working' => "6x - 4 = 10"]);
        $line = $s->lines()->first();
        $this->assertSame('wrong', $line->status);
        $this->assertSame(Misconceptions::DIST_FIRST_ONLY, $line->misconception);
        $this->assertSame(1, MisconceptionObservation::count());
        $check = TutorCheck::firstOrFail();
        $this->assertSame('ALG.DISTRIBUTIVE', $check->skill_code);
        $this->get(route('tutor.show', $s))->assertSee('multiplies EVERY term')->assertSee('Quick check');

        // A different but valid route: divide by 2 first.
        $this->post(route('tutor.lines', $s), ['working' => "3x - 4 = 5\n3x = 9\nx = 3"]);
        $s->refresh();
        $this->assertTrue($s->isSolved());
        $this->assertFalse($s->solvedUnaided(), 'a corrected mistake counts as help');
        $this->get(route('tutor.show', $s))->assertSee('Solved with help');
    }

    public function test_prerequisite_check_is_marked_and_recorded(): void
    {
        $u = $this->student();
        $s = $this->startHomework($u, '3(x + 2) = 15');
        $this->post(route('tutor.lines', $s), ['working' => '3x + 2 = 15']);
        $check = TutorCheck::firstOrFail();
        $this->post(route('tutor.check', $check), ['answer' => $check->answer])->assertRedirect();
        $this->assertTrue($check->fresh()->correct);
        $this->assertSame(1, SkillRecord::where('skill_code', 'ALG.DISTRIBUTIVE')->value('checks_passed'));
    }

    /** Scenario C: correct after three hints = helped, not independent; a fresh question is offered. */
    public function test_scenario_c_hints_mean_assisted_success(): void
    {
        $u = $this->student();
        $s = $this->startHomework($u, '3x + 7 = 31');
        foreach (range(1, 3) as $i) {
            $this->post(route('tutor.help', $s), ['kind' => 'hint']);
        }
        $this->assertStringContainsString('Next line: 3x = 24', HelpEvent::latest('id')->value('content'));
        $this->post(route('tutor.lines', $s), ['working' => "3x = 24\nx = 8"]);
        $s->refresh();
        $this->assertTrue($s->isSolved());
        $record = SkillRecord::where('user_id', $u->id)->where('skill_code', 'EQ.TWO_STEP')->first();
        $this->assertSame(1, $record->assisted_successes);
        $this->assertSame(0, $record->unaided_successes);
        $this->assertNotSame('independent', $record->state);

        $this->post(route('tutor.next', $s))->assertRedirect();
        $new = TutorSession::latest('id')->first();
        $this->assertSame('independent', $new->mode);
        $this->assertSame('EQ.TWO_STEP', $new->skill_code);
        $this->assertNotSame($s->question, $new->question);
    }

    public function test_two_unaided_solutions_make_a_skill_independent_and_later_remembered(): void
    {
        $u = $this->student();
        foreach (['3x + 7 = 31', '2x - 5 = 9'] as $q) {
            $s = $this->startHomework($u, $q);
            $x = $s->solutionValue();
            $this->post(route('tutor.lines', $s), ['working' => 'x = '.$x->toAscii()]);
        }
        $r = SkillRecord::where('user_id', $u->id)->where('skill_code', 'EQ.TWO_STEP')->first();
        $this->assertSame('independent', $r->state);

        $this->travel(4)->days();
        $s = $this->startHomework($u, '4x + 1 = 13');
        $this->post(route('tutor.lines', $s), ['working' => 'x = 3']);
        $this->assertSame('remembered', $r->fresh()->state);
    }

    public function test_independent_mode_offers_only_im_stuck(): void
    {
        $u = $this->student();
        $this->actingAs($u)->get(route('tutor.practice', ['skill' => 'EQ.BRACKETS', 'mode' => 'independent']));
        $s = TutorSession::firstOrFail();
        $this->get(route('tutor.show', $s))->assertSee("I'm stuck", false)->assertDontSee('Give me a hint');
        $this->post(route('tutor.help', $s), ['kind' => 'step']);
        $this->assertSame('guided', $s->fresh()->mode);
        $this->assertSame('stuck', HelpEvent::latest('id')->value('kind'));
    }

    public function test_example_and_explain_work_without_ai(): void
    {
        $u = $this->student();
        $s = $this->startHomework($u, '5x + 3 = 2x + 15');
        $this->post(route('tutor.help', $s), ['kind' => 'example']);
        $this->assertStringContainsString('worked step by step', HelpEvent::latest('id')->value('content'));
        $this->post(route('tutor.help', $s), ['kind' => 'explain']);
        $this->assertStringContainsString('balanced scales', HelpEvent::latest('id')->value('content'));
        $this->post(route('tutor.help', $s), ['kind' => 'ask', 'message' => 'why do you take away 2x?']);
        $this->assertSame('code', HelpEvent::latest('id')->value('source'));
        $this->assertSame(0, AiCall::count());
    }

    public function test_ai_explanation_is_used_but_never_gives_away_the_answer(): void
    {
        config(['services.anthropic.key' => 'test-key']);
        Http::fakeSequence()
            ->push(['content' => [['type' => 'text', 'text' => 'Think of balanced scales — take 7 off both sides.']], 'usage' => ['input_tokens' => 100, 'output_tokens' => 20]])
            ->push(['content' => [['type' => 'text', 'text' => 'Easy, x = 8.']], 'usage' => ['input_tokens' => 100, 'output_tokens' => 5]]);
        $u = $this->student();
        $s = $this->startHomework($u, '3x + 7 = 31');

        $this->post(route('tutor.help', $s), ['kind' => 'explain']);
        $this->assertSame('ai', HelpEvent::latest('id')->value('source'));
        $this->post(route('tutor.help', $s), ['kind' => 'ask', 'message' => 'just tell me']);
        $this->assertSame('fallback', HelpEvent::latest('id')->value('source'));
        $this->assertStringNotContainsString('x = 8', HelpEvent::latest('id')->value('content'));
        $this->assertSame(2, AiCall::count());
        $this->assertSame(200, (int) AiCall::sum('input_tokens'));
    }

    public function test_daily_ai_cap_falls_back_to_built_in_help(): void
    {
        config(['services.anthropic.key' => 'test-key', 'tutor.ai_daily_calls' => 1]);
        Http::fake(['*' => Http::response(['content' => [['type' => 'text', 'text' => 'Try thinking of scales.']]])]);
        $u = $this->student();
        $s = $this->startHomework($u, '3x + 7 = 31');
        $this->post(route('tutor.help', $s), ['kind' => 'explain']);
        $this->post(route('tutor.help', $s), ['kind' => 'explain']);
        $this->assertSame(1, AiCall::count());
        $this->assertSame('fallback', HelpEvent::latest('id')->value('source'));
    }

    /** Scenario H: nobody can open another student's tutor work. */
    public function test_scenario_h_other_users_cannot_see_a_session(): void
    {
        $owner = $this->student();
        $s = $this->startHomework($owner, '3x + 7 = 31');
        $other = $this->student();
        $parent = User::factory()->create(['role' => User::ROLE_PARENT]);
        foreach ([$other] as $intruder) {
            $this->actingAs($intruder)->get(route('tutor.show', $s))->assertForbidden();
            $this->post(route('tutor.lines', $s), ['working' => 'x = 8'])->assertForbidden();
        }
        $this->actingAs($parent)->get(route('tutor.show', $s))->assertForbidden();
        $this->assertFalse($s->fresh()->isSolved());
    }

    public function test_progress_page_shows_states_from_records(): void
    {
        $u = $this->student();
        $s = $this->startHomework($u, '3x + 7 = 31');
        $this->post(route('tutor.lines', $s), ['working' => 'x = 8']);
        $this->get(route('tutor.progress'))->assertOk()->assertSee('1 on your own');
    }

    public function test_typing_just_the_number_finishes(): void
    {
        $s = $this->startHomework($this->student(), 'x/4 - 2 = 1');
        $this->post(route('tutor.lines', $s), ['working' => '12']);
        $this->assertTrue($s->fresh()->isSolved());
    }
}

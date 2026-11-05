<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Models\StepCheck;
use App\Support\QuestionHelp;
use App\Support\StepCoach;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson): View|RedirectResponse
    {
        if ($lesson->isTest()) {
            return redirect()->route('tests.show', $lesson);
        }
        // Students only see published lessons; the admin can preview drafts.
        abort_unless($lesson->isPublished() || $request->user()->isAdmin(), 404);

        $lesson->load(['module.unit.subject', 'module.unit.level', 'questions']);

        $siblings = $lesson->module->lessons()->published()->get();
        $index = $siblings->search(fn ($l) => $l->id === $lesson->id);

        return view('student.lesson', [
            'lesson' => $lesson,
            'subject' => $lesson->module->unit->subject,
            'previous' => $index !== false && $index > 0 ? $siblings[$index - 1] : null,
            'nextLesson' => $index !== false ? $siblings->get($index + 1) : null,
            'position' => $index !== false ? $index + 1 : null,
            'count' => $siblings->count(),
            'completed' => $request->user()->completedLessons()->where('lessons.id', $lesson->id)->exists(),
        ]);
    }

    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->isPublished(), 404);

        $request->user()->completedLessons()->syncWithoutDetaching([
            $lesson->id => ['completed_at' => now()],
        ]);

        return back()->with('status', 'Lesson marked as done.');
    }

    public function answer(Request $request, Question $question): RedirectResponse
    {
        abort_unless($question->lesson->isPublished() || $request->user()->isAdmin(), 404);

        $data = $request->validate([
            'answer' => ['required', 'string', Rule::in(array_keys($question->options ?? []))],
            'help' => ['nullable', 'integer', 'exists:questions,id'],
        ], [
            'answer.required' => 'Pick an answer first.',
        ]);

        $correct = $question->isCorrect($data['answer']);
        $user = $request->user();

        QuestionAttempt::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'answer' => $data['answer'],
            'is_correct' => $correct,
            'help_used' => min(255, QuestionHelp::used($question)),
        ]);
        if ($correct) {
            QuestionHelp::forget($question);
        }

        // Wrong tries in a row since this question was last answered right.
        $wrongInARow = 0;
        foreach (QuestionAttempt::where('user_id', $user->id)->where('question_id', $question->id)->orderByDesc('id')->get() as $attempt) {
            if ($attempt->is_correct) {
                break;
            }
            $wrongInARow++;
        }

        $result = ['question_id' => $question->id, 'answer' => $data['answer'], 'correct' => $correct, 'reveal' => false];
        if (empty($data['help'])) {
            $result['suggest'] = $this->levelSuggestion($question, $user, $correct);
        }

        // Answered on a help page: stay there. Show the answer after a second wrong try.
        if (! empty($data['help'])) {
            $result['reveal'] = $wrongInARow >= 2;

            return redirect()
                ->to(route('questions.help', $data['help']).'#q'.$question->id)
                ->with('result', $result);
        }

        // Second wrong try in a row: go over the topic on the help page.
        if (! $correct && $wrongInARow >= 2) {
            return redirect()
                ->route('questions.help', $question)
                ->with('status', 'Not quite again — let’s go over this topic together.');
        }

        return redirect()
            ->to(route('lessons.show', $question->lesson_id).'#q'.$question->id)
            ->with('result', $result);
    }

    /** Penalty-free hint: reveal the next worked step (never the last one). */
    public function hint(Request $request, Question $question): RedirectResponse
    {
        abort_unless($question->lesson->isPublished() || $request->user()->isAdmin(), 404);
        QuestionHelp::addHint($question);

        return $this->backToQuestion($request, $question);
    }

    /** "I don't understand the question": what it asks, in simpler words — never the answer. */
    public function explainQuestion(Request $request, Question $question): RedirectResponse
    {
        abort_unless($question->lesson->isPublished() || $request->user()->isAdmin(), 404);
        QuestionHelp::explain($question);

        return $this->backToQuestion($request, $question);
    }

    private function backToQuestion(Request $request, Question $question): RedirectResponse
    {
        $help = $request->integer('help');

        return redirect()->to(($help ? route('questions.help', $help) : route('lessons.show', $question->lesson_id)).'#q'.$question->id);
    }

    /**
     * Adaptive difficulty: two right on your own at a level → suggest the next level up;
     * two different questions wrong at a level → suggest an easier one first.
     *
     * @return array{text:string,target:int}|null
     */
    private function levelSuggestion(Question $question, $user, bool $correct): ?array
    {
        $levels = array_keys(Question::LEVELS);
        $at = array_search($question->difficulty, $levels, true);
        if ($at === false) {
            return null;
        }
        $questions = $question->lesson->questions()->get();
        $latest = QuestionAttempt::where('user_id', $user->id)->whereIn('question_id', $questions->pluck('id'))
            ->orderByDesc('id')->get()->unique('question_id')->keyBy('question_id');
        $sameLevel = $questions->where('difficulty', $question->difficulty)->pluck('id');
        $doneRight = fn ($q) => ($latest[$q->id] ?? null)?->is_correct;

        if ($correct) {
            $unaidedRight = $latest->whereIn('question_id', $sameLevel)->filter(fn ($a) => $a->is_correct && $a->help_used === 0)->count();
            $up = $levels[$at + 1] ?? null;
            $target = $up ? $questions->where('difficulty', $up)->first(fn ($q) => ! $doneRight($q)) : null;
            if ($unaidedRight >= 2 && $target) {
                return ['text' => 'That\'s '.$unaidedRight.' '.Question::LEVELS[$question->difficulty].' questions right on your own. Ready for a '.Question::LEVELS[$up].' one?', 'target' => $target->id];
            }

            return null;
        }

        $wrongHere = $latest->whereIn('question_id', $sameLevel)->filter(fn ($a) => ! $a->is_correct)->count();
        $down = $at > 0 ? $levels[$at - 1] : null;
        $target = $down ? $questions->where('difficulty', $down)->first(fn ($q) => ! $doneRight($q)) : null;
        if ($wrongHere >= 2 && $target) {
            return ['text' => 'These '.Question::LEVELS[$question->difficulty].' ones are tricky. Try a '.Question::LEVELS[$down].' one first, then come back.', 'target' => $target->id];
        }

        return null;
    }

    /**
     * After a wrong answer: the student writes the steps they followed,
     * and gets told which step went wrong and how to fix it (never the final answer).
     */
    public function steps(Request $request, Question $question): RedirectResponse
    {
        abort_unless($question->lesson->isPublished() || $request->user()->isAdmin(), 404);

        $data = $request->validate([
            'answer' => ['required', 'string', Rule::in(array_keys($question->options ?? []))],
            'steps' => ['required', 'string', 'min:3', 'max:2000'],
            'help' => ['nullable', 'integer', 'exists:questions,id'],
        ], [
            'steps.required' => 'Write the steps you followed first.',
        ]);
        abort_if($question->isCorrect($data['answer']), 422);

        $check = StepCoach::check($question, $data['answer'], $data['steps']);
        StepCheck::create([
            'user_id' => $request->user()->id,
            'question_id' => $question->id,
            'answer' => strtoupper($data['answer']),
            'student_steps' => $data['steps'],
            'feedback' => $check['feedback'],
            'source' => $check['source'],
        ]);

        $flash = [
            'result' => ['question_id' => $question->id, 'answer' => strtoupper($data['answer']), 'correct' => false, 'reveal' => false],
            'stepcheck' => ['question_id' => $question->id, 'steps' => $data['steps'], 'feedback' => $check['feedback']],
        ];

        $to = ! empty($data['help'])
            ? route('questions.help', $data['help']).'#q'.$question->id
            : route('lessons.show', $question->lesson_id).'#q'.$question->id;

        return redirect()->to($to)->with($flash);
    }

    /**
     * Help page for a question answered wrong twice: the key parts of the lesson,
     * more questions on the same topic, then another try at the original question.
     */
    public function help(Request $request, Question $question): View
    {
        $lesson = $question->lesson()->with(['module.unit.subject', 'module.unit.level', 'questions'])->firstOrFail();
        abort_unless($lesson->isPublished() || $request->user()->isAdmin(), 404);

        // Same lesson first, then other lessons in the same module. Fixed order so the page doesn't reshuffle.
        $sameLesson = $lesson->questions->where('id', '!=', $question->id)->sortBy(fn ($q) => crc32($question->id.'-'.$q->id))->take(3);
        $sameModule = Question::whereIn('lesson_id', $lesson->module->lessons()->published()->where('lessons.id', '!=', $lesson->id)->pluck('lessons.id'))
            ->orderBy('id')->get()->sortBy(fn ($q) => crc32($question->id.'-'.$q->id))->take(2);

        return view('student.help', [
            'question' => $question,
            'lesson' => $lesson,
            'subject' => $lesson->module->unit->subject,
            'sections' => $lesson->helpSections(),
            'practice' => $sameLesson->concat($sameModule)->values(),
            'fixed' => QuestionAttempt::where('user_id', $request->user()->id)->where('question_id', $question->id)->latest('id')->value('is_correct'),
        ]);
    }
}

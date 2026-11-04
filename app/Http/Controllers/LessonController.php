<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson): View
    {
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
        ]);

        // Wrong tries in a row since this question was last answered right.
        $wrongInARow = 0;
        foreach (QuestionAttempt::where('user_id', $user->id)->where('question_id', $question->id)->orderByDesc('id')->get() as $attempt) {
            if ($attempt->is_correct) {
                break;
            }
            $wrongInARow++;
        }

        $result = ['question_id' => $question->id, 'answer' => $data['answer'], 'correct' => $correct, 'reveal' => false];

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

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
        ], [
            'answer.required' => 'Pick an answer first.',
        ]);

        $correct = $question->isCorrect($data['answer']);

        QuestionAttempt::create([
            'user_id' => $request->user()->id,
            'question_id' => $question->id,
            'answer' => $data['answer'],
            'is_correct' => $correct,
        ]);

        return redirect()
            ->to(route('lessons.show', $question->lesson_id).'#q'.$question->id)
            ->with('result', [
                'question_id' => $question->id,
                'answer' => $data['answer'],
                'correct' => $correct,
            ]);
    }
}

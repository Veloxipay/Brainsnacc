<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Support\Teach;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** "Teach me": worked example → your turn → more steps → mixed → short test. See App\Support\Teach. */
class TeachController extends Controller
{
    public function show(Request $request, Lesson $lesson): View
    {
        abort_if($lesson->isTest(), 404);
        abort_unless($lesson->isPublished() || $request->user()->isAdmin(), 404);
        $lesson->load(['module.unit.subject', 'module.unit.level', 'questions']);
        $plan = Teach::plan($lesson);
        abort_if(empty($plan['stages']), 404);

        return view('student.teach', [
            'lesson' => $lesson,
            'subject' => $lesson->module->unit->subject,
            'plan' => $plan,
            'homework' => Homework::where('student_id', $request->user()->id)->where('lesson_id', $lesson->id)->open()->exists(),
        ]);
    }

    /** "What answer did you get?" — check it, record it, and say what went wrong if it's wrong. */
    public function check(Request $request, Question $question): JsonResponse
    {
        abort_unless($question->lesson && ($question->lesson->isPublished() || $request->user()->isAdmin()), 404);
        $data = $request->validate([
            'said' => ['nullable', 'string', 'max:200'],
            'letter' => ['nullable', 'string', Rule::in(array_keys($question->options ?? []))],
            'mode' => ['required', Rule::in(['try', 'mix', 'test'])],
        ]);

        $letter = $data['letter'] ?? Teach::match($question, (string) ($data['said'] ?? ''));
        if ($letter === null) {
            return response()->json(['result' => 'unmatched']);
        }

        $correct = $question->isCorrect($letter);
        QuestionAttempt::create([
            'user_id' => $request->user()->id,
            'question_id' => $question->id,
            'answer' => $letter,
            'is_correct' => $correct,
            'help_used' => 0,
        ]);

        return response()->json([
            'result' => $correct ? 'correct' : 'wrong',
            'given' => (string) (($question->options ?? [])[$letter] ?? $letter),
            'answer' => Teach::answerText($question),
            'steps' => array_values($question->steps ?? []),
            'mistake' => $correct ? null : $question->mistakeFor($letter),
        ]);
    }
}

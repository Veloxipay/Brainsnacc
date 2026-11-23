<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionAttempt;
use App\Support\Teach;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Checks answers in step-by-step lessons (LessonController@show). See App\Support\Teach. */
class TeachController extends Controller
{
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

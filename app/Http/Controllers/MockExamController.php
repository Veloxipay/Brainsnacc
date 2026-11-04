<?php

namespace App\Http\Controllers;

use App\Models\MockExamAttempt;
use App\Support\MockExams;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sit a Brainsnacc practice exam in VCE conditions, then review and self-mark the written answers. */
class MockExamController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $exam = MockExams::find($slug) ?? abort(404);

        return view('student.mock-exam-start', [
            'exam' => $exam,
            'attempts' => MockExamAttempt::where('user_id', $request->user()->id)->where('exam', $slug)->latest('id')->get(),
        ]);
    }

    public function start(Request $request, string $slug): RedirectResponse
    {
        MockExams::find($slug) ?? abort(404);

        // Carry on an unfinished sitting instead of starting a second one.
        $attempt = MockExamAttempt::where('user_id', $request->user()->id)->where('exam', $slug)->whereNull('submitted_at')->latest('id')->first()
            ?? MockExamAttempt::create(['user_id' => $request->user()->id, 'exam' => $slug]);

        return redirect()->route('mock-exams.attempt', $attempt);
    }

    public function attempt(Request $request, MockExamAttempt $attempt): View
    {
        abort_unless($attempt->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        $exam = MockExams::find($attempt->exam) ?? abort(404);

        return view($attempt->submitted_at ? 'student.mock-exam-review' : 'student.mock-exam-sit', [
            'exam' => $exam,
            'attempt' => $attempt,
        ]);
    }

    public function submit(Request $request, MockExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        $exam = MockExams::find($attempt->exam) ?? abort(404);
        if ($attempt->submitted_at) {
            return redirect()->route('mock-exams.attempt', $attempt);
        }

        $a = (array) $request->input('a', []);
        $b = (array) $request->input('b', []);
        $answersA = [];
        $score = 0;
        foreach ($exam['section_a'] as $i => $q) {
            $choice = in_array($a[$i] ?? null, ['A', 'B', 'C', 'D'], true) ? $a[$i] : null;
            $answersA[$i] = $choice;
            $score += $choice === $q['answer'] ? 1 : 0;
        }
        $answersB = [];
        foreach ($exam['section_b'] as $q) {
            foreach ($q['parts'] as $part) {
                $key = MockExams::partKey($q, $part);
                $answersB[$key] = mb_substr((string) ($b[$key] ?? ''), 0, 5000);
            }
        }

        $attempt->update(['answers_a' => $answersA, 'answers_b' => $answersB, 'score_a' => $score, 'submitted_at' => now()]);

        return redirect()->route('mock-exams.attempt', $attempt)
            ->with('status', "Handed in! Section A is marked: {$score} / {$exam['marks_a']}. Now mark your Section B answers using the marking guide.");
    }

    public function mark(Request $request, MockExamAttempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        $exam = MockExams::find($attempt->exam) ?? abort(404);

        $given = (array) $request->input('marks', []);
        $marks = [];
        foreach ($exam['section_b'] as $q) {
            foreach ($q['parts'] as $part) {
                $key = MockExams::partKey($q, $part);
                $marks[$key] = max(0, min((int) $part['marks'], (int) ($given[$key] ?? 0)));
            }
        }
        $attempt->update(['marks_b' => $marks, 'score_b' => array_sum($marks), 'marked_at' => now()]);
        $total = $attempt->score_a + $attempt->score_b;

        return redirect()->route('mock-exams.attempt', $attempt)
            ->with('status', "Saved. Your total is {$total} / {$exam['total']} (".round($total / $exam['total'] * 100).'%).');
    }
}

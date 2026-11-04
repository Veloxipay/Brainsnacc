<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\QuestionAttempt;
use App\Models\Subject;
use App\Support\Progress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function home(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            $user->isAdmin() => redirect()->route('admin.lessons.index'),
            $user->isParent() => redirect()->route('family'),
            default => redirect()->route('dashboard'),
        };
    }

    public function student(Request $request): View
    {
        $student = $request->user();
        $level = $student->level ?? Level::orderBy('sort')->first();
        $done = Progress::completedIds($student);

        $subjects = Subject::orderBy('name')->get()->map(function (Subject $subject) use ($level, $done) {
            $lessons = Progress::lessonsFor($subject, $level);
            $finished = $lessons->whereIn('id', $done)->count();

            return [
                'subject' => $subject,
                'total' => $lessons->count(),
                'finished' => $finished,
                'percent' => $lessons->count() ? (int) round($finished / $lessons->count() * 100) : 0,
                'next' => $lessons->first(fn ($l) => ! $done->contains($l->id)),
            ];
        });

        $next = $subjects->pluck('next')->filter()->first();

        // Mistakes list: questions answered wrong in the last 7 days that haven't been tried again yet.
        // Once a question has been attempted a second time it leaves the list (right or wrong).
        $wrong = QuestionAttempt::where('user_id', $student->id)
            ->with('question.lesson')
            ->orderBy('id')
            ->get()
            ->groupBy('question_id')
            ->filter(fn ($tries) => $tries->count() === 1
                && ! $tries->first()->is_correct
                && $tries->first()->created_at >= now()->subWeek())
            ->map(fn ($tries) => $tries->first())
            ->filter(fn ($a) => $a->question && $a->question->lesson)
            ->values();
        $mistakes = $wrong->unique('question_id')->count();
        $mistakeLessons = $wrong->unique('question_id')
            ->groupBy(fn ($a) => $a->question->lesson_id)
            ->map(fn ($rows) => ['lesson' => $rows->first()->question->lesson, 'count' => $rows->count(), 'first' => $rows->sortBy(fn ($a) => $a->question->sort ?? $a->question_id)->first()->question_id])
            ->sortByDesc('count')
            ->values();

        return view('student.dashboard', [
            'student' => $student,
            'level' => $level,
            'subjects' => $subjects,
            'next' => $next,
            'weak' => Progress::weakLessons($student),
            'mistakes' => $mistakes,
            'mistakeLessons' => $mistakeLessons,
        ]);
    }
}

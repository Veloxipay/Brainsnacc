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
            $user->isSupervisor() => redirect()->route('family'),
            default => redirect()->route('dashboard'),
        };
    }

    public function student(Request $request): View
    {
        $student = $request->user();
        $level = $student->level ?? Level::orderBy('sort')->first();
        $done = Progress::completedIds($student);

        $subjects = Progress::subjectsAt($level)->map(function (Subject $subject) use ($level, $done) {
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

        // Mistakes list: questions whose most recent try was wrong. A question leaves once it is answered right.
        // After two wrong tries in a row the link goes to the help page (topic recap + extra questions).
        $mistakeRows = QuestionAttempt::where('user_id', $student->id)
            ->with('question.lesson')
            ->orderByDesc('id')
            ->get()
            ->groupBy('question_id')
            ->filter(fn ($tries) => ! $tries->first()->is_correct && $tries->first()->question && $tries->first()->question->lesson)
            ->map(function ($tries) {
                $wrongInARow = $tries->search(fn ($t) => $t->is_correct);

                return ['question' => $tries->first()->question, 'wrong' => $wrongInARow === false ? $tries->count() : $wrongInARow];
            })
            ->values();
        $mistakes = $mistakeRows->count();
        $mistakeLessons = $mistakeRows
            ->groupBy(fn ($row) => $row['question']->lesson_id)
            ->map(function ($rows) {
                $first = $rows->sortBy(fn ($r) => [$r['question']->sort, $r['question']->id])->first();

                return [
                    'lesson' => $first['question']->lesson,
                    'count' => $rows->count(),
                    'url' => $first['wrong'] >= 2
                        ? route('questions.help', $first['question'])
                        : route('lessons.show', $first['question']->lesson_id).'#q'.$first['question']->id,
                    'needsHelp' => $first['wrong'] >= 2,
                ];
            })
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

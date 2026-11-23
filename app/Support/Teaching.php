<?php

namespace App\Support;

use App\Models\HelpRequest;
use App\Models\Homework;
use App\Models\QuestionAttempt;
use App\Models\SessionNote;
use App\Models\TestAttempt;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** What a tutor needs to know about a student: since the last session, and what needs attention. */
class Teaching
{
    /** Can this person look after this student (admin, or linked as parent/teacher)? */
    public static function canSupervise(User $me, User $student): bool
    {
        return $student->isStudent() && ($me->isAdmin() || $me->students()->whereKey($student->id)->exists());
    }

    /**
     * Since the last session note (or the last 7 days if there are no notes yet):
     * lessons finished, questions answered and how many right, tests, homework, open questions.
     */
    public static function sinceLastSession(User $student): array
    {
        $last = SessionNote::where('student_id', $student->id)->orderByDesc('taught_on')->orderByDesc('id')->first();
        // Notes are dated by day; count everything after the end of that day.
        $since = $last
            ? $last->taught_on->copy()->endOfDay()
            : now()->subDays(7);

        $attempts = QuestionAttempt::where('user_id', $student->id)->where('created_at', '>', $since);
        $answered = (clone $attempts)->count();
        $right = (clone $attempts)->where('is_correct', true)->count();

        return [
            'lastNote' => $last,
            'since' => $since,
            'lessons' => $student->completedLessons()->wherePivot('completed_at', '>', $since)->orderByPivot('completed_at')->get(),
            'answered' => $answered,
            'accuracy' => $answered ? (int) round($right / $answered * 100) : null,
            'tests' => TestAttempt::where('user_id', $student->id)->where('created_at', '>', $since)->with('lesson')->latest('id')->get(),
            'homeworkDone' => Homework::where('student_id', $student->id)->where('completed_at', '>', $since)->count(),
            'homeworkOverdue' => Homework::where('student_id', $student->id)->open()->whereNotNull('due_on')
                ->whereDate('due_on', '<', now()->timezone('Australia/Melbourne')->toDateString())->count(),
            'openQuestions' => HelpRequest::where('student_id', $student->id)->open()->count(),
        ];
    }

    /**
     * Reasons a student needs attention, for a list of students in one go (a few queries, not a few per student).
     * Returns [student_id => ['score' => int, 'reasons' => [string, ...]]].
     */
    public static function attention(Collection $students, array $stats): array
    {
        $ids = $students->pluck('id')->all();
        if (! $ids) {
            return [];
        }
        $today = now()->timezone('Australia/Melbourne')->toDateString();
        $questions = HelpRequest::whereIn('student_id', $ids)->open()
            ->selectRaw('student_id, count(*) as n')->groupBy('student_id')->pluck('n', 'student_id');
        $overdue = Homework::whereIn('student_id', $ids)->open()->whereNotNull('due_on')->whereDate('due_on', '<', $today)
            ->selectRaw('student_id, count(*) as n')->groupBy('student_id')->pluck('n', 'student_id');

        $out = [];
        foreach ($students as $s) {
            $reasons = [];
            $score = 0;
            if ($n = (int) ($questions[$s->id] ?? 0)) {
                $reasons[] = $n.' '.($n === 1 ? 'question' : 'questions').' to go through';
                $score += 3 * $n;
            }
            if ($n = (int) ($overdue[$s->id] ?? 0)) {
                $reasons[] = $n.' overdue homework';
                $score += 2 * $n;
            }
            $last = $stats[$s->id]['lastActive'] ?? null;
            if (! $last) {
                $reasons[] = 'Not started yet';
                $score += 1;
            } elseif ($last->lt(now()->subDays(7))) {
                $reasons[] = 'Inactive since '.$last->copy()->timezone('Australia/Melbourne')->format('j M');
                $score += 2;
            }
            $acc = $stats[$s->id]['accuracy'] ?? null;
            if ($acc !== null && $acc < 60) {
                $reasons[] = 'Average score '.$acc.'%';
                $score += 2;
            }
            $out[$s->id] = ['score' => $score, 'reasons' => $reasons];
        }

        return $out;
    }
}

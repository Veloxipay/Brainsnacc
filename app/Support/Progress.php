<?php

namespace App\Support;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\QuestionAttempt;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Progress numbers shared by the student and parent pages. */
class Progress
{
    /** Subjects taught at this level (e.g. Year 7–9 only have Maths). All subjects if no level is set. */
    public static function subjectsAt(?Level $level): Collection
    {
        return Subject::orderBy('name')
            ->when($level, fn ($q) => $q->whereHas('units', fn ($u) => $u->where('level_id', $level->id)))
            ->get();
    }

    /** Published lessons for one subject at one level. */
    public static function lessonsFor(Subject $subject, ?Level $level): Collection
    {
        if (! $level) {
            return collect();
        }

        return Lesson::published()
            ->whereHas('module.unit', fn ($q) => $q->where('subject_id', $subject->id)->where('level_id', $level->id))
            ->with('module.unit')
            ->get()
            ->sortBy(fn (Lesson $l) => [$l->module->unit->sort, $l->module->sort, $l->sort])
            ->values();
    }

    /** Lesson ids this student has finished. */
    public static function completedIds(User $student): Collection
    {
        return $student->completedLessons()->pluck('lessons.id');
    }

    /** Share of questions answered correctly, as a whole percent (null if none answered). $unaidedOnly: only answers given with no hints. */
    public static function accuracy(User $student, ?int $lessonId = null, ?CarbonInterface $since = null, bool $unaidedOnly = false): ?int
    {
        $query = QuestionAttempt::where('user_id', $student->id);
        if ($unaidedOnly) {
            $query->where('help_used', 0);
        }

        if ($lessonId) {
            $query->whereHas('question', fn ($q) => $q->where('lesson_id', $lessonId));
        }
        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            return null;
        }

        return (int) round((clone $query)->where('is_correct', true)->count() / $total * 100);
    }

    /** Lessons where the student scores under 60% (after at least 3 answers). */
    public static function weakLessons(User $student, int $limit = 3): Collection
    {
        return QuestionAttempt::where('user_id', $student->id)
            ->with('question.lesson.module.unit.subject')
            ->get()
            ->groupBy(fn ($a) => $a->question->lesson_id)
            ->filter(fn ($attempts) => $attempts->count() >= 3)
            ->map(fn ($attempts) => [
                'lesson' => $attempts->first()->question->lesson,
                'score' => (int) round($attempts->where('is_correct', true)->count() / $attempts->count() * 100),
            ])
            ->filter(fn ($row) => $row['score'] < 60)
            ->sortBy('score')
            ->take($limit)
            ->values();
    }
}

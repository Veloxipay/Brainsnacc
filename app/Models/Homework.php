<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A lesson or chapter test a tutor or parent set for one student. */
class Homework extends Model
{
    protected $table = 'homework';

    protected $fillable = ['student_id', 'set_by', 'lesson_id', 'due_on', 'note', 'completed_at'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'completed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    /** Past its due date and not handed in. Due dates are whole days in Melbourne time. */
    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_on && $this->due_on->toDateString() < now()->timezone('Australia/Melbourne')->toDateString();
    }

    /** The student finished this lesson (or sat this test): hand in any open homework for it. */
    public static function handIn(User $student, Lesson $lesson): int
    {
        return static::where('student_id', $student->id)->where('lesson_id', $lesson->id)->open()
            ->update(['completed_at' => now()]);
    }
}

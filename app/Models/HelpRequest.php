<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** "Ask my tutor": a question (or a Maths tutor problem) the student wants to go through in their next session. */
class HelpRequest extends Model
{
    protected $fillable = ['student_id', 'question_id', 'tutor_session_id', 'message', 'resolved_at', 'resolved_by'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function tutorSession(): BelongsTo
    {
        return $this->belongsTo(TutorSession::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /** Can this student ask (someone looks after them)? Worked out once per page load. */
    public static function canAsk(?User $user): bool
    {
        if (! $user || ! $user->isStudent()) {
            return false;
        }

        return self::remember("can.{$user->id}", fn () => $user->supervisors()->exists());
    }

    /** Questions this student has asked about and their tutor hasn't gone through yet. */
    public static function openQuestionIds(User $user): array
    {
        return self::remember("open.{$user->id}", fn () => static::where('student_id', $user->id)->open()
            ->whereNotNull('question_id')->pluck('question_id')->all());
    }

    private static function remember(string $key, \Closure $fn): mixed
    {
        $bag = request()->attributes;
        $key = 'help_requests.'.$key;
        if (! $bag->has($key)) {
            $bag->set($key, $fn());
        }

        return $bag->get($key);
    }
}

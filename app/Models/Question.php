<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = ['lesson_id', 'type', 'difficulty', 'topic', 'prompt', 'options', 'answer', 'explanation', 'steps', 'mistakes', 'marks', 'sort'];

    /** Question levels, easiest first. */
    public const LEVELS = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'challenge' => 'Challenge'];

    protected function casts(): array
    {
        return ['options' => 'array', 'steps' => 'array', 'mistakes' => 'array'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }

    public function levelLabel(): ?string
    {
        return self::LEVELS[$this->difficulty] ?? null;
    }

    /** The note for a wrong option: which step usually goes wrong to give this answer. */
    public function mistakeFor(string $answer): ?string
    {
        return $this->mistakes[strtoupper(trim($answer))] ?? null;
    }

    public function isCorrect(string $answer): bool
    {
        return strtoupper(trim($answer)) === strtoupper(trim($this->answer));
    }
}

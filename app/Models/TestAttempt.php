<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One sitting of a chapter test: done alone, no hints, marked at the end. */
class TestAttempt extends Model
{
    protected $fillable = ['user_id', 'lesson_id', 'score', 'total', 'answers', 'by_topic', 'seconds'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'by_topic' => 'array'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function percent(): int
    {
        return $this->total ? (int) round($this->score / $this->total * 100) : 0;
    }
}

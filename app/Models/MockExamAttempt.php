<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockExamAttempt extends Model
{
    protected $fillable = ['user_id', 'exam', 'answers_a', 'answers_b', 'marks_b', 'score_a', 'score_b', 'submitted_at', 'marked_at'];

    protected function casts(): array
    {
        return ['answers_a' => 'array', 'answers_b' => 'array', 'marks_b' => 'array', 'submitted_at' => 'datetime', 'marked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

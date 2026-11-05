<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The steps a student wrote after a wrong answer, and the feedback on where they went wrong. */
class StepCheck extends Model
{
    protected $fillable = ['user_id', 'question_id', 'answer', 'student_steps', 'feedback', 'source'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

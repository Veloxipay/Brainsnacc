<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A quick check on an earlier skill, asked after a mistake that points to it. */
class TutorCheck extends Model
{
    protected $fillable = ['user_id', 'tutor_session_id', 'skill_code', 'reason', 'kind', 'prompt', 'answer', 'given', 'correct'];

    protected function casts(): array
    {
        return ['correct' => 'boolean'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TutorSession::class, 'tutor_session_id');
    }

    public function isOpen(): bool
    {
        return $this->correct === null;
    }
}

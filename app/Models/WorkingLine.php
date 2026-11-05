<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingLine extends Model
{
    protected $fillable = ['tutor_session_id', 'position', 'typed', 'read_as', 'status', 'is_final', 'misconception', 'confidence', 'feedback'];

    protected function casts(): array
    {
        return ['is_final' => 'boolean'];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TutorSession::class, 'tutor_session_id');
    }
}

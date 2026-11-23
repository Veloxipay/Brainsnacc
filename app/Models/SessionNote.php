<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A tutor's short note after a teaching session. Everyone who looks after the student can read it. */
class SessionNote extends Model
{
    protected $fillable = ['student_id', 'author_id', 'taught_on', 'body'];

    protected function casts(): array
    {
        return ['taught_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

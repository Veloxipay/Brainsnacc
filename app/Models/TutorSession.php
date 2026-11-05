<?php

namespace App\Models;

use App\Tutor\Math\Equation;
use App\Tutor\Math\Rational;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One student working one equation, guided or on their own. */
class TutorSession extends Model
{
    protected $fillable = ['user_id', 'skill_code', 'source', 'mode', 'question', 'solution', 'status', 'help_used', 'hint_level', 'parent_id', 'solved_at'];

    protected function casts(): array
    {
        return ['solved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(WorkingLine::class)->orderBy('position');
    }

    public function helpEvents(): HasMany
    {
        return $this->hasMany(HelpEvent::class)->orderBy('id');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(TutorCheck::class)->orderBy('id');
    }

    public function equation(): Equation
    {
        return Equation::parse($this->question);
    }

    public function solutionValue(): Rational
    {
        return Rational::fromAscii($this->solution);
    }

    public function isSolved(): bool
    {
        return $this->status === 'solved';
    }

    public function isIndependent(): bool
    {
        return $this->mode === 'independent';
    }

    /** Solved with no hints, steps, examples or corrections. */
    public function solvedUnaided(): bool
    {
        return $this->isSolved() && $this->help_used === 0;
    }
}

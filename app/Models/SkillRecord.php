<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** What a student has shown on one skill, keeping helped and unaided results apart. */
class SkillRecord extends Model
{
    public const STATES = [
        'not_assessed' => 'Not yet assessed',
        'needs_foundations' => 'Needs foundations',
        'developing' => 'Developing',
        'independent' => 'Solved on your own',
        'remembered' => 'Remembered',
    ];

    protected $fillable = ['user_id', 'skill_code', 'state', 'unaided_successes', 'assisted_successes', 'checks_passed', 'checks_failed', 'first_independent_at', 'last_unaided_at', 'last_assisted_at'];

    protected function casts(): array
    {
        return ['first_independent_at' => 'datetime', 'last_unaided_at' => 'datetime', 'last_assisted_at' => 'datetime'];
    }

    public function label(): string
    {
        return self::STATES[$this->state] ?? $this->state;
    }
}

<?php

namespace App\Tutor;

use App\Models\MisconceptionObservation;
use App\Models\SkillRecord;
use App\Models\TutorCheck;
use App\Models\User;

/**
 * The honest skill record. Helped successes are kept but never count towards "Solved on your own".
 * The rule's numbers live in config/tutor.php.
 *
 *   Solved on your own = N correct answers with no help at all (config: independent_successes)
 *   Remembered         = one more unaided correct answer at least D days after becoming independent (retention_days)
 *   Needs foundations  = the same mistake seen R times on this skill AND the latest quick check on the earlier skill missed
 *   Developing         = anything else after a first attempt
 */
final class Mastery
{
    public static function record(User $user, string $skill): SkillRecord
    {
        return SkillRecord::firstOrCreate(['user_id' => $user->id, 'skill_code' => $skill]);
    }

    public static function solved(User $user, string $skill, bool $unaided): SkillRecord
    {
        $r = self::record($user, $skill);
        if ($unaided) {
            $r->unaided_successes++;
            $r->last_unaided_at = now();
        } else {
            $r->assisted_successes++;
            $r->last_assisted_at = now();
        }

        return self::refresh($user, $r);
    }

    public static function checked(User $user, string $skill, bool $correct): SkillRecord
    {
        $r = self::record($user, $skill);
        $correct ? $r->checks_passed++ : $r->checks_failed++;
        if ($correct) {
            $r->unaided_successes++;
            $r->last_unaided_at = now();
        }

        return self::refresh($user, $r);
    }

    public static function attempted(User $user, string $skill): SkillRecord
    {
        return self::refresh($user, self::record($user, $skill));
    }

    public static function refresh(User $user, SkillRecord $r): SkillRecord
    {
        $needed = config('tutor.independent_successes');
        if ($r->unaided_successes >= $needed && ! $r->first_independent_at) {
            $r->first_independent_at = $r->last_unaided_at ?? now();
        }

        $state = 'developing';
        if ($r->unaided_successes >= $needed) {
            $state = 'independent';
            $days = config('tutor.retention_days');
            if ($r->first_independent_at && $r->last_unaided_at && $r->last_unaided_at->gte($r->first_independent_at->copy()->addDays($days))) {
                $state = 'remembered';
            }
        } elseif (self::needsFoundations($user, $r->skill_code)) {
            $state = 'needs_foundations';
        }
        $r->state = $state;
        $r->save();

        return $r;
    }

    /** The same mistake keeps happening on this skill, and the earlier skill behind it was missed on a quick check. */
    public static function needsFoundations(User $user, string $skill): bool
    {
        $repeated = MisconceptionObservation::where('user_id', $user->id)->where('skill_code', $skill)
            ->where('code', '!=', \App\Tutor\Math\Misconceptions::UNKNOWN)
            ->selectRaw('code, count(*) as n')->groupBy('code')
            ->havingRaw('count(*) >= ?', [config('tutor.foundation_repeats')])->exists();
        if (! $repeated) {
            return false;
        }
        $lastCheck = TutorCheck::where('user_id', $user->id)->whereNotNull('correct')
            ->whereIn('skill_code', Skills::get($skill)['prereqs'] ?? [])
            ->latest('id')->first();
        $lastCheck ??= TutorCheck::where('user_id', $user->id)->whereNotNull('correct')
            ->whereHas('session', fn ($q) => $q->where('skill_code', $skill))->latest('id')->first();

        return $lastCheck !== null && ! $lastCheck->correct;
    }

    /** The earlier skill to work on when a student needs foundations: the first prerequisite they missed. */
    public static function weakPrerequisite(User $user, string $skill): ?string
    {
        $check = TutorCheck::where('user_id', $user->id)->where('correct', false)
            ->whereHas('session', fn ($q) => $q->where('skill_code', $skill))->latest('id')->first();

        return $check?->skill_code;
    }
}

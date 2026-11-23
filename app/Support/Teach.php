<?php

namespace App\Support;

use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * "Teach me" — the way a parent teaches at the kitchen table:
 *   1. Show a worked example, writing it one step at a time while the student writes along.
 *   2. "What answer did you get?" on a similar question (typed or said out loud).
 *   3. Add more steps (easy → medium → hard) and repeat.
 *   4. Three or four questions that mix all the steps.
 *   5. A short test, no help.
 * Built from the lesson's own questions: easy / medium / hard become the stages, challenge goes into the mix and the test.
 */
class Teach
{
    public const STAGE_NAMES = ['easy' => 'The basic steps', 'medium' => 'Adding more steps', 'hard' => 'All the steps together'];

    /** @return array{stages: array<int, array{key: string, title: string, example: Question, tries: Collection}>, mixed: Collection, test: Collection} */
    public static function plan(Lesson $lesson): array
    {
        $questions = $lesson->questions->values();
        $byLevel = $questions->groupBy(fn (Question $q) => $q->difficulty ?: 'none');
        $challenge = $byLevel->get('challenge', collect())->values();

        // Stages: easy, medium, hard if the questions have levels; otherwise split them into up to three groups in order.
        $groups = [];
        foreach (['easy', 'medium', 'hard'] as $level) {
            if ($byLevel->has($level)) {
                $groups[$level] = $byLevel[$level]->values();
            }
        }
        if (! $groups) {
            $plain = $byLevel->get('none', collect())->values();
            $size = $plain->count() >= 6 ? (int) ceil($plain->count() / 3) : max(1, $plain->count());
            foreach ($plain->chunk($size)->values() as $i => $chunk) {
                $groups['part'.($i + 1)] = $chunk->values();
            }
        }

        $stages = [];
        $reserve = collect();
        $n = 0;
        foreach ($groups as $key => $group) {
            if ($group->isEmpty()) {
                continue;
            }
            $n++;
            $stages[] = [
                'key' => $key,
                'title' => self::STAGE_NAMES[$key] ?? ($n === 1 ? 'The basic steps' : 'Adding more steps'),
                'example' => $group[0],
                'tries' => $group->slice(1, 1)->values(),
            ];
            $reserve = $reserve->concat($group->slice(2));
        }

        // Mix: what's left from each stage plus the first challenge question, in a fixed shuffle per lesson.
        $mixed = $reserve->concat($challenge->take(1))->shuffle($lesson->id)->values();
        $test = $mixed->slice(4)->concat($challenge->slice(1))->values();
        $mixed = $mixed->take(4)->values();
        if ($test->count() < 3) {
            // Not enough new questions for a test: re-ask the ones they tried, without help this time.
            $tried = collect($stages)->flatMap(fn ($s) => $s['tries'])->concat($mixed);
            $test = $test->concat($tried->reject(fn ($q) => $test->contains('id', $q->id)))->unique('id')->take(5)->values();
        }

        return ['stages' => $stages, 'mixed' => $mixed, 'test' => $test];
    }

    /** The right answer, as words, e.g. "x = 4" (not just "B"). */
    public static function answerText(Question $q): string
    {
        return (string) (($q->options ?? [])[$q->answer] ?? $q->answer);
    }

    /**
     * Match what the student typed or said ("I got minus 4", "x = -4", "B") to one of the options.
     * Returns the option letter, or null if it matches none of them.
     */
    public static function match(Question $q, string $said): ?string
    {
        $options = $q->options ?? [];
        $raw = trim($said);
        if ($raw === '') {
            return null;
        }
        // A single letter that is one of the options: they picked it.
        if (strlen($raw) === 1 && array_key_exists(strtoupper($raw), $options) && ! self::anyOptionIs($options, $raw)) {
            return strtoupper($raw);
        }

        $mine = self::normalise($raw);
        $mineNum = self::number($mine);
        $hits = [];
        foreach ($options as $letter => $text) {
            $theirs = self::normalise((string) $text);
            $theirNum = self::number($theirs);
            if ($mine === $theirs
                || ($mineNum !== null && $theirNum !== null && abs($mineNum - $theirNum) < 1e-9)
                || (strlen($mine) > 2 && preg_replace('/[^a-z0-9]/', '', $mine) === preg_replace('/[^a-z0-9]/', '', $theirs))) {
                $hits[] = $letter;
            }
        }

        return count($hits) === 1 ? (string) $hits[0] : null;
    }

    private static function anyOptionIs(array $options, string $value): bool
    {
        foreach ($options as $text) {
            if (strtolower(trim((string) $text)) === strtolower($value)) {
                return true;
            }
        }

        return false;
    }

    /** Lower case, maths symbols and spoken words made plain, "x =" and filler words removed, no spaces. */
    public static function normalise(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['−', '–', '—', '×', '÷', '·', '’', '½', '¼', '¾'], ['-', '-', '-', 'x', '/', '.', "'", '1/2', '1/4', '3/4'], $s);
        $s = preg_replace('/\b(i got|i think|the answer is|answer is|answer|it is|it\'s|its|equals|is)\b/u', ' ', $s);
        $s = preg_replace('/\b(the|a|an)\s+(?!=)/u', '', $s);
        $s = self::wordsToDigits($s);
        $s = preg_replace('/\b(minus|negative)\s*/u', '-', $s);
        $s = preg_replace('/\s*(degrees?)\b/u', '°', $s);
        $s = preg_replace('/\s+point\s+/u', '.', $s);
        $s = preg_replace('/\s+(over|divided by|out of)\s+/u', '/', $s);
        $s = preg_replace('/\s+(times|multiplied by)\s+/u', 'x', $s);
        $s = preg_replace('/^\s*[a-z]\s*=\s*/u', '', $s); // "x = 4" → "4"
        $s = preg_replace('/(?<=\d),(?=\d{3}\b)/u', '', $s); // 1,000 → 1000
        $s = preg_replace('/\s+/u', '', $s);

        return rtrim($s, '.');
    }

    /** Spoken number words to digits: "twenty four" → 24, "three" → 3. Leaves everything else alone. */
    private static function wordsToDigits(string $s): string
    {
        $ones = ['zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9,
            'ten' => 10, 'eleven' => 11, 'twelve' => 12, 'thirteen' => 13, 'fourteen' => 14, 'fifteen' => 15, 'sixteen' => 16,
            'seventeen' => 17, 'eighteen' => 18, 'nineteen' => 19];
        $tens = ['twenty' => 20, 'thirty' => 30, 'forty' => 40, 'fifty' => 50, 'sixty' => 60, 'seventy' => 70, 'eighty' => 80, 'ninety' => 90];
        $s = preg_replace_callback('/\b('.implode('|', array_keys($tens)).')(?:[\s-]+('.implode('|', array_slice(array_keys($ones), 1, 9)).'))?\b/u',
            fn ($m) => (string) ($tens[$m[1]] + (isset($m[2]) && $m[2] !== '' ? $ones[$m[2]] : 0)), $s);

        return preg_replace_callback('/\b('.implode('|', array_keys($ones)).')\b/u', fn ($m) => (string) $ones[$m[1]], $s);
    }

    /** A plain number, fraction or decimal (with an optional $ or unit), as a float; null if it isn't one. */
    public static function number(string $s): ?float
    {
        $s = ltrim($s, '$');
        if (! preg_match('/^(-?\d+(?:\.\d+)?)(?:\/(-?\d+(?:\.\d+)?))?(?:[a-z°%²³]{0,10})$/u', $s, $m)) {
            return null;
        }
        $value = (float) $m[1];
        if (isset($m[2]) && $m[2] !== '') {
            if ((float) $m[2] == 0.0) {
                return null;
            }
            $value /= (float) $m[2];
        }

        return $value;
    }
}

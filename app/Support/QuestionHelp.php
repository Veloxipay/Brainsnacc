<?php

namespace App\Support;

use App\Models\Question;
use App\Support\Ai\AiModel;
use App\Tutor\Tutor;

/**
 * Help on a lesson's practice question, without penalty: step-by-step hints (never the last step,
 * which would give the answer away) and "I don't understand the question". Help used is remembered
 * until the student answers, so the answer is recorded as "with help".
 */
class QuestionHelp
{
    private static function key(Question $q): string
    {
        return 'qhelp.'.$q->id;
    }

    /** @return array{hints:int, explain:?string} */
    public static function state(Question $q): array
    {
        return session(self::key($q), ['hints' => 0, 'explain' => null]);
    }

    public static function used(Question $q): int
    {
        $s = self::state($q);

        return $s['hints'] + ($s['explain'] ? 1 : 0);
    }

    public static function forget(Question $q): void
    {
        session()->forget(self::key($q));
    }

    /** How many steps may be shown as hints: all but the last (that one is the answer). */
    public static function maxHints(Question $q): int
    {
        return max(0, count($q->steps ?? []) - 1);
    }

    public static function addHint(Question $q): void
    {
        $s = self::state($q);
        $s['hints'] = min(self::maxHints($q), $s['hints'] + 1);
        session([self::key($q) => $s]);
    }

    public static function explain(Question $q): string
    {
        $s = self::state($q);
        if ($s['explain']) {
            return $s['explain'];
        }
        $q->loadMissing('lesson.module.unit.level', 'lesson.module.unit.subject');
        $lesson = $q->lesson;
        $options = collect($q->options)->map(fn ($t, $l) => "{$l}. {$t}")->implode("\n");
        $system = <<<'TXT'
You help an Australian school student who doesn't understand what a multiple-choice question is asking.
Explain, in simple words, what the question is asking and which idea from the lesson it uses. Rephrase it more simply, and point to what to work out first.
Do NOT say which option is correct, do NOT work out the final answer, and do NOT eliminate options.
Under 90 words. Plain text, no markdown. Australian spelling. Friendly, not gushing.
TXT;
        $prompt = "Level: {$lesson->module->unit->level->name} {$lesson->module->unit->subject->name}\nLesson: {$lesson->title}\n\nQuestion: {$q->prompt}\n{$options}\n(Correct option, never reveal: {$q->answer})";
        $text = app(AiModel::class)->complete('question-explain', $system, [['type' => 'text', 'text' => $prompt]], auth()->user(), 250);

        if ($text !== null && preg_match('/\b(answer|option|choice)\s+(is\s+)?\(?'.preg_quote($q->answer, '/').'\)?\b/i', $text)) {
            $text = null; // gave the answer away
        }
        $text ??= self::fallback($q);
        $s['explain'] = $text;
        session([self::key($q) => $s]);

        return $text;
    }

    private static function fallback(Question $q): string
    {
        $first = $q->steps[0] ?? null;

        return 'This question is from the lesson "'.$q->lesson->title.'". Read it once more and underline what it asks you to find.'
            .($first ? ' A good first step: '.$first : ' Look back at the worked examples in the lesson notes for one like it.');
    }

    /**
     * If the question contains a linear equation the tutor can work through line by line, return it (as read by the tutor).
     */
    public static function tutorEquation(Question $q): ?string
    {
        if (($q->lesson->module->unit->subject->slug ?? null) !== 'maths') {
            return null;
        }
        if (! preg_match_all('/[0-9a-z()+\-−×÷\/. ]*=[0-9a-z()+\-−×÷\/. ]*/iu', $q->prompt, $m)) {
            return null;
        }
        foreach ($m[0] as $candidate) {
            $candidate = trim($candidate, " .\t");
            // Strip leading words: keep from the first maths character.
            $candidate = preg_replace('/^(?:[a-z]{2,}\s+)+/i', '', $candidate);
            if (! preg_match('/[a-z]/i', $candidate)) {
                continue;
            }
            $read = Tutor::read($candidate);
            if ($read['ok']) {
                return $read['read_as'];
            }
        }

        return null;
    }
}

<?php

namespace App\Tutor\Math;

/**
 * The mistakes the tutor can recognise in linear-equation working, in plain words for students.
 * Each one points to the earlier skill behind it, so the tutor can check that skill.
 */
final class Misconceptions
{
    public const DIST_FIRST_ONLY = Evaluator::BUG_DIST_FIRST_ONLY;

    public const DIST_SIGN = Evaluator::BUG_DIST_SIGN;

    public const DIV_ONE_TERM = Evaluator::BUG_DIV_ONE_TERM;

    public const MOVE_NO_SIGN = 'MOVE_NO_SIGN';

    public const WRONG_INVERSE = 'WRONG_INVERSE';

    public const COMBINE_UNLIKE = 'COMBINE_UNLIKE';

    public const SIGN_SLIP = 'SIGN_SLIP';

    public const ARITHMETIC = 'ARITHMETIC';

    public const UNKNOWN = 'UNKNOWN';

    /** Categories from the product brief. */
    public const CATEGORIES = [
        'concept' => 'Concept misunderstanding',
        'procedure' => 'Incorrect procedure',
        'arithmetic' => 'Arithmetic error',
        'algebra' => 'Algebraic manipulation error',
        'prerequisite' => 'Missing prerequisite knowledge',
        'unknown' => 'Insufficient evidence to classify',
    ];

    public static function all(): array
    {
        return [
            self::DIST_FIRST_ONLY => [
                'name' => 'Only the first term in the bracket was multiplied',
                'category' => 'algebra',
                'skill' => 'ALG.DISTRIBUTIVE',
                'explain' => 'When you expanded {part}, it looks like only the first term inside was multiplied. The number outside multiplies EVERY term inside: 3(x + 2) = 3x + 6, not 3x + 2.',
            ],
            self::DIST_SIGN => [
                'name' => 'Sign error when expanding a bracket',
                'category' => 'algebra',
                'skill' => 'INT.MULDIV',
                'explain' => 'Check the signs when you expanded {part}. Negative × negative = positive, and positive × negative = negative. So −2(x − 3) = −2x + 6.',
            ],
            self::DIV_ONE_TERM => [
                'name' => 'Only one term was divided',
                'category' => 'algebra',
                'skill' => 'ALG.DISTRIBUTIVE',
                'explain' => 'When you divide, EVERY term must be divided — not just the first one. (6x + 9) ÷ 3 = 2x + 3, not 2x + 9.',
            ],
            self::MOVE_NO_SIGN => [
                'name' => 'A term moved across the = sign without the opposite operation',
                'category' => 'procedure',
                'skill' => 'EQ.ONE_STEP',
                'explain' => 'It looks like {term} moved to the other side but kept its sign. Think of it as doing the same thing to both sides: to remove +7, subtract 7 from both sides; to remove −7, add 7.',
            ],
            self::WRONG_INVERSE => [
                'name' => 'The wrong operation was used to undo a step',
                'category' => 'procedure',
                'skill' => 'EQ.ONE_STEP',
                'explain' => 'To undo something, use the opposite operation on both sides. If x is multiplied by 3, divide both sides by 3. If x is divided by 3, multiply both sides by 3. If 5 is added, subtract 5.',
            ],
            self::COMBINE_UNLIKE => [
                'name' => 'x terms and numbers were added together',
                'category' => 'concept',
                'skill' => 'ALG.LIKE_TERMS',
                'explain' => 'An x term and a plain number are unlike terms, so they can\'t be added together. 3x + 2 stays as 3x + 2 — it is not 5x.',
            ],
            self::SIGN_SLIP => [
                'name' => 'The answer has the wrong sign',
                'category' => 'arithmetic',
                'skill' => 'INT.ADDSUB',
                'explain' => 'Your answer is the right size but has the wrong sign. Go back through each line and check every + and −.',
            ],
            self::ARITHMETIC => [
                'name' => 'A number was worked out wrongly',
                'category' => 'arithmetic',
                'skill' => 'INT.ADDSUB',
                'explain' => 'Your method looks right, but check the {number} on the {side} side — that number doesn\'t follow from the line before.',
            ],
            self::UNKNOWN => [
                'name' => 'Not enough evidence to say what went wrong',
                'category' => 'unknown',
                'skill' => null,
                'explain' => 'This line doesn\'t follow from the one before, but I can\'t tell exactly why. Check what you did, and make sure you did the same thing to BOTH sides.',
            ],
        ];
    }

    public static function get(string $code): array
    {
        return self::all()[$code] ?? self::all()[self::UNKNOWN];
    }

    /** The student-facing explanation with the details filled in. */
    public static function explain(string $code, array $detail = []): string
    {
        $text = self::get($code)['explain'];
        foreach (['part', 'term', 'number', 'side', 'coefficient'] as $k) {
            $text = str_replace('{'.$k.'}', (string) ($detail[$k] ?? ($k === 'part' ? 'the bracket' : ($k === 'term' ? 'a term' : 'a number'))), $text);
        }

        return $text;
    }
}

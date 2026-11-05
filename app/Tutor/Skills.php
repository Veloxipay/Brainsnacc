<?php

namespace App\Tutor;

use App\Tutor\Math\Equation;

/**
 * The skills the tutor covers, with the earlier skills each one needs.
 * Victorian Curriculum 2.0 codes are left blank ("mapping pending") until checked against the VCAA site.
 */
final class Skills
{
    public static function all(): array
    {
        return [
            'INT.ADDSUB' => ['name' => 'Adding and subtracting integers', 'year' => 7, 'strand' => 'Number', 'kind' => 'number', 'prereqs' => [],
                'objective' => 'Add and subtract positive and negative whole numbers.'],
            'INT.MULDIV' => ['name' => 'Multiplying and dividing integers', 'year' => 7, 'strand' => 'Number', 'kind' => 'number', 'prereqs' => [],
                'objective' => 'Multiply and divide positive and negative whole numbers, getting the sign right.'],
            'ALG.LIKE_TERMS' => ['name' => 'Collecting like terms', 'year' => 7, 'strand' => 'Algebra', 'kind' => 'expression', 'prereqs' => ['INT.ADDSUB'],
                'objective' => 'Simplify an expression by adding the x terms together and the numbers together.'],
            'ALG.DISTRIBUTIVE' => ['name' => 'Expanding brackets', 'year' => 8, 'strand' => 'Algebra', 'kind' => 'expression', 'prereqs' => ['INT.MULDIV'],
                'objective' => 'Multiply every term inside a bracket by the number outside.'],
            'EQ.ONE_STEP' => ['name' => 'One-step equations', 'year' => 7, 'strand' => 'Algebra', 'kind' => 'equation', 'prereqs' => ['INT.ADDSUB', 'INT.MULDIV'],
                'objective' => 'Solve an equation by doing one opposite operation to both sides.'],
            'EQ.TWO_STEP' => ['name' => 'Two-step equations', 'year' => 7, 'strand' => 'Algebra', 'kind' => 'equation', 'prereqs' => ['EQ.ONE_STEP'],
                'objective' => 'Solve equations like 3x + 7 = 31 by undoing the operations in reverse order.'],
            'EQ.BRACKETS' => ['name' => 'Equations with brackets', 'year' => 8, 'strand' => 'Algebra', 'kind' => 'equation', 'prereqs' => ['EQ.TWO_STEP', 'ALG.DISTRIBUTIVE'],
                'objective' => 'Solve equations like 2(3x − 4) = 10 by expanding first.'],
            'EQ.BOTH_SIDES' => ['name' => 'Unknowns on both sides', 'year' => 8, 'strand' => 'Algebra', 'kind' => 'equation', 'prereqs' => ['EQ.TWO_STEP', 'ALG.LIKE_TERMS'],
                'objective' => 'Solve equations like 5x + 3 = 2x + 15 by getting the x terms onto one side.'],
            'EQ.FRACTIONS' => ['name' => 'Equations with fractions', 'year' => 8, 'strand' => 'Algebra', 'kind' => 'equation', 'prereqs' => ['EQ.TWO_STEP', 'ALG.DISTRIBUTIVE'],
                'objective' => 'Solve equations like (x + 3)/2 = 5 by clearing the fraction first.'],
        ];
    }

    public static function get(string $code): ?array
    {
        return self::all()[$code] ?? null;
    }

    public static function name(string $code): string
    {
        return self::get($code)['name'] ?? $code;
    }

    /** The equation skills a student can practise, in teaching order. */
    public static function equationSkills(): array
    {
        return array_keys(array_filter(self::all(), fn ($s) => $s['kind'] === 'equation'));
    }

    /** Which skill an equation practises, from its shape. */
    public static function classify(Equation $eq): string
    {
        if ($eq->denominators() !== []) {
            return 'EQ.FRACTIONS';
        }
        if ($eq->hasBrackets()) {
            return 'EQ.BRACKETS';
        }
        if ($eq->left->hasVar() && $eq->right->hasVar()) {
            return 'EQ.BOTH_SIDES';
        }
        $x = $eq->left->hasVar() ? $eq->l : $eq->r;
        $steps = (int) ! $x->b->isZero() + (int) ! $x->a->isOne() + (int) ! $eq->isSimplified();

        return $steps >= 2 ? 'EQ.TWO_STEP' : 'EQ.ONE_STEP';
    }
}

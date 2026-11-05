<?php

namespace App\Tutor\Math;

/**
 * Works out a clear, standard sequence of steps from any line of a linear equation.
 * Used for hints (the next step from where the student is), worked examples and checking answers.
 */
final class Solver
{
    /**
     * @return list<array{kind:string,goal:string,say:string,line:Equation}>
     */
    public static function steps(Equation $eq, int $max = 10): array
    {
        $steps = [];
        for ($n = 0; $n < $max; $n++) {
            $step = self::next($eq);
            if ($step === null) {
                break;
            }
            $steps[] = $step;
            $eq = $step['line'];
        }

        return $steps;
    }

    /** The single next step from this line, or null if it's already x = number. */
    public static function next(Equation $eq): ?array
    {
        if ($eq->solution()['type'] !== Equation::ONE) {
            return null;
        }
        $v = $eq->var;
        if ($eq->isFinal()) {
            return $eq->left->type === Node::VAR ? null
                : self::step('swap', "Write it with {$v} first.", "Swap the sides so {$v} is on the left.",
                    Equation::fromLinear(Linear::x(), $eq->l, $v));
        }

        $dens = $eq->denominators();
        if ($dens !== []) {
            $m = Rational::int(self::lcm($dens));

            return self::step('clear_fraction', 'Get rid of the fraction first.',
                "Multiply every term on both sides by {$m} to clear the fraction.",
                Equation::fromLinear($eq->l->scale($m), $eq->r->scale($m), $v));
        }
        if ($eq->hasBrackets()) {
            return self::step('expand', 'Start by expanding the brackets.',
                'Expand the brackets: multiply every term inside by the number outside.',
                Equation::fromLinear($eq->l, $eq->r, $v));
        }
        if (! $eq->isSimplified() && Equation::fromLinear($eq->l, $eq->r, $v)->format() !== $eq->format()) {
            return self::step('collect', 'Tidy up each side first.',
                "Collect like terms: add the {$v} terms together and the numbers together on each side.",
                Equation::fromLinear($eq->l, $eq->r, $v));
        }

        [$a1, $b1, $a2, $b2] = [$eq->l->a, $eq->l->b, $eq->r->a, $eq->r->b];

        if (! $a1->isZero() && ! $a2->isZero()) {
            $keepLeft = $a1->compare($a2) >= 0;
            $move = $keepLeft ? $a2 : $a1;
            $term = (new Linear($move->abs(), Rational::zero()))->format($v);
            $say = ($move->isNegative() ? "Add {$term} to" : "Subtract {$term} from").' both sides, so the '.$v.' terms are on one side.';
            $l = $keepLeft ? new Linear($a1->sub($a2), $b1) : Linear::constant($b1);
            $r = $keepLeft ? Linear::constant($b2) : new Linear($a2->sub($a1), $b2);

            return self::step('move_x', "Get all the {$v} terms onto one side.", $say, Equation::fromLinear($l, $r, $v));
        }

        $xLeft = ! $a1->isZero();
        [$a, $b, $c] = $xLeft ? [$a1, $b1, $b2] : [$a2, $b2, $b1];

        if (! $b->isZero()) {
            $say = ($b->isNegative() ? 'Add '.$b->abs().' to' : 'Subtract '.$b.' from').' both sides.';
            $x = new Linear($a, Rational::zero());
            $k = Linear::constant($c->sub($b));

            return self::step('move_const', "Get the {$v} term on its own: deal with the number next to it.", $say,
                $xLeft ? Equation::fromLinear($x, $k, $v) : Equation::fromLinear($k, $x, $v));
        }

        if (! $a->isOne()) {
            $say = match (true) {
                $a->equals(Rational::int(-1)) => 'Multiply both sides by −1.',
                $a->isInteger() => "Divide both sides by {$a}.",
                abs($a->num) === 1 => 'Multiply both sides by '.($a->isNegative() ? '−' : '').$a->den.'.',
                default => "Divide both sides by {$a} (the same as multiplying by ".Rational::one()->div($a).').',
            };
            $x = Linear::x();
            $k = Linear::constant($c->div($a));

            return self::step('divide', "{$v} is nearly alone. What do you do about the number in front of {$v}?", $say,
                $xLeft ? Equation::fromLinear($x, $k, $v) : Equation::fromLinear($k, $x, $v));
        }

        if (! $xLeft) {
            return self::step('swap', "Write it with {$v} first.", "Swap the sides so {$v} is on the left.",
                Equation::fromLinear(Linear::x(), Linear::constant($c), $v));
        }

        return null;
    }

    /** "Check: when x = 3, the left side is 10 and the right side is 10 ✓" */
    public static function check(Equation $original, Rational $x): string
    {
        $l = Evaluator::at($original->left, $x);
        $r = Evaluator::at($original->right, $x);

        return "Check: when {$original->var} = {$x}, the left side is {$l} and the right side is {$r}".($l->equals($r) ? ' ✓' : ' ✗');
    }

    private static function step(string $kind, string $goal, string $say, Equation $line): array
    {
        return ['kind' => $kind, 'goal' => $goal, 'say' => $say, 'line' => $line];
    }

    /** @param list<int> $ns */
    private static function lcm(array $ns): int
    {
        $l = 1;
        foreach ($ns as $n) {
            $a = $l;
            $b = $n;
            while ($b) {
                [$a, $b] = [$b, $a % $b];
            }
            $l = intdiv($l * $n, $a);
        }

        return $l;
    }
}

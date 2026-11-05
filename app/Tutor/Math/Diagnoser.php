<?php

namespace App\Tutor\Math;

/**
 * Explains a wrong line. It re-does the previous line with each known wrong rule;
 * if a wrong rule gives exactly what the student wrote, that is the likely misconception.
 * If no rule fits, it says so ("not enough evidence") rather than guessing.
 */
final class Diagnoser
{
    /**
     * @return list<array{code:string,confidence:string,detail:array}>
     */
    public static function diagnose(Equation $prev, Equation $student): array
    {
        $target = $prev->solution();
        $got = $student->solution();
        if ($target['type'] !== Equation::ONE) {
            return [['code' => Misconceptions::UNKNOWN, 'confidence' => 'low', 'detail' => []]];
        }
        $s = $target['x'];

        $matches = [];
        foreach (self::candidates($prev) as [$code, $l, $r, $detail]) {
            try {
                $cand = Equation::fromLinear($l, $r, $prev->var);
            } catch (MathException) {
                continue;
            }
            if ($cand->sameSolutionAs($prev)) {
                continue; // not actually a mistake
            }
            if ($cand->sameSolutionAs($student) && ! isset($matches[$code])) {
                $matches[$code] = $detail;
            }
        }

        if ($got['type'] === Equation::ONE && ! $s->isZero() && $got['x']->equals($s->neg()) && ! $matches) {
            $matches[Misconceptions::SIGN_SLIP] = [];
        }

        if ($matches) {
            $confidence = count($matches) === 1 ? 'high' : 'medium';

            return array_map(fn ($code) => ['code' => $code, 'confidence' => $confidence, 'detail' => $matches[$code]], array_keys($matches));
        }

        if ($slip = self::arithmetic($student, $s)) {
            return [['code' => Misconceptions::ARITHMETIC, 'confidence' => 'low', 'detail' => $slip]];
        }

        return [['code' => Misconceptions::UNKNOWN, 'confidence' => 'low', 'detail' => []]];
    }

    /** @return iterable<array{0:string,1:Linear,2:Linear,3:array}> */
    private static function candidates(Equation $p): iterable
    {
        // Mistakes inside brackets and fractions, at each place they occur.
        foreach (['left', 'right'] as $side) {
            $node = $p->{$side};
            $other = $side === 'left' ? $p->r : $p->l;
            foreach ($node->all() as $n) {
                $bugs = $n->isBracketProduct() ? [Evaluator::BUG_DIST_FIRST_ONLY, Evaluator::BUG_DIST_SIGN] : ($n->isSumOverNumber() ? [Evaluator::BUG_DIV_ONE_TERM] : []);
                foreach ($bugs as $bug) {
                    try {
                        $buggy = Evaluator::withBug($node, $bug, $n->id);
                    } catch (MathException) {
                        continue;
                    }
                    $detail = ['part' => $n->format($p->var)];
                    yield $side === 'left' ? [$bug, $buggy, $other, $detail] : [$bug, $other, $buggy, $detail];
                }
            }
        }

        [$a1, $b1, $a2, $b2] = [$p->l->a, $p->l->b, $p->r->a, $p->r->b];
        $L = fn ($a, $b) => new Linear($a, $b);
        $zero = Rational::zero();

        // Moving a term across = without changing its sign.
        if (! $b1->isZero() && ! $a1->isZero()) {
            yield [Misconceptions::MOVE_NO_SIGN, $L($a1, $zero), $L($a2, $b2->add($b1)), ['term' => (string) $b1]];
        }
        if (! $b2->isZero() && ! $a2->isZero()) {
            yield [Misconceptions::MOVE_NO_SIGN, $L($a1, $b1->add($b2)), $L($a2, $zero), ['term' => (string) $b2]];
        }
        if (! $a1->isZero() && ! $a2->isZero()) {
            yield [Misconceptions::MOVE_NO_SIGN, $L($a1->add($a2), $b1), $L($zero, $b2), ['term' => $L($a2, $zero)->format($p->var)]];
            yield [Misconceptions::MOVE_NO_SIGN, $L($zero, $b1), $L($a2->add($a1), $b2), ['term' => $L($a1, $zero)->format($p->var)]];
        }

        foreach ([[$a1, $b1, $b2, $a2, true], [$a2, $b2, $b1, $a1, false]] as [$a, $b, $c, $otherA, $xLeft]) {
            if ($a->isZero() || ! $otherA->isZero()) {
                continue;
            }
            $put = fn (Linear $x, Linear $k) => $xLeft ? [$x, $k] : [$k, $x];
            if ($b->isZero() && ! $a->isOne()) {
                // Undoing the multiplication with the wrong operation.
                $wrong = [$c->sub($a), $c->add($a), $c->mul($a)];
                if (! $c->isZero()) {
                    $wrong[] = $a->div($c);
                }
                foreach ($wrong as $w) {
                    yield [Misconceptions::WRONG_INVERSE, ...$put(Linear::x(), Linear::constant($w)), ['coefficient' => (string) $a]];
                }
            }
            if (! $b->isZero() && ! $a->isOne()) {
                // Dividing only one term.
                yield [Misconceptions::DIV_ONE_TERM, ...$put($L(Rational::one(), $b), Linear::constant($c->div($a))), ['coefficient' => (string) $a]];
                yield [Misconceptions::DIV_ONE_TERM, ...$put($L(Rational::one(), $b->div($a)), Linear::constant($c)), ['coefficient' => (string) $a]];
            }
            if (! $b->isZero()) {
                // Undoing + b by multiplying or dividing.
                yield [Misconceptions::WRONG_INVERSE, ...$put($L($a, $zero), Linear::constant($c->mul($b))), ['term' => (string) $b]];
                // Adding x terms and numbers together.
                yield [Misconceptions::COMBINE_UNLIKE, ...$put($L($a->add($b), $zero), Linear::constant($c)), []];
            }
        }
    }

    /** Is the student's line right except for one number? Then point at that number. */
    private static function arithmetic(Equation $st, Rational $s): ?array
    {
        [$p, $q, $u, $v] = [$st->l->a, $st->l->b, $st->r->a, $st->r->b];
        $options = [];
        $diff = $p->sub($u);
        if (! $diff->isZero()) {
            $options[] = ['side' => 'right', 'had' => $v, 'should' => $p->sub($u)->mul($s)->add($q)];
            $options[] = ['side' => 'left', 'had' => $q, 'should' => $u->sub($p)->mul($s)->add($v)];
        }
        if (! $s->isZero()) {
            if (! $p->isZero()) {
                $options[] = ['side' => 'left', 'had' => $p, 'should' => $u->mul($s)->add($v)->sub($q)->div($s), 'is_coefficient' => true];
            }
            if (! $u->isZero()) {
                $options[] = ['side' => 'right', 'had' => $u, 'should' => $p->mul($s)->add($q)->sub($v)->div($s), 'is_coefficient' => true];
            }
        }
        foreach ($options as $o) {
            if ($o['had']->isZero() || $o['had']->equals($o['should'])) {
                continue;
            }
            $gap = $o['had']->sub($o['should'])->abs();
            if ($gap->compare(Rational::int(12)) <= 0 || $o['had']->equals($o['should']->neg())) {
                return ['side' => $o['side'], 'number' => (string) $o['had']->abs(), 'is_coefficient' => $o['is_coefficient'] ?? false];
            }
        }

        return null;
    }
}

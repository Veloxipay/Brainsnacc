<?php

namespace App\Tutor\Math;

/**
 * Turns a parsed expression into a simplified linear form (a·x + b).
 * Can also apply ONE deliberate wrong rule at one place, to reproduce a common student mistake.
 */
final class Evaluator
{
    /** Multiply only the first term in a bracket: 3(x + 2) → 3x + 2. */
    public const BUG_DIST_FIRST_ONLY = 'DIST_FIRST_ONLY';

    /** Wrong sign on the later terms when expanding: −2(x − 3) → −2x − 6. */
    public const BUG_DIST_SIGN = 'DIST_SIGN';

    /** Divide only the first term on top of a fraction: (x + 6)/3 → x/3 + 6. */
    public const BUG_DIV_ONE_TERM = 'DIV_ONE_TERM';

    private function __construct(private readonly ?string $bug = null, private readonly ?int $bugAt = null) {}

    public static function linear(Node $n): Linear
    {
        return (new self)->eval($n);
    }

    public static function constant(Node $n): Rational
    {
        $l = self::linear($n);
        if (! $l->isConstant()) {
            throw new MathException('Expected a number here.');
        }

        return $l->b;
    }

    /** Evaluate with one wrong rule applied at the node with id $at. */
    public static function withBug(Node $n, string $bug, int $at): Linear
    {
        return (new self($bug, $at))->eval($n);
    }

    /** The value of the expression when the unknown = $x. */
    public static function at(Node $n, Rational $x): Rational
    {
        $l = self::linear($n);

        return $l->a->mul($x)->add($l->b);
    }

    private function eval(Node $n): Linear
    {
        if ($this->bug && $n->id === $this->bugAt) {
            return $this->buggy($n);
        }

        return match ($n->type) {
            Node::NUM => Linear::constant($n->value),
            Node::VAR => Linear::x(),
            Node::NEG => $this->eval($n->left)->neg(),
            Node::ADD => $this->eval($n->left)->add($this->eval($n->right)),
            Node::SUB => $this->eval($n->left)->sub($this->eval($n->right)),
            Node::MUL => $this->eval($n->left)->mul($this->eval($n->right)),
            Node::DIV => $this->eval($n->left)->div($this->eval($n->right)),
        };
    }

    private function buggy(Node $n): Linear
    {
        if ($this->bug === self::BUG_DIV_ONE_TERM) {
            $k = $this->eval($n->right);
            $terms = $n->left->terms();

            return $this->sumTerms($terms, fn ($i, Linear $t) => $i === 0 ? $t->div($k) : $t);
        }

        // Bracket products: find the multiplier and the bracket.
        [$k, $sum] = $n->left->isSum() ? [$this->eval($n->right), $n->left] : [$this->eval($n->left), $n->right];
        $terms = $sum->terms();

        return match ($this->bug) {
            self::BUG_DIST_FIRST_ONLY => $this->sumTerms($terms, fn ($i, Linear $t) => $i === 0 ? $t->mul($k) : $t),
            self::BUG_DIST_SIGN => $this->sumTerms($terms, fn ($i, Linear $t) => $i === 0 ? $t->mul($k) : $t->mul($k)->neg()),
        };
    }

    /** @param list<array{0:int,1:Node}> $terms */
    private function sumTerms(array $terms, callable $f): Linear
    {
        $total = Linear::constant(Rational::zero());
        foreach ($terms as $i => [$sign, $node]) {
            $t = $this->eval($node);
            $t = $sign < 0 ? $t->neg() : $t;
            $total = $total->add($f($i, $t));
        }

        return $total;
    }
}

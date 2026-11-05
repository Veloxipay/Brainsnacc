<?php

namespace App\Tutor\Math;

/** A linear equation in one unknown, as written (left = right), with exact solving. */
final class Equation
{
    public const ONE = 'one', ALL = 'all', NONE = 'none';

    public readonly Linear $l;

    public readonly Linear $r;

    public function __construct(public readonly Node $left, public readonly Node $right, public readonly string $var = 'x')
    {
        $this->l = Evaluator::linear($left);
        $this->r = Evaluator::linear($right);
    }

    public static function parse(string $text): self
    {
        [$l, $r, $v] = Parser::equation($text);

        return new self($l, $r, $v);
    }

    /** An equation built from two simplified sides (used for worked steps). */
    public static function fromLinear(Linear $l, Linear $r, string $var = 'x'): self
    {
        return new self(self::nodeOf($l), self::nodeOf($r), $var);
    }

    private static function nodeOf(Linear $e): Node
    {
        $x = null;
        if (! $e->a->isZero()) {
            $abs = $e->a->abs();
            $x = $abs->isOne() ? Node::var() : Node::op(Node::MUL, Node::num($abs), Node::var());
            if (! $abs->isInteger()) {
                $x = $abs->num === 1
                    ? Node::op(Node::DIV, Node::var(), Node::num(Rational::int($abs->den)))
                    : Node::op(Node::DIV, Node::op(Node::MUL, Node::num(Rational::int($abs->num)), Node::var()), Node::num(Rational::int($abs->den)));
            }
            if ($e->a->isNegative()) {
                $x = Node::neg($x);
            }
        }
        if ($x === null) {
            return Node::num($e->b);
        }
        if ($e->b->isZero()) {
            return $x;
        }

        return Node::op($e->b->isNegative() ? Node::SUB : Node::ADD, $x, Node::num($e->b->abs()));
    }

    /** @return array{type:string,x?:Rational} */
    public function solution(): array
    {
        $a = $this->l->a->sub($this->r->a);
        $c = $this->r->b->sub($this->l->b);
        if ($a->isZero()) {
            return ['type' => $c->isZero() ? self::ALL : self::NONE];
        }

        return ['type' => self::ONE, 'x' => $c->div($a)];
    }

    public function sameSolutionAs(self $o): bool
    {
        $s1 = $this->solution();
        $s2 = $o->solution();

        return $s1['type'] === $s2['type'] && ($s1['type'] !== self::ONE || $s1['x']->equals($s2['x']));
    }

    /** "x = 3" or "3 = x": the unknown on its own with a number on the other side. */
    public function isFinal(): bool
    {
        return ($this->left->type === Node::VAR && ! $this->right->hasVar())
            || ($this->right->type === Node::VAR && ! $this->left->hasVar());
    }

    public function holdsAt(Rational $x): bool
    {
        return Evaluator::at($this->left, $x)->equals(Evaluator::at($this->right, $x));
    }

    /** As written by the student, tidied: 2(3x − 4) = 10. */
    public function format(): string
    {
        return $this->left->format($this->var).' = '.$this->right->format($this->var);
    }

    /** Simplified sides: 6x − 8 = 10. */
    public function formatSimplified(): string
    {
        return $this->l->format($this->var).' = '.$this->r->format($this->var);
    }

    /** True if the line is already in simplest form on both sides (no brackets, like terms collected). */
    public function isSimplified(): bool
    {
        return $this->format() === $this->formatSimplified();
    }

    public function hasBrackets(): bool
    {
        foreach ([$this->left, $this->right] as $side) {
            foreach ($side->all() as $n) {
                if ($n->isBracketProduct() || $n->isSumOverNumber()) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Whole-number denominators used anywhere (x/3, (x + 1)/4 …). @return list<int> */
    public function denominators(): array
    {
        $out = [];
        foreach ([$this->left, $this->right] as $side) {
            foreach ($side->all() as $n) {
                if ($n->type === Node::DIV && ! $n->right->hasVar()) {
                    $d = Evaluator::constant($n->right);
                    if ($d->isInteger() && abs($d->num) > 1) {
                        $out[] = abs($d->num);
                    }
                }
                if ($n->type === Node::NUM && ! $n->value->isInteger()) {
                    $out[] = $n->value->den;
                }
            }
        }

        return array_values(array_unique($out));
    }
}

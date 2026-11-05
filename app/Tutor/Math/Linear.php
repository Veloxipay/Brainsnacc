<?php

namespace App\Tutor\Math;

/** A simplified linear expression: a·x + b, with exact fractions. */
final class Linear
{
    public function __construct(public readonly Rational $a, public readonly Rational $b) {}

    public static function constant(Rational $b): self
    {
        return new self(Rational::zero(), $b);
    }

    public static function x(): self
    {
        return new self(Rational::one(), Rational::zero());
    }

    public function isConstant(): bool
    {
        return $this->a->isZero();
    }

    public function add(self $o): self
    {
        return new self($this->a->add($o->a), $this->b->add($o->b));
    }

    public function sub(self $o): self
    {
        return new self($this->a->sub($o->a), $this->b->sub($o->b));
    }

    public function neg(): self
    {
        return new self($this->a->neg(), $this->b->neg());
    }

    public function scale(Rational $k): self
    {
        return new self($this->a->mul($k), $this->b->mul($k));
    }

    public function mul(self $o): self
    {
        if ($this->isConstant()) {
            return $o->scale($this->b);
        }
        if ($o->isConstant()) {
            return $this->scale($o->b);
        }
        throw new MathException('This has x multiplied by x (an x² term). The tutor only covers linear equations for now.');
    }

    public function div(self $o): self
    {
        if (! $o->isConstant()) {
            throw new MathException('This divides by an expression with x in it. The tutor only covers linear equations for now.');
        }
        if ($o->b->isZero()) {
            throw new MathException('This divides by zero.');
        }

        return $this->scale(Rational::one()->div($o->b));
    }

    public function equals(self $o): bool
    {
        return $this->a->equals($o->a) && $this->b->equals($o->b);
    }

    /** "3x + 7", "−x", "5", "x/2 − 1" style, with proper minus signs. */
    public function format(string $var = 'x'): string
    {
        if ($this->a->isZero()) {
            return (string) $this->b;
        }
        $s = self::term($this->a, $var);
        if (! $this->b->isZero()) {
            $s .= ($this->b->isNegative() ? ' − ' : ' + ').$this->b->abs();
        }

        return $s;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private static function term(Rational $a, string $var): string
    {
        $abs = $a->abs();
        $body = match (true) {
            $abs->isOne() => $var,
            $abs->isInteger() => $abs.$var,
            $abs->num === 1 => $var.'/'.$abs->den,
            default => $abs->num.$var.'/'.$abs->den,
        };

        return ($a->isNegative() ? '−' : '').$body;
    }
}

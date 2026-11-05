<?php

namespace App\Tutor\Math;

/** An exact fraction, so the checker never suffers from rounding (1/3 stays 1/3). */
final class Rational
{
    public readonly int $num;

    public readonly int $den;

    public function __construct(int $num, int $den = 1)
    {
        if ($den === 0) {
            throw new MathException('Division by zero.');
        }
        if ($den < 0) {
            $num = -$num;
            $den = -$den;
        }
        $g = self::gcd(abs($num), $den);
        $this->num = intdiv($num, $g ?: 1);
        $this->den = intdiv($den, $g ?: 1);
    }

    public static function int(int $n): self
    {
        return new self($n, 1);
    }

    /** "2.5" → 5/2, "3" → 3/1. */
    public static function fromDecimal(string $s): self
    {
        if (! preg_match('/^\d+(\.\d+)?$/', $s)) {
            throw new MathException("Not a number: {$s}");
        }
        if (! str_contains($s, '.')) {
            return self::int((int) $s);
        }
        [$whole, $frac] = explode('.', $s);
        if (strlen($frac) > 9) {
            throw new MathException('Too many decimal places.');
        }
        $den = 10 ** strlen($frac);

        return new self((int) $whole * $den + (int) $frac, $den);
    }

    public static function zero(): self
    {
        return self::int(0);
    }

    public static function one(): self
    {
        return self::int(1);
    }

    private static function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }

    public function add(self $o): self
    {
        return new self($this->num * $o->den + $o->num * $this->den, $this->den * $o->den);
    }

    public function sub(self $o): self
    {
        return $this->add($o->neg());
    }

    public function mul(self $o): self
    {
        return new self($this->num * $o->num, $this->den * $o->den);
    }

    public function div(self $o): self
    {
        if ($o->isZero()) {
            throw new MathException('Division by zero.');
        }

        return new self($this->num * $o->den, $this->den * $o->num);
    }

    public function neg(): self
    {
        return new self(-$this->num, $this->den);
    }

    public function abs(): self
    {
        return new self(abs($this->num), $this->den);
    }

    public function isZero(): bool
    {
        return $this->num === 0;
    }

    public function isOne(): bool
    {
        return $this->num === 1 && $this->den === 1;
    }

    public function isInteger(): bool
    {
        return $this->den === 1;
    }

    public function isNegative(): bool
    {
        return $this->num < 0;
    }

    public function equals(self $o): bool
    {
        return $this->num === $o->num && $this->den === $o->den;
    }

    public function compare(self $o): int
    {
        return $this->num * $o->den <=> $o->num * $this->den;
    }

    public function toFloat(): float
    {
        return $this->num / $this->den;
    }

    /** "3", "−4", "7/2", "−1/3" (proper minus sign). */
    public function __toString(): string
    {
        $sign = $this->num < 0 ? '−' : '';
        $n = abs($this->num);

        return $this->den === 1 ? $sign.$n : $sign.$n.'/'.$this->den;
    }

    /** Plain ASCII form for storage: "-7/2". */
    public function toAscii(): string
    {
        return $this->den === 1 ? (string) $this->num : $this->num.'/'.$this->den;
    }

    public static function fromAscii(string $s): self
    {
        if (str_contains($s, '/')) {
            [$n, $d] = explode('/', $s, 2);

            return new self((int) $n, (int) $d);
        }

        return self::int((int) $s);
    }
}

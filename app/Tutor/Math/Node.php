<?php

namespace App\Tutor\Math;

/**
 * One piece of a parsed expression, kept as the student wrote it (brackets and all),
 * so we can print it back for confirmation and spot mistakes in how it was expanded.
 */
final class Node
{
    public const NUM = 'num', VAR = 'var', ADD = 'add', SUB = 'sub', MUL = 'mul', DIV = 'div', NEG = 'neg';

    private static int $nextId = 0;

    public readonly int $id;

    private function __construct(
        public readonly string $type,
        public readonly ?Rational $value = null,
        public readonly ?Node $left = null,
        public readonly ?Node $right = null,
    ) {
        $this->id = ++self::$nextId;
    }

    public static function num(Rational $v): self
    {
        return new self(self::NUM, $v);
    }

    public static function var(): self
    {
        return new self(self::VAR);
    }

    public static function op(string $type, Node $l, Node $r): self
    {
        return new self($type, null, $l, $r);
    }

    public static function neg(Node $e): self
    {
        return new self(self::NEG, null, $e);
    }

    public function isSum(): bool
    {
        return $this->type === self::ADD || $this->type === self::SUB
            || ($this->type === self::NEG && $this->left->isSum());
    }

    public function hasVar(): bool
    {
        return match ($this->type) {
            self::VAR => true,
            self::NUM => false,
            self::NEG => $this->left->hasVar(),
            default => $this->left->hasVar() || $this->right->hasVar(),
        };
    }

    /** @return list<Node> every node, depth first */
    public function all(): array
    {
        $out = [$this];
        foreach ([$this->left, $this->right] as $c) {
            if ($c) {
                array_push($out, ...$c->all());
            }
        }

        return $out;
    }

    /** A bracket being multiplied, like 3(x + 2) or (x − 1)·4. */
    public function isBracketProduct(): bool
    {
        return $this->type === self::MUL && ($this->left->isSum() || $this->right->isSum());
    }

    /** A fraction whose top is a sum, like (x + 3)/2. */
    public function isSumOverNumber(): bool
    {
        return $this->type === self::DIV && $this->left->isSum() && ! $this->right->hasVar();
    }

    /**
     * The terms of a sum with their signs: a − b + c → [[+1, a], [−1, b], [+1, c]].
     *
     * @return list<array{0:int,1:Node}>
     */
    public function terms(int $sign = 1): array
    {
        return match ($this->type) {
            self::ADD => [...$this->left->terms($sign), ...$this->right->terms($sign)],
            self::SUB => [...$this->left->terms($sign), ...$this->right->terms(-$sign)],
            self::NEG => $this->left->isSum() ? $this->left->terms(-$sign) : [[-$sign, $this->left]],
            default => [[$sign, $this]],
        };
    }

    /** Print it back the way a student would write it: 3(x − 2) + 5 = 2x/3. */
    public function format(string $var = 'x'): string
    {
        return $this->fmt($var, 0);
    }

    private function prec(): int
    {
        return match ($this->type) {
            self::ADD, self::SUB => 1,
            self::MUL, self::DIV => 2,
            self::NEG => 3,
            default => 4,
        };
    }

    private function fmt(string $var, int $outer): string
    {
        $s = match ($this->type) {
            self::NUM => $this->value->isInteger() ? (string) $this->value->abs() : '('.$this->value->abs().')',
            self::VAR => $var,
            self::ADD => $this->left->fmt($var, 1).' + '.$this->right->fmt($var, 2),
            self::SUB => $this->left->fmt($var, 1).' − '.$this->right->fmt($var, 2),
            self::NEG => '−'.$this->left->fmt($var, $this->left->type === self::NEG ? 3 : 2),
            self::MUL => $this->fmtMul($var),
            self::DIV => $this->left->fmt($var, 3).'/'.$this->right->fmt($var, 3),
        };
        if ($this->type === self::NUM && $this->value->isNegative()) {
            $s = '−'.$s;
            if ($outer >= 2) {
                $s = '('.$s.')';
            }

            return $s;
        }

        return $this->prec() < $outer ? '('.$s.')' : $s;
    }

    private function fmtMul(string $var): string
    {
        $l = $this->left->fmt($var, 2);
        $r = $this->right->fmt($var, 3);
        // Write 3x, 3(x + 1), x(2) as a student would; keep × between two plain numbers.
        $implicit = $this->right->type === self::VAR
            || str_starts_with($r, '(')
            || ($this->left->type === self::VAR && $this->right->type !== self::NUM);

        return $implicit ? $l.$r : $l.' × '.$r;
    }
}

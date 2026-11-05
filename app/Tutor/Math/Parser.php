<?php

namespace App\Tutor\Math;

/**
 * Reads what a student types: 2(3x - 4) = 10, 3x+7=31, (x+3)/2 = 5, x ÷ 4 − 2 = 1, −x = 6, 2.5x = 10.
 * Understands ×, ÷, −, ², implied multiplication (3x, 2(x+1), (x+1)(3)) and one letter as the unknown.
 * Never guesses: anything it can't read is reported with the position, so the student can fix it.
 */
final class Parser
{
    /** @var list<array{0:string,1:string,2:int}> [type, text, position] */
    private array $tokens = [];

    private int $i = 0;

    private ?string $var = null;

    private function __construct(private readonly string $src) {}

    /** @return array{0:Node,1:Node,2:string} left, right, variable letter */
    public static function equation(string $text): array
    {
        $p = new self($text);
        $p->tokenize();
        $eq = array_keys(array_filter($p->tokens, fn ($t) => $t[0] === '='));
        if (count($eq) !== 1) {
            throw MathException::at(count($eq) === 0 ? 'An equation needs an equals sign (=).' : 'There is more than one equals sign.', count($eq) ? $p->tokens[$eq[1]][2] : null);
        }
        $left = $p->expr();
        $p->expect('=');
        $right = $p->expr();
        $p->end();

        return [$left, $right, $p->var ?? 'x'];
    }

    /** @return array{0:Node,1:string} */
    public static function expression(string $text): array
    {
        $p = new self($text);
        $p->tokenize();
        if (array_filter($p->tokens, fn ($t) => $t[0] === '=')) {
            throw new MathException('Type just the expression, without an equals sign.');
        }
        $e = $p->expr();
        $p->end();

        return [$e, $p->var ?? 'x'];
    }

    private function tokenize(): void
    {
        $s = strtr(trim($this->src), [
            '−' => '-', '–' => '-', '—' => '-', '×' => '*', '·' => '*', '⋅' => '*', '÷' => '/', '∶' => '/',
            '²' => '^2', '³' => '^3', '½' => '(1/2)', '¼' => '(1/4)', '¾' => '(3/4)', '⅓' => '(1/3)', '⅔' => '(2/3)',
            '[' => '(', ']' => ')', '{' => '(', '}' => ')', '，' => ',',
        ]);
        if ($s === '') {
            throw new MathException('Type the question first.');
        }
        if (mb_strlen($s) > 200) {
            throw new MathException('That is too long for one line.');
        }
        $len = strlen($s);
        for ($k = 0; $k < $len;) {
            $c = $s[$k];
            if (ctype_space($c)) {
                $k++;

                continue;
            }
            if (ctype_digit($c) || ($c === '.' && $k + 1 < $len && ctype_digit($s[$k + 1]))) {
                preg_match('/\G\d*\.?\d+|\G\d+\.?/', $s, $m, 0, $k);
                $num = rtrim($m[0], '.');
                $this->tokens[] = ['num', $num[0] === '.' ? '0'.$num : $num, $k];
                $k += strlen($m[0]);

                continue;
            }
            if (ctype_alpha($c)) {
                $letter = strtolower($c);
                if ($k + 1 < $len && ctype_alpha($s[$k + 1])) {
                    throw MathException::at('Use a single letter for the unknown (like x).', $k);
                }
                if ($this->var !== null && $this->var !== $letter) {
                    throw MathException::at("This uses two different letters ({$this->var} and {$letter}). The tutor solves for one unknown.", $k);
                }
                $this->var = $letter;
                $this->tokens[] = ['var', $letter, $k];
                $k++;

                continue;
            }
            if (str_contains('+-*/()=^', $c)) {
                $this->tokens[] = [$c, $c, $k];
                $k++;

                continue;
            }
            throw MathException::at("I can't read the symbol \"{$c}\".", $k);
        }
    }

    private function peek(): ?string
    {
        return $this->tokens[$this->i][0] ?? null;
    }

    private function pos(): ?int
    {
        return $this->tokens[$this->i][2] ?? strlen($this->src);
    }

    private function expect(string $type): void
    {
        if ($this->peek() !== $type) {
            $what = $type === ')' ? 'a closing bracket )' : "\"{$type}\"";
            throw MathException::at("I expected {$what} here.", $this->pos());
        }
        $this->i++;
    }

    private function end(): void
    {
        if ($this->peek() !== null) {
            throw MathException::at("I didn't expect \"{$this->tokens[$this->i][1]}\" here.", $this->pos());
        }
    }

    private function expr(): Node
    {
        $node = $this->term();
        while (in_array($this->peek(), ['+', '-'], true)) {
            $op = $this->tokens[$this->i++][0];
            $node = Node::op($op === '+' ? Node::ADD : Node::SUB, $node, $this->term());
        }

        return $node;
    }

    private function term(): Node
    {
        $node = $this->unary();
        while (true) {
            $t = $this->peek();
            if ($t === '*' || $t === '/') {
                $this->i++;
                $node = Node::op($t === '*' ? Node::MUL : Node::DIV, $node, $this->unary());
            } elseif ($t === '(' || $t === 'var' || ($t === 'num' && $this->prevIsCloseOrVar())) {
                $node = Node::op(Node::MUL, $node, $this->power());
            } else {
                return $node;
            }
        }
    }

    private function prevIsCloseOrVar(): bool
    {
        $prev = $this->tokens[$this->i - 1][0] ?? null;

        return $prev === ')' || $prev === 'var';
    }

    private function unary(): Node
    {
        if ($this->peek() === '-') {
            $this->i++;

            return Node::neg($this->unary());
        }
        if ($this->peek() === '+') {
            $this->i++;

            return $this->unary();
        }

        return $this->power();
    }

    private function power(): Node
    {
        $base = $this->atom();
        if ($this->peek() !== '^') {
            return $base;
        }
        $at = $this->pos();
        $this->i++;
        if ($this->peek() !== 'num' || ! ctype_digit($this->tokens[$this->i][1])) {
            throw MathException::at('Only whole-number powers can be read.', $at);
        }
        $n = (int) $this->tokens[$this->i++][1];
        if ($base->hasVar()) {
            if ($n === 1) {
                return $base;
            }
            throw MathException::at('This has a power of the unknown (like x²). The tutor only covers linear equations for now.', $at);
        }
        if ($n > 6) {
            throw MathException::at('That power is too big.', $at);
        }
        $v = Evaluator::constant($base);
        $r = Rational::one();
        for ($k = 0; $k < $n; $k++) {
            $r = $r->mul($v);
        }

        return Node::num($r);
    }

    private function atom(): Node
    {
        $t = $this->tokens[$this->i] ?? null;
        if ($t === null) {
            throw MathException::at('Something is missing at the end.', strlen($this->src));
        }
        if ($t[0] === 'num') {
            $this->i++;

            return Node::num(Rational::fromDecimal($t[1]));
        }
        if ($t[0] === 'var') {
            $this->i++;

            return Node::var();
        }
        if ($t[0] === '(') {
            $this->i++;
            if ($this->peek() === ')') {
                throw MathException::at('These brackets are empty.', $this->pos());
            }
            $e = $this->expr();
            $this->expect(')');

            return $e;
        }
        throw MathException::at($t[0] === ')' ? 'This closing bracket has no opening bracket.' : "I didn't expect \"{$t[1]}\" here.", $t[2]);
    }
}

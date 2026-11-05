<?php

namespace App\Tutor;

use App\Tutor\Math\Equation;
use App\Tutor\Math\Evaluator;
use App\Tutor\Math\Parser;
use App\Tutor\Math\Rational;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Makes fresh questions from checked templates. Every question is solved by the maths engine
 * before it is used, and only whole-number answers are kept.
 */
final class Generator
{
    private Randomizer $rnd;

    public function __construct(?int $seed = null)
    {
        $this->rnd = new Randomizer($seed === null ? null : new Mt19937($seed));
    }

    private function n(int $lo, int $hi, bool $nonZero = true): int
    {
        do {
            $v = $this->rnd->getInt($lo, $hi);
        } while ($nonZero && $v === 0);

        return $v;
    }

    private function pick(array $a): mixed
    {
        return $a[$this->rnd->getInt(0, count($a) - 1)];
    }

    private static function signed(int $v): string
    {
        return $v < 0 ? '- '.abs($v) : '+ '.$v;
    }

    /** A new equation for an equation skill. @return array{text:string,equation:Equation} */
    public function equation(string $skill, ?string $avoid = null): array
    {
        for ($tries = 0; $tries < 50; $tries++) {
            $text = $this->equationText($skill);
            if ($text === $avoid || preg_match('/[+-] 0(?!\d)/', $text)) {
                continue;
            }
            $eq = Equation::parse($text);
            $sol = $eq->solution();
            if ($sol['type'] === Equation::ONE && $sol['x']->isInteger() && Skills::classify($eq) === $skill) {
                return ['text' => $eq->format(), 'equation' => $eq];
            }
        }
        throw new \LogicException("Could not make a question for {$skill}");
    }

    private function equationText(string $skill): string
    {
        $s = $this->n(-6, 12);
        $a = $this->n(2, 9);
        $b = $this->n(1, 15);

        return match ($skill) {
            'EQ.ONE_STEP' => $this->pick([
                'x '.self::signed($b).' = '.($s + $b),
                'x - '.$b.' = '.($s - $b),
                $a.'x = '.($a * $s),
                'x/'.$a.' = '.$s,
            ]),
            'EQ.TWO_STEP' => $this->pick([
                $a.'x '.self::signed($b).' = '.($a * $s + $b),
                $a.'x - '.$b.' = '.($a * $s - $b),
                'x/'.$a.' '.self::signed($b).' = '.($s + $b),
            ]),
            'EQ.BRACKETS' => (function () use ($s, $a, $b) {
                $c = $this->n(1, 5);

                return $this->pick([
                    $a.'(x '.self::signed($b).') = '.($a * ($s + $b)),
                    $a.'(x - '.$b.') = '.($a * ($s - $b)),
                    $a.'('.$c.'x - '.$b.') = '.($a * ($c * $s - $b)),
                    '-'.$a.'(x '.self::signed($b).') = '.(-$a * ($s + $b)),
                ]);
            })(),
            'EQ.BOTH_SIDES' => (function () use ($s, $a, $b) {
                do {
                    $c = $this->n(1, 9);
                } while ($c === $a);
                $d = $a * $s + $b - $c * $s;

                return $a.'x '.self::signed($b).' = '.($c === 1 ? '' : $c).'x '.self::signed($d);
            })(),
            'EQ.FRACTIONS' => (function () use ($s, $a) {
                $c = $this->n(-4, 8);
                $k = $this->n(1, 4);
                $plain = $a * $c - $s;
                $withK = $a * $c - $k * $s;

                return $this->pick([
                    '(x '.self::signed($plain).')/'.$a.' = '.$c,
                    '('.$k.'x '.self::signed($withK).')/'.$a.' = '.$c,
                ]);
            })(),
        };
    }

    /**
     * A quick check question for a prerequisite skill.
     *
     * @return array{skill:string,kind:string,prompt:string,answer:string}
     */
    public function check(string $skill): array
    {
        [$p, $q, $r] = [$this->n(-12, 12), $this->n(2, 9), $this->n(-9, 9)];

        return match ($skill) {
            'INT.ADDSUB' => $this->numberCheck($skill, "Work out {$p} + {$q} − ({$r})", "{$p} + {$q} - ({$r})"),
            'INT.MULDIV' => $this->pick([
                $this->numberCheck($skill, "Work out −{$q} × {$r}", "-{$q} * ({$r})"),
                $this->numberCheck($skill, 'Work out '.(-$q * abs($r ?: 3)).' ÷ '.(-abs($r ?: 3)), (-$q * abs($r ?: 3)).' / ('.(-abs($r ?: 3)).')'),
            ]),
            'ALG.LIKE_TERMS' => (function () use ($skill) {
                [$a, $b, $c, $d] = [$this->n(2, 9), $this->n(1, 9), $this->n(1, 5), $this->n(1, 9)];
                $text = "{$a}x + {$b} - {$c}x + {$d}";

                return ['skill' => $skill, 'kind' => 'expression', 'prompt' => 'Simplify '.str_replace('-', '−', $text), 'answer' => (string) Evaluator::linear(Parser::expression($text)[0])];
            })(),
            'ALG.DISTRIBUTIVE' => (function () use ($skill, $q) {
                $a = $this->n(1, 9);
                $text = $this->pick(["{$q}(x + {$a})", "{$q}(x - {$a})", "-{$q}(x - {$a})", "-{$q}(2x + {$a})"]);

                return ['skill' => $skill, 'kind' => 'expression', 'prompt' => 'Expand '.str_replace('-', '−', $text), 'answer' => (string) Evaluator::linear(Parser::expression($text)[0])];
            })(),
            'EQ.ONE_STEP' => (function () use ($skill) {
                $e = $this->equation('EQ.ONE_STEP');

                return ['skill' => $skill, 'kind' => 'equation', 'prompt' => 'Solve '.$e['text'], 'answer' => (string) $e['equation']->solution()['x']];
            })(),
            default => $this->check('INT.ADDSUB'),
        };
    }

    private function numberCheck(string $skill, string $prompt, string $text): array
    {
        return ['skill' => $skill, 'kind' => 'number', 'prompt' => $prompt, 'answer' => (string) Evaluator::constant(Parser::expression($text)[0])];
    }

    /** Mark a quick-check answer typed by the student. @return array{correct:bool,message:?string} */
    public static function markCheck(array $check, string $typed): array
    {
        $typed = trim($typed);
        try {
            if ($check['kind'] === 'equation') {
                $value = str_contains($typed, '=') ? Equation::parse($typed) : null;
                if ($value) {
                    if (! $value->isFinal()) {
                        return ['correct' => false, 'message' => 'Finish solving it: write x = a number.'];
                    }
                    $x = $value->solution()['x'] ?? null;
                } else {
                    $x = Evaluator::constant(Parser::expression($typed)[0]);
                }

                return ['correct' => $x !== null && (string) $x === $check['answer'], 'message' => null];
            }
            [$node] = Parser::expression($typed);
            $lin = Evaluator::linear($node);
            if ($check['kind'] === 'number') {
                return ['correct' => $lin->isConstant() && (string) $lin->b === $check['answer'], 'message' => null];
            }
            $same = (string) $lin === $check['answer'];
            $simplified = count($node->terms()) <= 2 && ! array_filter($node->all(), fn ($n) => $n->isBracketProduct());
            if ($same && ! $simplified) {
                return ['correct' => false, 'message' => 'That has the right value, but it isn\'t simplified yet — write it with no brackets and one x term.'];
            }

            return ['correct' => $same, 'message' => null];
        } catch (\Throwable) {
            return ['correct' => false, 'message' => 'I couldn\'t read that. Try typing it again.'];
        }
    }
}

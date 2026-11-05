<?php

namespace Tests\Unit;

use App\Tutor\Generator;
use App\Tutor\Math\Diagnoser;
use App\Tutor\Math\Equation;
use App\Tutor\Math\MathException;
use App\Tutor\Math\Misconceptions;
use App\Tutor\Math\Solver;
use App\Tutor\Skills;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TutorMathTest extends TestCase
{
    private function solve(string $text): string
    {
        $s = Equation::parse($text)->solution();

        return $s['type'] === Equation::ONE ? (string) $s['x'] : $s['type'];
    }

    public static function equations(): array
    {
        return [
            ['2(3x - 4) = 10', '3'],
            ['2(3x − 4) = 10', '3'],
            ['3x+7=31', '8'],
            ['x/3 - 2 = 5', '21'],
            ['(x + 3)/2 = 5', '7'],
            ['5x + 3 = 2x + 15', '4'],
            ['-x = 6', '−6'],
            ['2.5x = 10', '4'],
            ['x ÷ 4 − 2 = 1', '12'],
            ['3 = x - 4', '7'],
            ['2x = 7', '7/2'],
            ['4(x+1) - 2(x-3) = 20', '5'],
            ['(2x + 1)(3) = 21', '3'],
            ['x + 1 = x + 2', Equation::NONE],
            ['2(x + 1) = 2x + 2', Equation::ALL],
            ['y + 5 = 2', '−3'],
            ['-2(x - 3) = 4', '1'],
            ['-(x + 4) = 2x - 1', '−1'],
            ['3 - 2x = 9', '−3'],
        ];
    }

    #[DataProvider('equations')]
    public function test_solves_equations_exactly(string $text, string $answer): void
    {
        $this->assertSame($answer, $this->solve($text));
    }

    public function test_prints_back_what_was_typed(): void
    {
        $this->assertSame('2(3x − 4) = 10', Equation::parse('2(3x-4)=10')->format());
        $this->assertSame('(x + 3)/2 = 5', Equation::parse('(x+3)/2=5')->format());
        $this->assertSame('6x − 8 = 10', Equation::parse('2(3x-4)=10')->formatSimplified());
        $this->assertSame('−x = 6', Equation::parse('-x=6')->format());
        $this->assertSame('−2x + 6 = 4', Equation::parse('-2(x-3)=4')->formatSimplified());
        $this->assertSame('−(x + 4) = 2x − 1', Equation::parse('-(x+4)=2x-1')->format());
    }

    public static function unreadable(): array
    {
        return [['2(3x - 4 = 10'], ['3x + 7'], ['x = 2 = 3'], ['x^2 = 4'], ['2x + 3y = 7'], ['3x ? 4 = 1'], ['()=3'], ['x(x+1) = 3']];
    }

    #[DataProvider('unreadable')]
    public function test_refuses_to_guess(string $text): void
    {
        $this->expectException(MathException::class);
        Equation::parse($text);
    }

    public function test_any_valid_route_is_accepted(): void
    {
        $q = Equation::parse('2(3x - 4) = 10');
        foreach (['6x - 8 = 10', '3x - 4 = 5', '6x = 18', '3x = 9', 'x = 3', '18 = 6x', '10 = 6x - 8'] as $line) {
            $this->assertTrue(Equation::parse($line)->sameSolutionAs($q), $line);
        }
        $this->assertFalse(Equation::parse('6x - 4 = 10')->sameSolutionAs($q));
    }

    public static function mistakes(): array
    {
        return [
            'brief: 3(x + 2) = 15' => ['3(x + 2) = 15', '3x + 2 = 15', Misconceptions::DIST_FIRST_ONLY],
            'brief: 2(3x − 4)' => ['2(3x - 4) = 10', '6x - 4 = 10', Misconceptions::DIST_FIRST_ONLY],
            'negative bracket' => ['-2(x - 3) = 4', '-2x - 6 = 4', Misconceptions::DIST_SIGN],
            'moved without sign' => ['3x + 7 = 31', '3x = 38', Misconceptions::MOVE_NO_SIGN],
            'x terms moved' => ['5x + 3 = 2x + 15', '7x + 3 = 15', Misconceptions::MOVE_NO_SIGN],
            'subtracted coefficient' => ['3x = 12', 'x = 9', Misconceptions::WRONG_INVERSE],
            'divided instead of multiplied' => ['x/3 = 7', 'x = 7/3', Misconceptions::WRONG_INVERSE],
            'divided one term' => ['3x + 6 = 12', 'x + 6 = 4', Misconceptions::DIV_ONE_TERM],
            'fraction top' => ['(x + 6)/3 = 5', 'x/3 + 6 = 5', Misconceptions::DIV_ONE_TERM],
            'unlike terms' => ['3x + 2 = 17', '5x = 17', Misconceptions::COMBINE_UNLIKE],
            'sign slip' => ['2x = -6', 'x = 3', Misconceptions::SIGN_SLIP],
            'arithmetic' => ['3x + 7 = 31', '3x = 25', Misconceptions::ARITHMETIC],
        ];
    }

    #[DataProvider('mistakes')]
    public function test_diagnoses_the_misconception(string $prev, string $line, string $code): void
    {
        $found = Diagnoser::diagnose(Equation::parse($prev), Equation::parse($line));
        $this->assertSame($code, $found[0]['code'], json_encode($found));
    }

    public function test_says_when_there_is_not_enough_evidence(): void
    {
        $found = Diagnoser::diagnose(Equation::parse('3x + 7 = 31'), Equation::parse('40x = 2'));
        $this->assertSame(Misconceptions::UNKNOWN, $found[0]['code']);
    }

    public function test_worked_steps_reach_the_answer(): void
    {
        foreach (array_column(self::equations(), 0) as $text) {
            $eq = Equation::parse($text);
            if ($eq->solution()['type'] !== Equation::ONE) {
                continue;
            }
            $steps = Solver::steps($eq);
            $last = $steps ? end($steps)['line'] : $eq;
            $this->assertTrue($last->isFinal(), "$text ended at ".$last->format());
            $this->assertLessThanOrEqual(5, count($steps), $text);
            foreach ($steps as $st) {
                $this->assertTrue($st['line']->sameSolutionAs($eq), $text.': '.$st['say']);
            }
        }
        $steps = Solver::steps(Equation::parse('2(3x - 4) = 10'));
        $this->assertSame(['expand', 'move_const', 'divide'], array_column($steps, 'kind'));
        $this->assertSame('6x − 8 = 10', $steps[0]['line']->format());
        $this->assertSame('x = 3', end($steps)['line']->format());
    }

    public function test_generated_questions_are_valid_and_whole_number(): void
    {
        $g = new Generator(42);
        foreach (Skills::equationSkills() as $skill) {
            for ($i = 0; $i < 200; $i++) {
                $q = $g->equation($skill);
                $again = Equation::parse($q['text']);
                $sol = $again->solution();
                $this->assertSame(Equation::ONE, $sol['type'], $q['text']);
                $this->assertTrue($sol['x']->isInteger(), $q['text']);
                $this->assertSame($skill, Skills::classify($again), $q['text']);
                $this->assertStringNotContainsString('+ 0', $q['text']);
            }
        }
    }

    public function test_quick_checks_mark_typed_answers(): void
    {
        $this->assertTrue(Generator::markCheck(['kind' => 'expression', 'answer' => '3x + 15'], '3x+15')['correct']);
        $this->assertTrue(Generator::markCheck(['kind' => 'expression', 'answer' => '3x + 15'], '15 + 3x')['correct']);
        $this->assertFalse(Generator::markCheck(['kind' => 'expression', 'answer' => '3x + 15'], '3(x+5)')['correct']);
        $this->assertFalse(Generator::markCheck(['kind' => 'expression', 'answer' => '3x + 15'], '3x + 5')['correct']);
        $this->assertTrue(Generator::markCheck(['kind' => 'number', 'answer' => '−3'], '-3')['correct']);
        $this->assertTrue(Generator::markCheck(['kind' => 'equation', 'answer' => '−4'], 'x = -4')['correct']);
        $this->assertTrue(Generator::markCheck(['kind' => 'equation', 'answer' => '−4'], '-4')['correct']);
        $g = new Generator(7);
        foreach (['INT.ADDSUB', 'INT.MULDIV', 'ALG.LIKE_TERMS', 'ALG.DISTRIBUTIVE', 'EQ.ONE_STEP'] as $skill) {
            for ($i = 0; $i < 50; $i++) {
                $c = $g->check($skill);
                $this->assertNotSame('', $c['answer']);
            }
        }
    }
}

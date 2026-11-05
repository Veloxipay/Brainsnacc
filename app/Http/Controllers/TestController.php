<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\QuestionAttempt;
use App\Models\TestAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chapter tests: 15 questions, done alone with no hints, marked at the end.
 * The result shows the score for each topic in the chapter and which lessons to revise.
 */
class TestController extends Controller
{
    public function show(Request $request, Lesson $lesson): View
    {
        $this->testOnly($request, $lesson);
        $lesson->load(['module.unit.subject', 'module.unit.level', 'module.lessons', 'questions']);

        return view('student.test', [
            'lesson' => $lesson,
            'subject' => $lesson->module->unit->subject,
            'past' => TestAttempt::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)->latest('id')->get(),
            'started' => now()->timestamp,
        ]);
    }

    public function submit(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->testOnly($request, $lesson);
        $given = (array) $request->input('answers', []);
        $user = $request->user();
        $score = 0;
        $byTopic = [];
        $answers = [];
        foreach ($lesson->questions as $q) {
            $a = isset($given[$q->id]) && array_key_exists($given[$q->id], $q->options ?? []) ? $given[$q->id] : null;
            $right = $a !== null && $q->isCorrect($a);
            $score += (int) $right;
            $answers[$q->id] = $a;
            $topic = $q->topic ?: 'Other';
            $byTopic[$topic] ??= [0, 0];
            $byTopic[$topic][0] += (int) $right;
            $byTopic[$topic][1]++;
            if ($a !== null) {
                QuestionAttempt::create(['user_id' => $user->id, 'question_id' => $q->id, 'answer' => $a, 'is_correct' => $right, 'help_used' => 0]);
            }
        }
        $started = (int) $request->input('started');
        $attempt = TestAttempt::create([
            'user_id' => $user->id, 'lesson_id' => $lesson->id, 'score' => $score, 'total' => $lesson->questions->count(),
            'answers' => $answers, 'by_topic' => $byTopic,
            'seconds' => $started > 0 && $started <= now()->timestamp ? min(86400, now()->timestamp - $started) : null,
        ]);
        $user->completedLessons()->syncWithoutDetaching([$lesson->id => ['completed_at' => now()]]);

        return redirect()->route('tests.result', [$lesson, $attempt]);
    }

    public function result(Request $request, Lesson $lesson, TestAttempt $attempt): View
    {
        abort_unless($attempt->user_id === $request->user()->id && $attempt->lesson_id === $lesson->id, 403);
        $lesson->load(['module.unit.subject', 'module.lessons', 'questions']);
        $lessonsByTitle = $lesson->module->lessons->keyBy('title');
        $nextTest = $lesson->module->lessons->where('kind', 'test')->where('sort', '>', $lesson->sort)->first(fn ($l) => $l->isPublished());

        return view('student.test-result', [
            'lesson' => $lesson,
            'attempt' => $attempt,
            'subject' => $lesson->module->unit->subject,
            'topics' => collect($attempt->by_topic)->map(fn ($v, $t) => ['topic' => $t, 'right' => $v[0], 'of' => $v[1], 'lesson' => $lessonsByTitle->get($t)])
                ->sortBy(fn ($r) => $r['right'] / max(1, $r['of']))->values(),
            'nextTest' => $nextTest,
        ]);
    }

    private function testOnly(Request $request, Lesson $lesson): void
    {
        abort_unless($lesson->isTest(), 404);
        abort_unless($lesson->isPublished() || $request->user()->isAdmin(), 404);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string'],
            'options' => ['required', 'array'],
            'options.A' => ['required', 'string'],
            'options.B' => ['required', 'string'],
            'options.C' => ['nullable', 'string'],
            'options.D' => ['nullable', 'string'],
            'answer' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'explanation' => ['nullable', 'string'],
        ]);

        $options = array_filter($data['options'], fn ($text) => filled($text));

        if (! array_key_exists($data['answer'], $options)) {
            return back()->withErrors(['answer' => 'The correct answer must be one of the options you filled in.'])->withInput();
        }

        $lesson->questions()->create([
            'type' => 'mcq',
            'prompt' => $data['prompt'],
            'options' => $options,
            'answer' => $data['answer'],
            'explanation' => $data['explanation'] ?? null,
            'sort' => $lesson->questions()->max('sort') + 1,
        ]);

        $lesson->update(['content_hash' => null]); // hand-edited: keep it

        return redirect()->to(route('admin.lessons.edit', $lesson).'#questions')->with('status', 'Question added.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $lessonId = $question->lesson_id;
        $question->delete();
        $question->lesson()->update(['content_hash' => null]); // hand-edited: keep it

        return redirect()->to(route('admin.lessons.edit', $lessonId).'#questions')->with('status', 'Question removed.');
    }
}

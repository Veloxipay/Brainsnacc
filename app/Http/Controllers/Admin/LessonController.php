<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function index(): View
    {
        $levels = Level::orderBy('sort')
            ->with(['units' => fn ($q) => $q->with(['subject', 'modules.lessons'])])
            ->get();

        return view('admin.lessons.index', ['levels' => $levels]);
    }

    public function create(Request $request): View
    {
        return view('admin.lessons.form', [
            'lesson' => new Lesson([
                'module_id' => $request->integer('module') ?: null,
                'minutes' => 10,
                'status' => 'draft',
            ]),
            'modules' => $this->moduleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort'] = Lesson::where('module_id', $data['module_id'])->max('sort') + 1;

        $lesson = Lesson::create($data);

        return redirect()->route('admin.lessons.edit', $lesson)->with('status', 'Lesson created. Add questions below.');
    }

    public function edit(Lesson $lesson): View
    {
        $lesson->load('questions');

        return view('admin.lessons.form', [
            'lesson' => $lesson,
            'modules' => $this->moduleOptions(),
        ]);
    }

    public function update(Request $request, Lesson $lesson): RedirectResponse
    {
        // A hand edit means lesson files no longer replace this lesson.
        $lesson->update($this->validated($request) + ['content_hash' => null]);

        return redirect()->route('admin.lessons.edit', $lesson)->with('status', 'Lesson saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'module_id' => ['required', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);
    }

    /** "Year 12 · Chemistry · Unit 3 · Energy …" labels for the module picker. */
    private function moduleOptions()
    {
        return Module::with('unit.level', 'unit.subject')->get()
            ->sortBy(fn ($m) => [$m->unit->level->sort, $m->unit->subject->name, $m->unit->sort, $m->sort])
            ->mapWithKeys(fn ($m) => [
                $m->id => $m->unit->level->short_name.' · '.$m->unit->subject->name.' · '.$m->unit->code.' · '.$m->title,
            ]);
    }
}

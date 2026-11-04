<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Module;
use App\Models\Subject;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Add and rename levels' units and modules (the course skeleton). */
class StructureController extends Controller
{
    public function index(): View
    {
        return view('admin.structure', [
            'levels' => Level::orderBy('sort')->with(['units' => fn ($q) => $q->with(['subject', 'modules' => fn ($m) => $m->withCount('lessons')])])->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }

    public function storeUnit(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
        ]);
        $data['sort'] = Unit::max('sort') + 1;

        Unit::create($data);

        return back()->with('status', 'Unit added.');
    }

    public function updateUnit(Request $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validate([
            'code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'sort' => ['required', 'integer', 'min:0'],
        ]));

        return back()->with('status', 'Unit saved.');
    }

    public function storeModule(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:255']]);

        $unit->modules()->create([
            'title' => $data['title'],
            'sort' => $unit->modules()->max('sort') + 1,
        ]);

        return back()->with('status', 'Module added to '.$unit->code.'.');
    }

    public function updateModule(Request $request, Module $module): RedirectResponse
    {
        $module->update($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort' => ['required', 'integer', 'min:0'],
        ]));

        return back()->with('status', 'Module saved.');
    }
}

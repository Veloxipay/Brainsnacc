<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Subject;
use App\Support\Progress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function show(Request $request, Subject $subject): View
    {
        $student = $request->user();
        $levels = Level::orderBy('sort')->get();

        $level = $levels->firstWhere('id', (int) $request->query('level'))
            ?? $student->level
            ?? $levels->first();

        $units = $subject->units()
            ->where('level_id', $level?->id)
            ->with('modules.lessons') // drafts are listed as "Coming soon"
            ->get();

        return view('student.course', [
            'subject' => $subject,
            'subjects' => Subject::orderBy('name')->get(),
            'levels' => $levels,
            'level' => $level,
            'units' => $units,
            'done' => Progress::completedIds($student),
        ]);
    }
}

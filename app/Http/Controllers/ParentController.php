<?php

namespace App\Http\Controllers;

use App\Models\QuestionAttempt;
use App\Models\User;
use App\Support\Progress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // A parent sees their own children; the admin sees every student.
        $children = $user->isAdmin()
            ? User::where('role', User::ROLE_STUDENT)->orderBy('name')->get()
            : $user->children()->orderBy('name')->get();

        $since = now()->startOfWeek(\Carbon\CarbonInterface::MONDAY);

        $rows = $children->map(fn (User $child) => [
            'child' => $child,
            'lessonsThisWeek' => $child->completedLessons()->wherePivot('completed_at', '>=', $since)->count(),
            'questionsThisWeek' => QuestionAttempt::where('user_id', $child->id)->where('created_at', '>=', $since)->count(),
            'daysThisWeek' => QuestionAttempt::where('user_id', $child->id)->where('created_at', '>=', $since)
                ->get()->groupBy(fn ($a) => $a->created_at->toDateString())->count(),
            'accuracy' => Progress::accuracy($child, null, $since),
            'weak' => Progress::weakLessons($child),
        ]);

        return view('parent.index', ['rows' => $rows, 'since' => $since]);
    }
}

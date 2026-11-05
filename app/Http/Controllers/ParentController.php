<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\MockExamAttempt;
use App\Models\QuestionAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Support\MockExams;
use App\Support\Progress;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Parents and teachers: add student accounts under their own login and follow each
 * student's progress. The admin can see every student.
 */
class ParentController extends Controller
{
    private const MESSAGES = [
        'username.unique' => 'That username is taken. Try adding a number, e.g. mahek2.',
        'username.regex' => 'Usernames can only use lower-case letters, numbers, dots, dashes and underscores (no spaces).',
        'email.unique' => 'That email already has an account.',
    ];

    private function students(User $user)
    {
        return $user->isAdmin()
            ? User::where('role', User::ROLE_STUDENT)->with('level')->orderBy('name')->get()
            : $user->students()->with('level')->orderBy('name')->get();
    }

    private function ownStudent(Request $request, User $student): User
    {
        $me = $request->user();
        abort_unless($student->isStudent() && ($me->isAdmin() || $me->students()->whereKey($student->id)->exists()), 404);

        return $student;
    }

    public function index(Request $request): View
    {
        $since = now()->startOfWeek(CarbonInterface::MONDAY);
        $subjects = Subject::orderBy('name')->get();

        $rows = $this->students($request->user())->map(function (User $s) use ($since, $subjects) {
            $done = Progress::completedIds($s);
            $total = $subjects->sum(fn ($sub) => Progress::lessonsFor($sub, $s->level)->count());
            $last = QuestionAttempt::where('user_id', $s->id)->latest('id')->first()?->created_at;

            return [
                'student' => $s,
                'lessonsDone' => $done->count(),
                'lessonsTotal' => $total,
                'percent' => $total ? (int) round(min($done->count(), $total) / $total * 100) : 0,
                'lessonsThisWeek' => $s->completedLessons()->wherePivot('completed_at', '>=', $since)->count(),
                'questionsThisWeek' => QuestionAttempt::where('user_id', $s->id)->where('created_at', '>=', $since)->count(),
                'accuracy' => Progress::accuracy($s),
                'lastActive' => $last,
                'weak' => Progress::weakLessons($s, 2),
            ];
        });

        return view('parent.index', ['rows' => $rows, 'since' => $since, 'me' => $request->user()]);
    }

    public function create(): View
    {
        return view('parent.create', ['levels' => Level::orderBy('sort')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => array_merge(User::usernameRules(), ['required']),
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'level_id' => ['required', 'exists:levels,id'],
        ], self::MESSAGES);

        $student = User::create($data + ['role' => User::ROLE_STUDENT]);
        $me = $request->user();
        if (! $me->isAdmin()) {
            $me->students()->attach($student->id);
        }

        return redirect()->route('family')->with('status', "Account created for {$student->name}. They log in with the username “{$student->username}” and the password you set.");
    }

    public function show(Request $request, User $student): View
    {
        $student = $this->ownStudent($request, $student);
        $done = Progress::completedIds($student);
        $subjects = Progress::subjectsAt($student->level)->map(function (Subject $sub) use ($student, $done) {
            $lessons = Progress::lessonsFor($sub, $student->level);

            return [
                'subject' => $sub,
                'total' => $lessons->count(),
                'done' => $lessons->whereIn('id', $done)->count(),
                'next' => $lessons->first(fn ($l) => ! $done->contains($l->id)),
            ];
        });

        $recent = $student->completedLessons()->orderByPivot('completed_at', 'desc')->limit(8)->get();
        $days = QuestionAttempt::where('user_id', $student->id)->where('created_at', '>=', now()->subDays(27)->startOfDay())
            ->get()->groupBy(fn ($a) => $a->created_at->timezone('Australia/Melbourne')->toDateString())->map->count();
        $exams = MockExamAttempt::where('user_id', $student->id)->whereNotNull('submitted_at')->latest('id')->get()
            ->map(fn ($a) => ['attempt' => $a, 'exam' => MockExams::find($a->exam)])->filter(fn ($r) => $r['exam']);

        return view('parent.show', [
            'student' => $student,
            'subjects' => $subjects,
            'accuracy' => Progress::accuracy($student),
            'weak' => Progress::weakLessons($student, 6),
            'recent' => $recent,
            'days' => $days,
            'exams' => $exams,
            'levels' => Level::orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        $student = $this->ownStudent($request, $student);
        $request->merge(['username' => strtolower(trim((string) $request->input('username'))) ?: null]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => User::usernameRules($student->id),
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'level_id' => ['required', 'exists:levels,id'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], self::MESSAGES);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if (empty($data['username']) && empty($data['email'])) {
            return back()->withErrors(['username' => 'Give the student a username or an email, so they can log in.'])->withInput();
        }
        $student->update($data);

        return redirect()->route('family.students.show', $student)->with('status', 'Saved.');
    }

    public function destroy(Request $request, User $student): RedirectResponse
    {
        $student = $this->ownStudent($request, $student);
        $request->user()->students()->detach($student->id);

        return redirect()->route('family')->with('status', "{$student->name} is no longer linked to your account. Their account and progress are kept.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\HelpRequest;
use App\Models\Homework;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\SessionNote;
use App\Models\TutorSession;
use App\Models\MockExamAttempt;
use App\Models\QuestionAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Support\MockExams;
use App\Support\Progress;
use App\Support\Teaching;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $students = $this->students($request->user());
        $ids = $students->pluck('id')->all();

        // A handful of grouped queries for the whole list, so the page stays quick with many students.
        $doneAll = DB::table('lesson_progress')->whereIn('user_id', $ids)
            ->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id');
        $doneWeek = DB::table('lesson_progress')->whereIn('user_id', $ids)->where('completed_at', '>=', $since)
            ->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id');
        $answers = QuestionAttempt::whereIn('user_id', $ids)
            ->selectRaw('user_id, count(*) as total, sum(case when is_correct then 1 else 0 end) as right_answers, sum(case when created_at >= ? then 1 else 0 end) as week, max(created_at) as last_at', [$since])
            ->groupBy('user_id')->get()->keyBy('user_id');
        $weakRows = DB::table('question_attempts')->join('questions', 'questions.id', '=', 'question_attempts.question_id')
            ->whereIn('question_attempts.user_id', $ids)
            ->selectRaw('question_attempts.user_id as user_id, questions.lesson_id as lesson_id, count(*) as total, sum(case when question_attempts.is_correct then 1 else 0 end) as right_answers')
            ->groupBy('question_attempts.user_id', 'questions.lesson_id')
            ->havingRaw('count(*) >= 3')->get()
            ->each(function ($r) {
                $r->score = (int) round($r->right_answers / $r->total * 100);
            })
            ->filter(fn ($r) => $r->score < 60);
        $weakLessons = Lesson::whereIn('id', $weakRows->pluck('lesson_id')->unique())->get()->keyBy('id');
        $totals = [];

        $stats = [];
        foreach ($students as $s) {
            $a = $answers[$s->id] ?? null;
            $stats[$s->id] = [
                'accuracy' => $a && $a->total ? (int) round($a->right_answers / $a->total * 100) : null,
                'lastActive' => $a?->last_at ? Carbon::parse($a->last_at) : null,
            ];
        }
        $attention = Teaching::attention($students, $stats);

        $rows = $students->map(function (User $s) use ($subjects, $doneAll, $doneWeek, $answers, $weakRows, $weakLessons, &$totals, $stats, $attention) {
            $levelKey = $s->level_id ?? 0;
            $totals[$levelKey] ??= $subjects->sum(fn ($sub) => Progress::lessonsFor($sub, $s->level)->count());
            $total = $totals[$levelKey];
            $done = (int) ($doneAll[$s->id] ?? 0);

            return [
                'student' => $s,
                'lessonsDone' => $done,
                'lessonsTotal' => $total,
                'percent' => $total ? (int) round(min($done, $total) / $total * 100) : 0,
                'lessonsThisWeek' => (int) ($doneWeek[$s->id] ?? 0),
                'questionsThisWeek' => (int) ($answers[$s->id]->week ?? 0),
                'accuracy' => $stats[$s->id]['accuracy'],
                'lastActive' => $stats[$s->id]['lastActive'],
                'weak' => $weakRows->where('user_id', $s->id)->sortBy('score')->take(2)
                    ->map(fn ($r) => ['lesson' => $weakLessons[$r->lesson_id] ?? null, 'score' => $r->score])
                    ->filter(fn ($w) => $w['lesson'])->values(),
                'attention' => $attention[$s->id] ?? ['score' => 0, 'reasons' => []],
            ];
        })
            // Students who need help first, then by name.
            ->sortBy(fn ($r) => [-$r['attention']['score'], $r['student']->name])->values();

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
            'unaided' => Progress::accuracy($student, null, null, true),
            'skillRecords' => \App\Models\SkillRecord::where('user_id', $student->id)->get(),
            'tests' => \App\Models\TestAttempt::where('user_id', $student->id)->with('lesson.module.unit.level')->latest('id')->limit(10)->get(),
            'weak' => Progress::weakLessons($student, 6),
            'recent' => $recent,
            'days' => $days,
            'exams' => $exams,
            'levels' => Level::orderBy('sort')->get(),
            // Teaching
            'summary' => Teaching::sinceLastSession($student),
            'homework' => Homework::where('student_id', $student->id)->with(['lesson', 'setter'])
                ->orderByRaw('completed_at is not null')->latest('id')->limit(30)->get(),
            'homeworkChoices' => $subjects->mapWithKeys(fn ($row) => [
                $row['subject']->name => Progress::lessonsFor($row['subject'], $student->level),
            ])->filter(fn ($lessons) => $lessons->isNotEmpty()),
            'notes' => SessionNote::where('student_id', $student->id)->with('author')
                ->orderByDesc('taught_on')->orderByDesc('id')->limit(20)->get(),
            'questions' => $this->openQuestions($student),
            'tutorSessions' => TutorSession::where('user_id', $student->id)->with('lines')->latest('id')->limit(5)->get(),
        ]);
    }

    /** The student's "Ask my tutor" questions, with the answer they last gave. */
    private function openQuestions(User $student)
    {
        $requests = HelpRequest::where('student_id', $student->id)->open()
            ->with(['question.lesson', 'tutorSession.lines'])->oldest('id')->get();
        $lastAnswers = QuestionAttempt::where('user_id', $student->id)
            ->whereIn('question_id', $requests->pluck('question_id')->filter())
            ->orderBy('id')->get()->keyBy('question_id');

        return $requests->map(fn (HelpRequest $r) => ['request' => $r, 'last' => $r->question_id ? ($lastAnswers[$r->question_id] ?? null) : null]);
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

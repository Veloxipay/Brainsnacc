<?php

namespace App\Http\Controllers;

use App\Models\SkillRecord;
use App\Models\TutorCheck;
use App\Models\TutorSession;
use App\Tutor\Mastery;
use App\Tutor\PhotoReader;
use App\Tutor\Skills;
use App\Tutor\Tutor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The Maths tutor: bring a homework equation (or pick a skill), work it line by line, prove it on your own. */
class TutorController extends Controller
{
    public function __construct(private readonly Tutor $tutor) {}

    public function home(Request $request): View
    {
        $user = $request->user();
        $records = SkillRecord::where('user_id', $user->id)->get()->keyBy('skill_code');

        return view('tutor.home', [
            'skills' => collect(Skills::all())->filter(fn ($s) => $s['kind'] === 'equation'),
            'records' => $records,
            'continue' => TutorSession::where('user_id', $user->id)->where('status', 'working')->latest('updated_at')->first(),
            'recent' => TutorSession::where('user_id', $user->id)->latest('id')->limit(5)->get(),
        ]);
    }

    /** Step 1: read the typed question and show how it was read, for the student to confirm. */
    public function read(Request $request): RedirectResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:200']], ['question.required' => 'Type your question first.']);
        $read = Tutor::read($data['question']);
        if (! $read['ok']) {
            return back()->withInput()->with('read_error', $read['error'])->with('read_position', $read['position'] ?? null);
        }

        return redirect()->route('tutor.confirm')->with('pending', [
            'typed' => $data['question'], 'read_as' => $read['read_as'], 'skill' => $read['skill'], 'source' => $request->input('source', 'homework'),
        ]);
    }

    public function confirm(Request $request): View|RedirectResponse
    {
        $pending = session('pending');
        if (! $pending) {
            return redirect()->route('tutor.home');
        }
        session()->keep('pending');

        return view('tutor.confirm', ['pending' => $pending, 'skill' => Skills::get($pending['skill'])]);
    }

    /** Step 2: the student said "yes, that's my question". */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'read_as' => ['required', 'string', 'max:200'],
            'source' => ['nullable', Rule::in(['homework', 'photo', 'lesson'])],
        ]);
        $read = Tutor::read($data['read_as']);
        abort_unless($read['ok'], 422);
        $session = $this->tutor->start($request->user(), $read['read_as'], $data['source'] ?? 'homework');

        return redirect()->route('tutor.show', $session);
    }

    public function practice(Request $request, string $skill): RedirectResponse
    {
        abort_unless(in_array($skill, Skills::equationSkills(), true), 404);
        $mode = $request->query('mode') === 'independent' ? 'independent' : 'guided';
        $session = $this->tutor->practice($request->user(), $skill, $mode);

        return redirect()->route('tutor.show', $session);
    }

    public function show(Request $request, TutorSession $session): View
    {
        $this->own($request, $session);

        return view('tutor.session', [
            's' => $session,
            'feed' => $this->tutor->feed($session),
            'skill' => Skills::get($session->skill_code),
            'openCheck' => $session->checks()->whereNull('correct')->first(),
            'record' => Mastery::record($session->user, $session->skill_code),
        ]);
    }

    public function lines(Request $request, TutorSession $session): RedirectResponse
    {
        $this->own($request, $session);
        $data = $request->validate(['working' => ['required', 'string', 'max:1500']], ['working.required' => 'Type a line of working first.']);
        if ($session->isSolved()) {
            return redirect()->route('tutor.show', $session);
        }
        $this->tutor->submit($session, $data['working']);

        return redirect()->to(route('tutor.show', $session).'#latest');
    }

    public function help(Request $request, TutorSession $session): RedirectResponse
    {
        $this->own($request, $session);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['hint', 'step', 'example', 'explain', 'ask', 'stuck'])],
            'message' => ['nullable', 'required_if:kind,ask', 'string', 'max:500'],
        ], ['message.required_if' => 'Type your question first.']);
        // On your own: only "I'm stuck" is offered; it turns the attempt into a guided one.
        if ($session->isIndependent() && $data['kind'] !== 'stuck') {
            $data['kind'] = 'stuck';
        }
        $this->tutor->help($session, $data['kind'], $data['message'] ?? null);

        return redirect()->to(route('tutor.show', $session).'#latest');
    }

    /** After solving: a fresh question of the same kind, to do with no help. */
    public function next(Request $request, TutorSession $session): RedirectResponse
    {
        $this->own($request, $session);
        $new = $this->tutor->practice($request->user(), $session->skill_code, 'independent', $session);

        return redirect()->route('tutor.show', $new);
    }

    public function check(Request $request, TutorCheck $check): RedirectResponse
    {
        abort_unless($check->user_id === $request->user()->id, 403);
        $data = $request->validate(['answer' => ['required', 'string', 'max:100']], ['answer.required' => 'Type your answer first.']);
        if (! $check->isOpen()) {
            return redirect()->route('tutor.show', $check->tutor_session_id);
        }
        $result = $this->tutor->answerCheck($check, $data['answer']);

        return redirect()->to(route('tutor.show', $check->tutor_session_id).'#latest')
            ->with('check_result', $result + ['id' => $check->id]);
    }

    public function photo(PhotoReader $reader): View
    {
        return view('tutor.photo', ['enabled' => $reader->enabled(), 'found' => session('photo_questions')]);
    }

    /** Read the questions off a photo or PDF. The file is never stored — only what the student confirms. */
    public function readPhoto(Request $request, PhotoReader $reader): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:'.config('tutor.photo_max_kb')],
        ], [
            'photo.required' => 'Choose a photo first.',
            'photo.mimes' => 'Use a photo (JPG, PNG, WEBP) or a PDF.',
            'photo.max' => 'That file is too big. Try a smaller photo.',
        ]);
        if (! $reader->enabled()) {
            return back()->withErrors(['photo' => 'Reading photos isn\'t switched on yet. Type the question instead.']);
        }
        $file = $request->file('photo');
        $mime = $file->getMimeType() === 'application/pdf' ? 'application/pdf' : ($file->getMimeType() ?: 'image/jpeg');
        $result = $reader->read(file_get_contents($file->getRealPath()), $mime, $request->user());
        @unlink($file->getRealPath()); // not kept

        if (! $result['ok']) {
            return back()->withErrors(['photo' => $result['error']]);
        }

        session(['photo_questions' => $result['questions']]); // kept until the next photo, so the student can try each question

        return redirect()->route('tutor.photo');
    }

    public function progress(Request $request): View
    {
        $user = $request->user();

        return view('tutor.progress', [
            'skills' => Skills::all(),
            'records' => SkillRecord::where('user_id', $user->id)->get()->keyBy('skill_code'),
        ]);
    }

    /** Students only ever see their own sessions. */
    private function own(Request $request, TutorSession $session): void
    {
        abort_unless($session->user_id === $request->user()->id, 403);
    }
}

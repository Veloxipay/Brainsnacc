@extends('layouts.app')
@section('title', $student->name)

@section('content')
    <div class="row small muted"><a href="{{ route('family') }}" style="font-weight:600">← All students</a></div>
    <header class="row between">
        <div>
            <h1 style="margin:0">{{ $student->name }}</h1>
            <p class="muted" style="margin:0">{{ $student->level?->name ?? 'No year level set' }} · logs in with <strong>{{ $student->loginName() }}</strong></p>
        </div>
        <div class="score-big">
            <span class="small muted">Average score</span>
            <strong>{{ $accuracy !== null ? $accuracy.'%' : '—' }}</strong>
            @if ($unaided !== null)<span class="small muted">Without hints: {{ $unaided }}%</span>@endif
        </div>
    </header>

    {{-- Before the session: what happened since the last one --}}
    @php($sum = $summary)
    <section class="card" aria-labelledby="since-h">
        <h2 id="since-h" style="margin:0;font-size:20px">{{ $sum['lastNote'] ? 'Since the last session ('.$sum['lastNote']->taught_on->format('D j M').')' : 'The last 7 days' }}</h2>
        <div class="since-grid">
            <div><strong>{{ $sum['lessons']->count() }}</strong><span class="small muted">{{ \Illuminate\Support\Str::plural('lesson', $sum['lessons']->count()) }} finished</span></div>
            <div><strong>{{ $sum['answered'] }}</strong><span class="small muted">questions answered{{ $sum['accuracy'] !== null ? ' · '.$sum['accuracy'].'% right' : '' }}</span></div>
            <div><strong>{{ $sum['homeworkDone'] }}</strong><span class="small muted">homework handed in</span></div>
            <div><strong class="{{ $sum['homeworkOverdue'] ? 'bad' : '' }}">{{ $sum['homeworkOverdue'] }}</strong><span class="small muted">homework overdue</span></div>
            <div><strong class="{{ $sum['openQuestions'] ? 'bad' : '' }}">{{ $sum['openQuestions'] }}</strong><span class="small muted">questions to go through</span></div>
        </div>
        @if ($sum['lessons']->isNotEmpty())
            <div class="small"><span class="muted">Finished:</span> {{ $sum['lessons']->pluck('title')->join(', ') }}</div>
        @endif
        @foreach ($sum['tests'] as $t)
            <div class="small"><span class="muted">Test:</span> {{ $t->lesson?->title }} — <span class="score-pill {{ $t->percent() >= 75 ? 'good' : ($t->percent() >= 60 ? 'ok' : 'low') }}">{{ $t->score }}/{{ $t->total }}</span></div>
        @endforeach
    </section>

    {{-- "Ask my tutor" --}}
    <section class="card" id="questions" aria-labelledby="q-h">
        <h2 id="q-h" style="margin:0;font-size:20px">Questions to go through</h2>
        @forelse ($questions as $row)
            @php($r = $row['request'])
            <div class="ask-item">
                <div class="small muted">Asked {{ $r->created_at->diffForHumans() }}</div>
                @if ($r->message)<div class="tutor-says">“{{ $r->message }}”</div>@endif
                @if ($r->question)
                    @php($q = $r->question)
                    <div><strong>{{ $q->lesson?->title }}</strong></div>
                    <p style="margin:0;font-weight:600">{{ $q->prompt }}</p>
                    <ul class="small opt-list">
                        @foreach ($q->options ?? [] as $letter => $text)
                            <li class="{{ $letter === $q->answer ? 'good' : ($row['last'] && $row['last']->answer === $letter ? 'bad' : '') }}">
                                <strong>{{ $letter }}</strong> {{ $text }}
                                @if ($letter === $q->answer) · correct answer @endif
                                @if ($row['last'] && $row['last']->answer === $letter) · {{ $student->name }}’s last answer @endif
                            </li>
                        @endforeach
                    </ul>
                    @if (! empty($q->steps))
                        <details class="small"><summary>Worked steps</summary><ol class="steps-list">@foreach ($q->steps as $step)<li>{{ $step }}</li>@endforeach</ol></details>
                    @endif
                @elseif ($r->tutorSession)
                    @include('parent._working', ['session' => $r->tutorSession])
                @endif
                <form method="POST" action="{{ route('family.help.resolve', $r) }}">
                    @csrf
                    <button class="btn secondary small" type="submit">Gone through it ✓</button>
                </form>
            </div>
        @empty
            <p class="muted small" style="margin:0">Nothing yet. When {{ $student->name }} taps “Ask my tutor” on a question, it shows up here.</p>
        @endforelse
    </section>

    {{-- Homework --}}
    <section class="card" id="homework" aria-labelledby="hw-h">
        <h2 id="hw-h" style="margin:0;font-size:20px">Homework</h2>
        @if ($homework->isNotEmpty())
            <ul class="hw-list">
                @foreach ($homework as $h)
                    <li class="{{ $h->isDone() ? 'done' : ($h->isOverdue() ? 'overdue' : '') }}">
                        <div style="flex:1 1 220px">
                            <strong>{{ $h->lesson?->isTest() ? 'Test: ' : '' }}{{ $h->lesson?->title }}</strong>
                            <div class="small muted">Set {{ $h->created_at->format('j M') }}{{ $h->setter ? ' by '.$h->setter->name : '' }}{{ $h->note ? ' · “'.$h->note.'”' : '' }}</div>
                        </div>
                        <span class="small">
                            @if ($h->isDone())
                                <span class="good">Handed in {{ $h->completed_at->format('D j M') }}</span>
                            @elseif ($h->isOverdue())
                                <span class="bad">Overdue (due {{ $h->due_on->format('D j M') }})</span>
                            @elseif ($h->due_on)
                                Due {{ $h->due_on->format('D j M') }}
                            @else
                                <span class="muted">No due date</span>
                            @endif
                        </span>
                        @unless ($h->isDone())
                            <form method="POST" action="{{ route('family.homework.destroy', $h) }}" onsubmit="return confirm('Remove this homework?')">
                                @csrf @method('DELETE')
                                <button class="linklike small" type="submit">Remove</button>
                            </form>
                        @endunless
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($homeworkChoices->isEmpty())
            <p class="muted small" style="margin:0">There are no published lessons at {{ $student->name }}’s year level yet.</p>
        @else
            <form method="POST" action="{{ route('family.homework.store', $student) }}" class="teach-form">
                @csrf
                <label>Lesson or chapter test
                    <select name="lesson_id" required>
                        <option value="">Choose…</option>
                        @foreach ($homeworkChoices as $subjectName => $lessons)
                            <optgroup label="{{ $subjectName }}">
                                @foreach ($lessons as $l)
                                    <option value="{{ $l->id }}" @selected(old('lesson_id') == $l->id)>{{ $l->module->unit->code }} · {{ $l->isTest() ? 'Test: ' : '' }}{{ $l->title }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>
                <label>Due date <span class="small muted">(optional)</span> <input type="date" name="due_on" value="{{ old('due_on') }}" min="{{ now()->toDateString() }}"></label>
                <label class="wide">Note for {{ $student->name }} <span class="small muted">(optional)</span> <input name="note" maxlength="1000" value="{{ old('note') }}" placeholder="e.g. Do the hard questions too"></label>
                <div><button class="btn" type="submit">Set homework</button></div>
            </form>
        @endif
    </section>

    {{-- Session notes --}}
    <section class="card" id="notes" aria-labelledby="notes-h">
        <h2 id="notes-h" style="margin:0;font-size:20px">Session notes</h2>
        <form method="POST" action="{{ route('family.notes.store', $student) }}" class="teach-form">
            @csrf
            <label>Session date <input type="date" name="taught_on" value="{{ old('taught_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></label>
            <label class="wide">What you covered, and what to work on next
                <textarea name="body" rows="3" maxlength="3000" required placeholder="e.g. Solving two-step equations. Good with whole numbers, struggles with negatives. Revise 3B before next week.">{{ old('body') }}</textarea>
            </label>
            <div><button class="btn" type="submit">Save note</button></div>
        </form>
        <p class="small muted" style="margin:0">Everyone who looks after {{ $student->name }} (parents and tutors) can read these notes.</p>
        @foreach ($notes as $n)
            <article class="note">
                <div class="row between small">
                    <strong>{{ $n->taught_on->format('l j M Y') }}</strong>
                    <span class="muted">{{ $n->author?->name ?? 'Former tutor' }}</span>
                </div>
                <div style="white-space:pre-line">{{ $n->body }}</div>
                @if (auth()->user()->isAdmin() || $n->author_id === auth()->id())
                    <form method="POST" action="{{ route('family.notes.destroy', $n) }}" onsubmit="return confirm('Delete this note?')">
                        @csrf @method('DELETE')
                        <button class="linklike small" type="submit">Delete</button>
                    </form>
                @endif
            </article>
        @endforeach
    </section>

    {{-- The student's own working in the Maths tutor --}}
    @if ($tutorSessions->isNotEmpty())
        <section class="card" aria-labelledby="work-h">
            <h2 id="work-h" style="margin:0;font-size:20px">Recent working in the Maths tutor</h2>
            <p class="small muted" style="margin:0">Every line {{ $student->name }} typed. Wrong lines are in red, with what the tutor found.</p>
            @foreach ($tutorSessions as $ts)
                <details class="working-item" @if ($loop->first) open @endif>
                    <summary><strong>{{ $ts->question }}</strong> <span class="small muted">· {{ $ts->created_at->diffForHumans() }} · {{ $ts->status === 'solved' ? ($ts->help_used ? 'solved with help' : 'solved on their own') : ($ts->status === 'left' ? 'left unfinished' : 'still working') }}</span></summary>
                    @include('parent._working', ['session' => $ts])
                </details>
            @endforeach
        </section>
    @endif

    <div class="grid-2">
        @foreach ($subjects as $row)
            @php($pct = $row['total'] ? (int) round($row['done'] / $row['total'] * 100) : 0)
            <section class="card" style="border-top:4px solid {{ $row['subject']->color }}">
                <div class="row between"><h2 style="margin:0;font-size:20px">{{ $row['subject']->name }}</h2><strong>{{ $pct }}%</strong></div>
                <div class="small">{{ $row['done'] }} of {{ $row['total'] }} lessons finished</div>
                <div class="bar" style="background:{{ $row['subject']->tint }}"><span style="width:{{ $pct }}%;background:{{ $row['subject']->color }}"></span></div>
                <div class="small muted">{{ $row['next'] ? 'Up next: '.$row['next']->title : ($row['total'] ? 'All lessons finished!' : 'No lessons at this level yet') }}</div>
            </section>
        @endforeach
    </div>

    @if ($tests->isNotEmpty())
        <section class="card">
            <h2 style="margin:0;font-size:20px">Chapter tests</h2>
            <p class="small muted" style="margin:0">Done on their own, no hints. Weakest topic shown for each.</p>
            <ul class="skill-list">
                @foreach ($tests as $t)
                    @php($weak = collect($t->by_topic)->sortBy(fn ($v) => $v[0] / max(1, $v[1]))->keys()->first())
                    <li>
                        <div style="flex:1 1 220px"><strong>{{ $t->lesson->module->unit->level->short_name }} · {{ $t->lesson->module->title }} · {{ $t->lesson->title }}</strong>
                            <div class="small muted">{{ $t->created_at->timezone('Australia/Melbourne')->format('j M') }}@if ($weak && $t->score < $t->total) · work on: {{ $weak }}@endif</div></div>
                        <span class="score-pill {{ $t->percent() >= 75 ? 'good' : ($t->percent() >= 60 ? 'ok' : 'low') }}">{{ $t->score }}/{{ $t->total }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($skillRecords->isNotEmpty())
        <section class="card">
            <h2 style="margin:0;font-size:20px">Maths tutor skills</h2>
            <p class="small muted" style="margin:0">From questions worked line by line in the tutor. "On their own" means no hints, steps or corrections.</p>
            <ul class="skill-list">
                @foreach ($skillRecords as $r)
                    <li>
                        <strong style="flex:1 1 200px">{{ \App\Tutor\Skills::name($r->skill_code) }}</strong>
                        <span class="small muted">{{ $r->unaided_successes }} on their own · {{ $r->assisted_successes }} with help</span>
                        <span class="state state-{{ $r->state }}">{{ $r->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="card">
        <h2 style="margin:0;font-size:20px">Study days (last 4 weeks)</h2>
        <div class="heat">
            @for ($i = 27; $i >= 0; $i--)
                @php($d = now()->timezone('Australia/Melbourne')->subDays($i)->toDateString())
                @php($n = $days[$d] ?? 0)
                <span class="heat-cell lvl-{{ $n === 0 ? 0 : ($n < 5 ? 1 : ($n < 15 ? 2 : 3)) }}" title="{{ \Carbon\Carbon::parse($d)->format('D j M') }}: {{ $n }} questions"></span>
            @endfor
        </div>
        <span class="small muted">Each square is a day. Darker = more questions answered.</span>
    </section>

    <div class="grid-2">
        <section class="card">
            <h2 style="margin:0;font-size:20px">Needs more work</h2>
            @forelse ($weak as $w)
                <div class="row between"><span>{{ $w['lesson']->title }}</span><span class="bad">{{ $w['score'] }}%</span></div>
            @empty
                <p class="muted small" style="margin:0">Nothing flagged. Topics under 60% show here.</p>
            @endforelse
        </section>
        <section class="card">
            <h2 style="margin:0;font-size:20px">Recently finished</h2>
            @forelse ($recent as $lesson)
                <div class="row between small"><span>{{ $lesson->title }}</span><span class="muted">{{ \Carbon\Carbon::parse($lesson->pivot->completed_at)->diffForHumans() }}</span></div>
            @empty
                <p class="muted small" style="margin:0">No lessons finished yet.</p>
            @endforelse
        </section>
    </div>

    @if ($exams->isNotEmpty())
        <section class="card">
            <h2 style="margin:0;font-size:20px">Practice exams</h2>
            @foreach ($exams as $e)
                @php($a = $e['attempt'])
                <div class="row between small">
                    <span>{{ $e['exam']['title'] }} · {{ $a->submitted_at->timezone('Australia/Melbourne')->format('j M') }}</span>
                    <span>{{ $a->score_b !== null ? ($a->score_a + $a->score_b).' / '.$e['exam']['total'] : 'Section A '.$a->score_a.' / '.$e['exam']['marks_a'].' (written part not marked yet)' }}</span>
                </div>
            @endforeach
        </section>
    @endif

    <details class="card">
        <summary style="cursor:pointer;font-weight:600">Edit account (name, username, year level, password)</summary>
        <form method="POST" action="{{ route('family.students.update', $student) }}" style="display:flex;flex-direction:column;gap:12px;max-width:520px;margin-top:12px">
            @csrf
            @method('PUT')
            <label>Name <input name="name" value="{{ old('name', $student->name) }}" required></label>
            <label>Username <input name="username" value="{{ old('username', $student->username) }}" minlength="3" maxlength="30" pattern="[a-z0-9._\-]+" autocapitalize="none" spellcheck="false" placeholder="e.g. mahek"></label>
            <label>Email <span class="small muted">(optional)</span> <input type="email" name="email" value="{{ old('email', $student->email) }}"></label>
            <label>Year level
                <select name="level_id" required>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('level_id', $student->level_id) == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>New password <span class="small muted">(leave blank to keep it)</span> <input type="password" name="password" minlength="8" autocomplete="new-password"></label>
            <label>Type the new password again <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password"></label>
            <div><button class="btn" type="submit">Save</button></div>
        </form>
        @unless (auth()->user()->isAdmin())
            <form method="POST" action="{{ route('family.students.destroy', $student) }}" style="margin-top:16px" onsubmit="return confirm('Remove {{ addslashes($student->name) }} from your list? Their account and progress are kept.')">
                @csrf
                @method('DELETE')
                <button class="linklike small" type="submit">Remove this student from my list</button>
            </form>
        @endunless
    </details>
@endsection

@extends('layouts.app')
@section('title', $student->name)

@section('content')
    <div class="row small muted"><a href="{{ route('family') }}" style="font-weight:600">← All students</a></div>
    <header class="row between">
        <div>
            <h1 style="margin:0">{{ $student->name }}</h1>
            <p class="muted" style="margin:0">{{ $student->level?->name ?? 'No year level set' }} · logs in with {{ $student->email }}</p>
        </div>
        <div class="score-big">
            <span class="small muted">Average score</span>
            <strong>{{ $accuracy !== null ? $accuracy.'%' : '—' }}</strong>
        </div>
    </header>

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
        <summary style="cursor:pointer;font-weight:600">Edit account (name, year level, login email, password)</summary>
        <form method="POST" action="{{ route('family.students.update', $student) }}" style="display:flex;flex-direction:column;gap:12px;max-width:520px;margin-top:12px">
            @csrf
            @method('PUT')
            <label>Name <input name="name" value="{{ old('name', $student->name) }}" required></label>
            <label>Login email <input type="email" name="email" value="{{ old('email', $student->email) }}" required></label>
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

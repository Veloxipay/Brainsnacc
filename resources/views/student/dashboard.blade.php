@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <header class="row between">
        <div>
            <p class="muted small" style="margin:0">{{ now()->format('l j F') }} · {{ $level?->name }}</p>
            <h1>Hi {{ \Illuminate\Support\Str::before($student->name, ' ') }}, ready for 20 minutes?</h1>
        </div>
    </header>

    <a class="card tutor-continue" href="{{ route('tutor.home') }}" style="flex-direction:row;flex-wrap:wrap;align-items:center;gap:16px">
        <span style="flex:1 1 260px"><strong>Stuck on maths homework?</strong><br><span class="small muted">Type the equation, work it line by line, and I'll show you exactly where it goes wrong.</span></span>
        <span class="btn secondary">Open the Maths tutor</span>
    </a>

    @if ($next)
        @php($nextSubject = $next->module->unit->subject)
        <section class="card" aria-labelledby="next-h" style="flex-direction:row;flex-wrap:wrap;align-items:center;gap:24px">
            <div style="flex:1 1 320px;display:flex;flex-direction:column;gap:8px">
                <span class="pill {{ ['biology' => 'bio', 'maths' => 'maths'][$nextSubject->slug] ?? '' }}" style="align-self:flex-start">
                    {{ $nextSubject->name }} · {{ $next->module->unit->code }}
                </span>
                <h2 id="next-h" style="font-family:'Space Grotesk',sans-serif;font-size:24px">Up next: {{ $next->title }}</h2>
                <p class="muted" style="margin:0">{{ $next->summary }} About {{ $next->minutes }} minutes.</p>
            </div>
            <a class="btn" href="{{ route('lessons.show', $next) }}">Start lesson</a>
        </section>
    @else
        <section class="card">
            <h2>Nothing new right now</h2>
            <p class="muted" style="margin:0">You’ve finished every published lesson at your level. Try the practice questions again, or look at another level in the course map.</p>
        </section>
    @endif

    <div class="grid-2">
        @foreach ($subjects as $row)
            @php($s = $row['subject'])
            <section class="card" aria-labelledby="sub-{{ $s->id }}">
                <div class="row between">
                    <h2 id="sub-{{ $s->id }}">{{ $s->name }}</h2>
                    <a class="small" href="{{ route('course', $s) }}">Course map →</a>
                </div>
                <div>
                    <div class="row between small"><span>Lessons done</span><strong>{{ $row['finished'] }} of {{ $row['total'] }} · {{ $row['percent'] }}%</strong></div>
                    <div class="bar" style="margin-top:6px;background:{{ $s->tint }}"><span style="width:{{ $row['percent'] }}%;background:{{ $s->color }}"></span></div>
                </div>
                @if ($row['next'])
                    <a class="small" href="{{ route('lessons.show', $row['next']) }}">Next: {{ $row['next']->title }} →</a>
                @endif
            </section>
        @endforeach
    </div>

    <div class="grid-2">
        <section class="card" aria-labelledby="weak-h">
            <h2 id="weak-h">Topics to revise</h2>
            @forelse ($weak as $w)
                <div class="row between">
                    <a href="{{ route('lessons.show', $w['lesson']) }}">{{ $w['lesson']->title }}</a>
                    <span class="bad">{{ $w['score'] }}%</span>
                </div>
            @empty
                <p class="muted small" style="margin:0">Nothing yet. Topics where you score under 60% will show up here.</p>
            @endforelse
        </section>
        <section class="card" aria-labelledby="mis-h">
            <h2 id="mis-h">Mistakes to fix</h2>
            @if ($mistakes === 0)
                <p class="muted small" style="margin:0">No mistakes left to fix. Nice work!</p>
            @else
                <p class="muted small" style="margin:0 0 8px">{{ $mistakes }} {{ \Illuminate\Support\Str::plural('question', $mistakes) }} to get right. Click a topic to try again — {{ $mistakes === 1 ? 'it leaves' : 'each one leaves' }} this list once you get it right.</p>
                @foreach ($mistakeLessons as $m)
                    <div class="row between">
                        <a class="mistake-topic" href="{{ $m['url'] }}">{{ $m['lesson']->title }} →</a>
                        <span class="bad small">{{ $m['count'] }} to fix{{ $m['needsHelp'] ? ' · go over topic' : '' }}</span>
                    </div>
                @endforeach
            @endif
        </section>
    </div>
@endsection

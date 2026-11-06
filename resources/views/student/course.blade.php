@extends('layouts.app')
@section('title', $subject->name.' course map')

@section('content')
    {{-- The student's next lesson across the whole course: the page jumps to it on open, so nobody has to scroll on a phone --}}
    @php($nextLesson = $units->flatMap(fn ($u) => $u->modules)->flatMap(fn ($m) => $m->lessons)->first(fn ($l) => $l->isPublished() && ! $done->contains($l->id)))
    <header>
        <p class="small muted" style="margin:0 0 4px">{{ $level?->name }}</p>
        <h1 style="margin:0"><span class="nav-dot" style="background:{{ $subject->color }};width:14px;height:14px"></span> {{ $subject->name }} course map</h1>
        @if ($nextLesson)
            <a class="btn" href="#next-lesson" style="margin-top:12px">Up next: {{ $nextLesson->title }} ↓</a>
        @endif
    </header>

    @forelse ($units as $unit)
        <h2 style="font-size:22px">{{ $unit->code }} · {{ $unit->title }}</h2>

        @foreach ($unit->modules as $module)
            @php($lessons = $module->lessons)
            @php($ready = $lessons->filter(fn ($l) => $l->isPublished()))
            @php($doneCount = $ready->whereIn('id', $done)->count())
            @php($currentId = $ready->first(fn ($l) => ! $done->contains($l->id))?->id)
            <section class="card" aria-labelledby="m-{{ $module->id }}">
                <div class="row between">
                    <h2 id="m-{{ $module->id }}">{{ $module->title }}</h2>
                    <span class="muted small">{{ $doneCount }} of {{ $ready->count() }} ready lessons done @if ($lessons->count() > $ready->count()) · {{ $lessons->count() - $ready->count() }} coming soon @endif</span>
                </div>

                @if ($lessons->isEmpty())
                    <p class="muted small" style="margin:0">Lessons coming soon.</p>
                @else
                    <ol class="lesson-list">
                        @foreach ($lessons as $lesson)
                            @if (! $lesson->isPublished())
                                <li class="soon">
                                    <span class="dot" aria-hidden="true"></span>
                                    <span class="lesson-title">{{ $lesson->title }}@if ($lesson->kind === 'test') <span class="pill maths" style="font-size:11px">Test</span>@endif</span>
                                    <span class="small muted">Coming soon</span>
                                </li>
                                @continue
                            @endif
                            @php($isDone = $done->contains($lesson->id))
                            @php($isCurrent = $lesson->id === $currentId)
                            <li class="{{ $isDone ? 'done' : ($isCurrent ? 'current' : '') }}" @if ($lesson->id === $nextLesson?->id) id="next-lesson" style="scroll-margin-top:24px" @endif>
                                @if ($isDone)
                                    <span class="dot done" aria-label="Done">✓</span>
                                @else
                                    <span class="dot {{ $isCurrent ? 'current' : '' }}" aria-label="{{ $isCurrent ? 'Up next' : 'Not started' }}"></span>
                                @endif
                                <a href="{{ route('lessons.show', $lesson) }}">{{ $lesson->title }}@if ($lesson->kind === 'test') <span class="pill maths" style="font-size:11px">Test</span>@endif</a>
                                <span class="small muted">{{ $isCurrent ? 'Up next' : $lesson->minutes.' min' }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endforeach
    @empty
        <section class="card">
            <p class="muted" style="margin:0">No {{ $subject->name }} units at this level yet.</p>
        </section>
    @endforelse

    <script>
        // Open the course map at the student's next lesson instead of the top (unless the link already points somewhere on the page).
        (function () {
            var next = document.getElementById('next-lesson');
            if (!next || location.hash) return;
            if (next.getBoundingClientRect().top > window.innerHeight * 0.6) {
                next.scrollIntoView({ block: 'center' });
            }
        })();
    </script>
@endsection

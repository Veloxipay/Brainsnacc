@extends('layouts.app')
@section('title', $subject->name.' course map')

@section('content')
    <header>
        <p class="small muted" style="margin:0 0 4px">{{ $level?->name }}</p>
        <h1 style="margin:0"><span class="nav-dot" style="background:{{ $subject->color }};width:14px;height:14px"></span> {{ $subject->name }} course map</h1>
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
                                    <span class="lesson-title">{{ $lesson->title }}</span>
                                    <span class="small muted">Coming soon</span>
                                </li>
                                @continue
                            @endif
                            @php($isDone = $done->contains($lesson->id))
                            @php($isCurrent = $lesson->id === $currentId)
                            <li class="{{ $isDone ? 'done' : ($isCurrent ? 'current' : '') }}">
                                @if ($isDone)
                                    <span class="dot done" aria-label="Done">✓</span>
                                @else
                                    <span class="dot {{ $isCurrent ? 'current' : '' }}" aria-label="{{ $isCurrent ? 'Up next' : 'Not started' }}"></span>
                                @endif
                                <a href="{{ route('lessons.show', $lesson) }}">{{ $lesson->title }}</a>
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
@endsection

@extends('layouts.app')
@section('title', $lesson->title)

@section('content')
    @php($unit = $lesson->module->unit)
    @php($result = session('result'))

    <div class="row small muted">
        <a href="{{ route('course', [$subject, 'level' => $unit->level_id]) }}" style="font-weight:600">← Course map</a>
        <span>{{ $subject->name }} · {{ $unit->level->short_name }} · {{ $unit->code }} · {{ $lesson->module->title }}</span>
        @if ($position)<span style="margin-left:auto">Lesson {{ $position }} of {{ $count }}</span>@endif
    </div>

    @unless ($lesson->isPublished())
        <div class="errors">Draft — students can’t see this lesson yet.</div>
    @endunless

    <article class="card" style="padding:32px">
        <h1>{{ $lesson->title }}</h1>
        <div class="lesson-body">{!! $lesson->bodyHtml() !!}</div>

        <div class="row between">
            @if ($completed)
                <span class="good">✓ Lesson done</span>
            @elseif ($lesson->isPublished())
                <form method="POST" action="{{ route('lessons.complete', $lesson) }}">
                    @csrf
                    <button class="btn secondary" type="submit">Mark lesson as done</button>
                </form>
            @endif
            <span class="muted small">About {{ $lesson->minutes }} minutes</span>
        </div>
    </article>

    @if ($lesson->questions->isNotEmpty())
        <h2 id="practice" style="font-size:22px">Practice questions</h2>

        @foreach ($lesson->questions as $i => $question)
            @include('student._question', ['question' => $question, 'label' => 'Question '.($i + 1), 'subject' => $subject, 'result' => $result])
        @endforeach
    @endif

    <section class="card" aria-labelledby="tutor-h">
        <h2 id="tutor-h">Ask the tutor</h2>
        <p class="muted small" style="margin:0">The AI tutor is coming in the next stage. It will give hints first, then full explanations once you’ve tried.</p>
    </section>

    <div class="row between">
        @if ($previous)<a href="{{ route('lessons.show', $previous) }}" style="font-weight:600">← {{ $previous->title }}</a>@else<span></span>@endif
        @if ($nextLesson)<a class="btn" href="{{ route('lessons.show', $nextLesson) }}">Next: {{ $nextLesson->title }} →</a>@endif
    </div>
@endsection

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
            @php($mine = $result && $result['question_id'] === $question->id ? $result : null)
            <section class="card" id="q{{ $question->id }}" aria-labelledby="qp{{ $question->id }}">
                <span class="pill {{ $subject->slug === 'biology' ? 'bio' : '' }}" style="align-self:flex-start">Question {{ $i + 1 }} · {{ $question->marks }} {{ \Illuminate\Support\Str::plural('mark', $question->marks) }}</span>
                <p id="qp{{ $question->id }}" style="margin:0;font-size:18px;font-weight:600">{{ $question->prompt }}</p>

                <form method="POST" action="{{ route('questions.answer', $question) }}" style="display:flex;flex-direction:column;gap:10px">
                    @csrf
                    <fieldset style="border:0;padding:0;margin:0;display:flex;flex-direction:column;gap:10px">
                        <legend class="small muted" style="margin-bottom:8px">Choose one answer</legend>
                        @foreach ($question->options ?? [] as $letter => $text)
                            @php($state = $mine ? ($letter === $question->answer ? 'correct' : ($letter === $mine['answer'] ? 'wrong' : '')) : '')
                            <label class="option {{ $state }}" style="font-weight:400;margin:0">
                                <input type="radio" name="answer" value="{{ $letter }}" @checked($mine && $mine['answer'] === $letter) required>
                                <strong>{{ $letter }}</strong>
                                <span style="flex:1">{{ $text }}</span>
                                @if ($state === 'correct')<span class="good small">Correct</span>@endif
                                @if ($state === 'wrong')<span class="bad small">Your answer</span>@endif
                            </label>
                        @endforeach
                    </fieldset>

                    @if ($mine)
                        <div class="feedback {{ $mine['correct'] ? 'right' : 'wrong' }}" role="status">
                            <strong>{{ $mine['correct'] ? 'Correct!' : 'Not quite.' }}</strong>
                            @if ($question->explanation)<div>{{ $question->explanation }}</div>@endif
                        </div>
                    @endif

                    <div><button class="btn" type="submit">{{ $mine ? 'Try again' : 'Check answer' }}</button></div>
                </form>
            </section>
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

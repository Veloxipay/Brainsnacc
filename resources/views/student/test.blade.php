@extends('layouts.app')
@section('title', $lesson->title)

@section('content')
    <header>
        <a class="small" href="{{ route('course', [$subject, 'level' => $lesson->module->unit->level_id]) }}" style="font-weight:600">← Course map</a>
        <p class="small muted" style="margin:8px 0 0">{{ $lesson->module->unit->level->name }} · {{ $lesson->module->title }}</p>
        <h1 style="margin:0">{{ $lesson->title }}</h1>
    </header>

    <section class="card">
        <h2 style="font-size:18px">Before you start</h2>
        <ul class="small" style="margin:0;padding-left:20px;display:flex;flex-direction:column;gap:4px">
            @if ($subject->slug === 'selective')
                <li>{{ $lesson->questions->count() }} questions in {{ $lesson->minutes }} minutes, like the real selective entry test. The timer starts now and your answers are handed in when it reaches zero.</li>
                <li>Don't get stuck: skip a hard question, answer the rest, then come back. A guess is better than a blank.</li>
            @else
                <li>{{ $lesson->questions->count() }} questions from every topic in this chapter, Easy to Challenge. Allow about {{ $lesson->minutes }} minutes.</li>
            @endif
            <li>Do it on your own: no notes, no hints, phone away. Use paper for working.</li>
            <li>Nothing is marked until you finish. Then you'll see your score for each topic and what to revise.</li>
        </ul>
        @if ($past->isNotEmpty())
            <p class="small muted" style="margin:0">Your earlier tries: @foreach ($past as $p)<a href="{{ route('tests.result', [$lesson, $p]) }}">{{ $p->score }}/{{ $p->total }}</a>{{ $loop->last ? '' : ' · ' }}@endforeach</p>
        @endif
    </section>

    @if (str_contains((string) $lesson->body, '## Passage'))
        <section class="card lesson-body passages">{!! \Illuminate\Support\Str::markdown($lesson->body, ['html_input' => 'escape']) !!}</section>
    @endif

    @if ($subject->slug === 'selective')
        <div class="exam-timer" id="exam-timer" role="timer" aria-live="off" data-seconds="{{ $lesson->minutes * 60 }}">{{ $lesson->minutes }}:00 left</div>
    @endif

    <form method="POST" action="{{ route('tests.submit', $lesson) }}" id="test-form" style="display:flex;flex-direction:column;gap:16px">
        @csrf
        <input type="hidden" name="started" value="{{ $started }}">
        @foreach ($lesson->questions as $i => $q)
            <fieldset class="card test-q">
                <legend class="visually-hidden">Question {{ $i + 1 }}</legend>
                <span class="pill maths" style="align-self:flex-start">Question {{ $i + 1 }} of {{ $lesson->questions->count() }}@if ($q->levelLabel()) · <span class="level level-{{ $q->difficulty }}">{{ $q->levelLabel() }}</span>@endif</span>
                <p style="margin:0;font-size:18px;font-weight:600">{{ $q->prompt }}</p>
                @include('student._diagram', ['question' => $q])
                @foreach ($q->options ?? [] as $letter => $text)
                    <label class="option" style="font-weight:400;margin:0">
                        <input type="radio" name="answers[{{ $q->id }}]" value="{{ $letter }}">
                        <strong>{{ $letter }}</strong>
                        <span style="flex:1">{{ $text }}</span>
                    </label>
                @endforeach
            </fieldset>
        @endforeach
        <section class="card">
            <p class="small muted" style="margin:0">Check you've answered every question — blanks count as wrong.</p>
            <div><button class="btn" type="submit" onclick="var n=document.querySelectorAll('.test-q').length-document.querySelectorAll('.test-q input:checked').length; return n===0 || confirm(n+' question(s) not answered. Finish anyway?')">Finish and mark my test</button></div>
        </section>
    </form>
    @if ($subject->slug === 'selective')
        <script>
            (function () {
                var el = document.getElementById('exam-timer'), form = document.getElementById('test-form');
                var end = Date.now() + parseInt(el.dataset.seconds, 10) * 1000, done = false;
                function tick() {
                    var left = Math.max(0, Math.round((end - Date.now()) / 1000));
                    el.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0') + ' left';
                    if (left <= 300) el.classList.add('low');
                    if (left === 0 && !done) { done = true; el.textContent = "Time's up: handing in"; form.submit(); return; }
                    setTimeout(tick, 1000);
                }
                tick();
            })();
        </script>
    @endif
@endsection

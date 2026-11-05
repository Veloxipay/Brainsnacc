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
            <li>{{ $lesson->questions->count() }} questions from every topic in this chapter, Easy to Challenge. Allow about {{ $lesson->minutes }} minutes.</li>
            <li>Do it on your own: no notes, no hints, phone away. Use paper for working.</li>
            <li>Nothing is marked until you finish. Then you'll see your score for each topic and what to revise.</li>
        </ul>
        @if ($past->isNotEmpty())
            <p class="small muted" style="margin:0">Your earlier tries: @foreach ($past as $p)<a href="{{ route('tests.result', [$lesson, $p]) }}">{{ $p->score }}/{{ $p->total }}</a>{{ $loop->last ? '' : ' · ' }}@endforeach</p>
        @endif
    </section>

    <form method="POST" action="{{ route('tests.submit', $lesson) }}" style="display:flex;flex-direction:column;gap:16px">
        @csrf
        <input type="hidden" name="started" value="{{ $started }}">
        @foreach ($lesson->questions as $i => $q)
            <fieldset class="card test-q">
                <legend class="visually-hidden">Question {{ $i + 1 }}</legend>
                <span class="pill maths" style="align-self:flex-start">Question {{ $i + 1 }} of {{ $lesson->questions->count() }}@if ($q->levelLabel()) · <span class="level level-{{ $q->difficulty }}">{{ $q->levelLabel() }}</span>@endif</span>
                <p style="margin:0;font-size:18px;font-weight:600">{{ $q->prompt }}</p>
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
@endsection

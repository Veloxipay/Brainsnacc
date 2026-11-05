@extends('layouts.app')
@section('title', 'Let’s go over it · '.$lesson->title)

@section('content')
    @php($unit = $lesson->module->unit)
    @php($result = session('result'))

    <div class="row small muted">
        <a href="{{ route('lessons.show', $lesson) }}" style="font-weight:600">← Back to the lesson</a>
        <span>{{ $subject->name }} · {{ $unit->level->short_name }} · {{ $unit->code }} · {{ $lesson->module->title }}</span>
    </div>

    <header>
        <h1 style="margin-bottom:6px">Let’s go over it: {{ $lesson->title }}</h1>
        <p class="muted" style="margin:0">This question was answered wrong twice, so let’s fix it properly. Work through the three steps below.</p>
    </header>

    <section class="card help-question">
        <span class="pill {{ ['biology' => 'bio', 'maths' => 'maths'][$subject->slug] ?? '' }}" style="align-self:flex-start">The question you’re fixing</span>
        <p style="margin:0;font-size:18px;font-weight:600">{{ $question->prompt }}</p>
    </section>

    <h2 class="help-step"><span>1</span> Watch and re-read the key parts of the topic</h2>
    @if ($lesson->video)
        <figure class="lesson-video card" style="margin:0">
            <video controls preload="metadata" playsinline src="{{ asset($lesson->video) }}"></video>
            <figcaption class="small muted">Watch the short video on this topic again.</figcaption>
        </figure>
    @endif
    @if (isset($sections['Think of it like this']))
        <section class="card lesson-body">
            <h3 style="margin-top:0">Think of it like this</h3>
            {!! $sections['Think of it like this'] !!}
        </section>
    @endif
    @if (isset($sections['Common mistakes']))
        <section class="card lesson-body">
            <h3 style="margin-top:0">Common mistakes</h3>
            {!! $sections['Common mistakes'] !!}
        </section>
    @endif
    @if (isset($sections['Tips to remember']))
        <section class="card lesson-body">
            <h3 style="margin-top:0">Tips to remember</h3>
            {!! $sections['Tips to remember'] !!}
        </section>
    @endif
    @if (isset($sections['Key ideas']))
        <details class="card lesson-body">
            <summary style="cursor:pointer;font-weight:600">Read the full explanation of the topic (Key ideas)</summary>
            {!! $sections['Key ideas'] !!}
        </details>
    @endif
    @if (empty($sections))
        <p><a href="{{ route('lessons.show', $lesson) }}">Re-read the lesson →</a></p>
    @endif

    @if ($practice->isNotEmpty())
        <h2 class="help-step"><span>2</span> Practise more on this topic</h2>
        @foreach ($practice as $i => $q)
            @include('student._question', ['question' => $q, 'label' => 'Practice '.($i + 1), 'subject' => $subject, 'result' => $result, 'help' => $question->id])
        @endforeach
    @endif

    <h2 class="help-step"><span>{{ $practice->isNotEmpty() ? 3 : 2 }}</span> Now try the original question again</h2>
    @if ($fixed)
        <div class="feedback right" role="status"><strong>Fixed!</strong> It’s off your mistakes list.</div>
    @endif
    @include('student._question', ['question' => $question, 'label' => 'Your question', 'subject' => $subject, 'result' => $result, 'help' => $question->id])

    <div class="row between">
        <a href="{{ route('lessons.show', $lesson) }}" style="font-weight:600">← Back to {{ $lesson->title }}</a>
        <a class="btn secondary" href="{{ route('dashboard') }}">Dashboard</a>
    </div>
@endsection

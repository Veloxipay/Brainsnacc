@extends('layouts.app')
@section('title', 'Result: '.$lesson->title)

@section('content')
    <header>
        <a class="small" href="{{ route('course', [$subject, 'level' => $lesson->module->unit->level_id]) }}" style="font-weight:600">← Course map</a>
        <p class="small muted" style="margin:8px 0 0">{{ $lesson->module->title }}</p>
        <h1 style="margin:0">{{ $lesson->title }}: {{ $attempt->score }} / {{ $attempt->total }}</h1>
        <p class="muted" style="margin:4px 0 0">
            {{ $attempt->percent() }}% · done on your own on {{ $attempt->created_at->timezone('Australia/Melbourne')->format('j M Y') }}@if ($attempt->seconds) in {{ max(1, (int) round($attempt->seconds / 60)) }} min @endif
        </p>
    </header>

    <section class="card" aria-labelledby="topics-h">
        <h2 id="topics-h">By topic</h2>
        <p class="small muted" style="margin:0">Weakest first. Revise any topic under 2 out of 3, then try the next test.</p>
        <ul class="skill-list">
            @foreach ($topics as $t)
                @php($pct = $t['of'] ? $t['right'] / $t['of'] : 0)
                <li>
                    <strong style="flex:1 1 200px">{{ $t['topic'] }}</strong>
                    <span class="state {{ $pct >= 0.99 ? 'state-remembered' : ($pct >= 0.66 ? 'state-independent' : ($pct > 0 ? 'state-developing' : 'state-needs_foundations')) }}">{{ $t['right'] }} / {{ $t['of'] }}</span>
                    @if ($t['lesson'] && $t['lesson']->isPublished() && $pct < 0.66)
                        <a class="btn secondary small" href="{{ route('lessons.show', $t['lesson']) }}">Revise</a>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="row">
            @if ($nextTest)<a class="btn" href="{{ route('tests.show', $nextTest) }}">Try {{ $nextTest->title }}</a>@endif
            <a class="btn secondary" href="{{ route('tests.show', $lesson) }}">Retake this test</a>
        </div>
    </section>

    <section class="card" aria-labelledby="review-h">
        <h2 id="review-h">Check your answers</h2>
        <ol class="feed">
            @foreach ($lesson->questions as $i => $q)
                @php($given = $attempt->answers[$q->id] ?? null)
                @php($right = $given !== null && $q->isCorrect($given))
                <li class="feed-line {{ $right ? 'line-correct' : 'line-wrong' }}">
                    <div class="small muted">Question {{ $i + 1 }}@if ($q->levelLabel()) · {{ $q->levelLabel() }}@endif @if ($q->topic) · {{ $q->topic }}@endif</div>
                    <div style="font-weight:600">{{ $q->prompt }}</div>
                    <div class="line-row">
                        <span class="line-mark" aria-hidden="true">{{ $right ? '✓' : '✗' }}</span>
                        <span class="small line-status">
                            @if ($right) Correct: {{ $q->answer }}. {{ $q->options[$q->answer] ?? '' }}
                            @else You chose {{ $given ? $given.'. '.($q->options[$given] ?? '') : 'nothing' }} — the answer is {{ $q->answer }}. {{ $q->options[$q->answer] ?? '' }} @endif
                        </span>
                    </div>
                    @unless ($right)
                        <div class="tutor-says">
                            @if ($given && $q->mistakeFor($given))<div><strong>Where it usually goes wrong:</strong> {{ $q->mistakeFor($given) }}</div>@endif
                            @include('student._question_steps', ['question' => $q])
                        </div>
                    @endunless
                </li>
            @endforeach
        </ol>
    </section>
@endsection

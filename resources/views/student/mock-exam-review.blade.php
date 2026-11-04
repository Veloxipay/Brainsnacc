@extends('layouts.app')
@section('title', 'Review · '.$exam['title'])

@section('content')
    @php($md = fn ($t) => \App\Support\MockExams::md($t))
    @php($marked = $attempt->score_b !== null)
    <div class="row small muted"><a href="{{ route('mock-exams.show', $exam['slug']) }}" style="font-weight:600">← {{ $exam['title'] }}</a></div>

    <section class="card">
        <h1 style="margin:0">Your results</h1>
        <div class="mx-facts">
            <div><strong>{{ $attempt->score_a }} / {{ $exam['marks_a'] }}</strong><span>Section A (auto-marked)</span></div>
            <div><strong>{{ $marked ? $attempt->score_b.' / '.$exam['marks_b'] : '—' }}</strong><span>Section B {{ $marked ? '(self-marked)' : '(mark it below)' }}</span></div>
            @if ($marked)
                @php($total = $attempt->score_a + $attempt->score_b)
                <div><strong>{{ $total }} / {{ $exam['total'] }}</strong><span>{{ round($total / $exam['total'] * 100) }}% overall</span></div>
            @endif
        </div>
        @php($wrongTopics = collect($exam['section_a'])->filter(fn ($q, $i) => ($attempt->answers_a[$i] ?? null) !== $q['answer'])->pluck('topic')->filter()->countBy()->sortDesc())
        @if ($wrongTopics->isNotEmpty())
            <p class="small" style="margin:0"><strong>Topics to revise (from Section A):</strong> {{ $wrongTopics->map(fn ($n, $t) => $t.($n > 1 ? " ×$n" : ''))->implode(', ') }}</p>
        @endif
    </section>

    <h2 class="mx-section">Section A — Multiple choice</h2>
    @foreach ($exam['section_a'] as $i => $q)
        @php($mine = $attempt->answers_a[$i] ?? null)
        @php($right = $mine === $q['answer'])
        <details class="card mx-q" @if (! $right) open @endif>
            <summary><span class="pill">Question {{ $i + 1 }}</span> <span class="{{ $right ? 'good' : 'bad' }}">{{ $right ? '✓ Correct' : ($mine ? '✗ You chose '.$mine : '✗ Not answered') }}</span> @if (! empty($q['topic']))<span class="small muted">· {{ $q['topic'] }}</span>@endif</summary>
            <div class="lesson-body">{!! $md($q['prompt']) !!}</div>
            @foreach ($q['options'] as $letter => $text)
                @php($state = $letter === $q['answer'] ? 'correct' : ($letter === $mine ? 'wrong' : ''))
                <div class="option {{ $state }}"><strong>{{ $letter }}</strong><span style="flex:1">{{ $text }}</span></div>
            @endforeach
            <div class="feedback {{ $right ? 'right' : 'wrong' }}"><strong>Answer: {{ $q['answer'] }}.</strong> {{ $q['explanation'] }}</div>
        </details>
    @endforeach

    <h2 class="mx-section">Section B — Mark your written answers</h2>
    <p class="muted" style="margin:0">For each part, compare your answer with the model answer and the marking guide, then give yourself the marks you honestly earned. Be strict — that’s how the real examiners mark.</p>
    <form method="POST" action="{{ route('mock-exams.mark', $attempt) }}" style="display:flex;flex-direction:column;gap:16px">
        @csrf
        @foreach ($exam['section_b'] as $q)
            <section class="card mx-q">
                <span class="pill">Question {{ $q['number'] }} @if (! empty($q['topic']))· {{ $q['topic'] }}@endif</span>
                @if (! empty($q['stem']))<div class="lesson-body">{!! $md($q['stem']) !!}</div>@endif
                @foreach ($q['parts'] as $part)
                    @php($key = \App\Support\MockExams::partKey($q, $part))
                    <div class="mx-part">
                        <div class="lesson-body"><strong>{{ $part['label'] }}.</strong> {!! $md($part['prompt']) !!} <span class="muted small">({{ $part['marks'] }} {{ \Illuminate\Support\Str::plural('mark', $part['marks']) }})</span></div>
                        <div class="mx-compare">
                            <div><div class="small muted">Your answer</div><div class="mx-mine">{{ ($attempt->answers_b[$key] ?? '') !== '' ? $attempt->answers_b[$key] : '(no answer)' }}</div></div>
                            <div><div class="small muted">Model answer</div><div class="lesson-body mx-model">{!! $md($part['answer']) !!}</div></div>
                        </div>
                        <div class="small"><strong>Marking guide:</strong>
                            <ul style="margin:4px 0 0;padding-left:20px">@foreach ($part['marking'] as $pt)<li>{{ $pt }}</li>@endforeach</ul>
                        </div>
                        <label class="small">My mark:
                            <select name="marks[{{ $key }}]">
                                @for ($m = 0; $m <= $part['marks']; $m++)
                                    <option value="{{ $m }}" @selected(($attempt->marks_b[$key] ?? null) === $m)>{{ $m }} / {{ $part['marks'] }}</option>
                                @endfor
                            </select>
                        </label>
                    </div>
                @endforeach
            </section>
        @endforeach
        <div><button class="btn" type="submit">{{ $marked ? 'Update my marks' : 'Save my marks' }}</button></div>
    </form>
@endsection

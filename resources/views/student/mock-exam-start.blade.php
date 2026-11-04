@extends('layouts.app')
@section('title', $exam['title'])

@section('content')
    <div class="row small muted"><a href="{{ route('past-papers') }}" style="font-weight:600">← Past VCE exams</a></div>

    <section class="card">
        <h1 style="margin:0">{{ $exam['title'] }}</h1>
        <p class="muted" style="margin:0">Original Brainsnacc questions written in the same format as the real end-of-year VCE {{ ucfirst($exam['subject']) }} exam.</p>
        <div class="mx-facts">
            <div><strong>{{ $exam['reading_minutes'] ?? 15 }} min</strong><span>reading time</span></div>
            <div><strong>{{ intdiv($exam['writing_minutes'] ?? 150, 60) }} h {{ ($exam['writing_minutes'] ?? 150) % 60 }} min</strong><span>writing time</span></div>
            <div><strong>{{ count($exam['section_a']) }}</strong><span>multiple choice ({{ $exam['marks_a'] }} marks)</span></div>
            <div><strong>{{ count($exam['section_b']) }}</strong><span>written questions ({{ $exam['marks_b'] }} marks)</span></div>
            <div><strong>{{ $exam['total'] }}</strong><span>marks total</span></div>
        </div>
        <ul style="margin:0;padding-left:20px;line-height:1.7">
            <li>Find a quiet spot and treat it like the real exam: no notes, phone away.@if ($exam['subject'] === 'chemistry') Use a scientific calculator and the <a href="https://www.vcaa.vic.edu.au/sites/default/files/2026-02/2026-ChemistryDataBook_0.pdf" target="_blank" rel="noopener">VCE Chemistry Data Book ↗</a>.@endif</li>
            <li>The timer starts with reading time. Your answers save on this device as you go, so a refresh won’t lose them.</li>
            <li>When you hand in, Section A is marked straight away. Then you mark your own Section B with the marking guide, just like a teacher would.</li>
        </ul>
        <form method="POST" action="{{ route('mock-exams.start', $exam['slug']) }}">
            @csrf
            @php($open = $attempts->firstWhere('submitted_at', null))
            <button class="btn" type="submit">{{ $open ? 'Continue my exam' : 'Start the exam' }}</button>
        </form>
    </section>

    @if ($attempts->whereNotNull('submitted_at')->isNotEmpty())
        <section class="card">
            <h2 style="margin:0;font-size:20px">Your past attempts</h2>
            @foreach ($attempts->whereNotNull('submitted_at') as $at)
                <div class="row between">
                    <span>{{ $at->submitted_at->timezone('Australia/Melbourne')->format('j M Y, g:ia') }}</span>
                    <span>
                        Section A {{ $at->score_a }}/{{ $exam['marks_a'] }}
                        · Section B {{ $at->score_b !== null ? $at->score_b.'/'.$exam['marks_b'] : 'not marked yet' }}
                        @if ($at->score_b !== null)· <strong>{{ $at->score_a + $at->score_b }}/{{ $exam['total'] }}</strong>@endif
                    </span>
                    <a href="{{ route('mock-exams.attempt', $at) }}">Review →</a>
                </div>
            @endforeach
        </section>
    @endif
@endsection

@extends('layouts.app')
@section('title', 'Past VCE exams')

@section('content')
    <header>
        <h1 style="margin:0 0 6px">VCE exams</h1>
        <p class="muted" style="margin:0">Real Year 12 (Units 3 and 4) exams from the last 10 years, with the examiners’ reports that show what earned marks. The files open on the official VCAA website.</p>
    </header>

    @if ($mockExams->isNotEmpty())
        <section class="card" aria-labelledby="mx-h">
            <h2 id="mx-h" style="margin:0;font-size:22px">Brainsnacc practice exams (sit them here)</h2>
            <p class="muted small" style="margin:0">Original questions written by Brainsnacc in the exact VCE exam format: 15 minutes reading, 2 hours 30 minutes writing, Section A multiple choice and Section B written answers, 120 marks. Sit them on screen with a timer; Section A marks itself and you mark Section B with the marking guide.</p>
            <div class="pp-list">
                @foreach ($mockExams as $mx)
                    @php($tries = $mockAttempts[$mx['slug']] ?? collect())
                    @php($best = $tries->whereNotNull('score_b')->map(fn ($t) => $t->score_a + $t->score_b)->max())
                    <div class="pp-row">
                        <div class="pp-name"><strong>{{ $mx['title'] }}</strong></div>
                        <div class="small muted">{{ count($mx['section_a']) }} multiple choice · {{ count($mx['section_b']) }} written questions · {{ $mx['total'] }} marks</div>
                        <div class="pp-form">
                            @if ($best !== null)<span class="good small">Best: {{ $best }}/{{ $mx['total'] }}</span>@endif
                            <a class="btn small" href="{{ route('mock-exams.show', $mx['slug']) }}">{{ $tries->isEmpty() ? 'Sit exam' : 'Open' }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="card">
        <h2 style="margin:0;font-size:20px">How to use them</h2>
        <ol style="margin:0;padding-left:20px;line-height:1.7">
            <li>Start with the papers marked <span class="pill" style="background:var(--good-tint);color:var(--good)">Current course</span>. They match what you learn now.</li>
            <li>Do the paper in exam conditions: 2 hours 30 minutes plus 15 minutes reading time, no notes (Chemistry: use the Data Book).</li>
            <li>Mark it with the examiners’ report. It gives the answers and says what students commonly got wrong.</li>
            <li>Tick it off below and enter your score. Revise any topic you lost marks on, then try an older paper.</li>
        </ol>
        <p class="small muted" style="margin:0">Older papers were written for an earlier course. Most questions are still good practice, but skip any on topics you haven’t been taught. Year 10 and Year 11 (Units 1 and 2) have no official VCAA exams, so use each unit’s practice exam in Brainsnacc instead. Monash past exams are in the Monash library for enrolled students.</p>
    </section>

    @foreach ($papers as $slug => $set)
        @php($subject = $subjects[$slug] ?? null)
        <section class="card" aria-labelledby="pp-{{ $slug }}">
            <div class="row between">
                <h2 id="pp-{{ $slug }}" style="margin:0;font-size:22px">
                    <span class="nav-dot" style="background:{{ $subject?->color }};width:12px;height:12px"></span>
                    VCE {{ $subject?->name ?? ucfirst($slug) }}
                </h2>
                <a class="small" href="{{ $set['source'] }}" target="_blank" rel="noopener">All {{ ucfirst($slug) }} papers on the VCAA website ↗</a>
            </div>

            <div class="row small" style="flex-wrap:wrap;gap:8px 16px">
                @foreach ($set['extras'] as $extra)
                    <a href="{{ $extra['url'] }}" target="_blank" rel="noopener">{{ $extra['label'] }} ↗</a>
                @endforeach
            </div>

            <div class="pp-list">
                @foreach ($set['papers'] as $paper)
                    @php($key = \App\Http\Controllers\PastPaperController::key($slug, $paper))
                    @php($mine = $done[$key] ?? null)
                    <div class="pp-row {{ $mine ? 'is-done' : '' }}" id="{{ $key }}">
                        <div class="pp-name">
                            <strong>{{ $paper['year'] }}{{ $paper['nht'] ? ' (mid-year NHT)' : '' }}</strong>
                            @if ($paper['current'])
                                <span class="pill" style="background:var(--good-tint);color:var(--good)">Current course</span>
                            @else
                                <span class="pill" style="background:var(--ground);color:var(--muted, #5F6A7D)">Older course</span>
                            @endif
                        </div>
                        <div class="pp-links small">
                            <a href="{{ $paper['exam'] }}" target="_blank" rel="noopener">Exam paper ↗</a>
                            <a href="{{ $paper['report'] }}" target="_blank" rel="noopener">Examiners’ report ↗</a>
                        </div>
                        <form method="POST" action="{{ route('past-papers.update') }}" class="pp-form">
                            @csrf
                            <input type="hidden" name="paper" value="{{ $key }}">
                            @if ($mine)
                                <span class="good small">✓ Done{{ $mine->score !== null ? ' · '.$mine->score.'%' : '' }}</span>
                                <input type="hidden" name="done" value="0">
                                <button class="linklike small" type="submit">Undo</button>
                            @else
                                <input type="hidden" name="done" value="1">
                                <label class="small muted">Score <input type="number" name="score" min="0" max="100" inputmode="numeric" placeholder="%" style="width:64px"></label>
                                <button class="btn secondary small" type="submit">Mark done</button>
                            @endif
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <p class="small muted">Exam papers and reports: © Victorian Curriculum and Assessment Authority. For current versions and related content visit <a href="https://www.vcaa.vic.edu.au" target="_blank" rel="noopener">www.vcaa.vic.edu.au</a>. Each link opens the original file on the VCAA website; Brainsnacc does not copy them and is not endorsed by the VCAA.</p>
@endsection

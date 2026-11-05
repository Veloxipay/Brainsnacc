@extends('layouts.app')
@section('title', 'My maths skills')

@section('content')
    <header>
        <a class="small" href="{{ route('tutor.home') }}">← Tutor</a>
        <h1 style="margin:4px 0 0">My maths skills</h1>
        <p class="muted" style="margin:4px 0 0">Every state here comes from questions you actually did. "On your own" means no hints, steps or corrections.</p>
    </header>

    <section class="card">
        <ul class="skill-list">
            @foreach ($skills as $code => $skill)
                @php($r = $records->get($code))
                <li>
                    <div style="flex:1 1 220px">
                        <strong>{{ $skill['name'] }}</strong> <span class="small muted">· Year {{ $skill['year'] }} {{ $skill['strand'] }}</span>
                        <div class="small muted">
                            @if ($r)
                                {{ $r->unaided_successes }} on your own · {{ $r->assisted_successes }} with help
                                @if ($r->checks_passed + $r->checks_failed) · quick checks {{ $r->checks_passed }}/{{ $r->checks_passed + $r->checks_failed }} @endif
                                @if ($r->last_unaided_at) · last on your own {{ $r->last_unaided_at->timezone('Australia/Melbourne')->format('j M') }} @endif
                            @else
                                No questions yet
                            @endif
                        </div>
                    </div>
                    <span class="state state-{{ $r->state ?? 'not_assessed' }}">{{ $r?->label() ?? 'Not yet assessed' }}</span>
                    @if (in_array($code, \App\Tutor\Skills::equationSkills(), true))
                        <a class="btn secondary" href="{{ route('tutor.practice', $code) }}">Practise</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    <section class="card">
        <h2 style="font-size:18px">How the states work</h2>
        <ul class="small" style="margin:0;padding-left:20px;display:flex;flex-direction:column;gap:6px">
            <li><strong>Developing</strong> — you've started; help is welcome.</li>
            <li><strong>Solved on your own</strong> — {{ config('tutor.independent_successes') }} correct with no help at all.</li>
            <li><strong>Remembered</strong> — still right on your own at least {{ config('tutor.retention_days') }} days later.</li>
            <li><strong>Needs foundations</strong> — the same mistake keeps coming up and the quick check on an earlier skill was missed. Practise that earlier skill first.</li>
        </ul>
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Tutor: '.$s->question)

@section('content')
    @php($checkResult = session('check_result'))
    <header class="tutor-head">
        <div class="row" style="gap:8px">
            <a class="small" href="{{ route('tutor.home') }}">← Tutor</a>
            <span class="pill maths">{{ $skill['name'] }}</span>
            <span class="pill {{ $s->isIndependent() ? 'solo' : '' }}">{{ $s->isIndependent() ? 'On your own' : 'Guided' }}</span>
        </div>
        <p class="small muted" style="margin:8px 0 0">Solve</p>
        <h1 class="eq eq-big" style="margin:0">{{ $s->question }}</h1>
        @if ($s->isIndependent() && ! $s->isSolved())
            <p class="small muted" style="margin:4px 0 0">No hints this time — show what you can do. If you get stuck, press "I'm stuck" and we'll do it together.</p>
        @endif
    </header>

    <section class="card tutor-feed" aria-label="Your working and the tutor's help">
        @if (empty($feed))
            <p class="muted" style="margin:0">Write your first line of working below, one step per line. For example, if the question has brackets, start by expanding them.</p>
        @endif
        <ol class="feed">
            @foreach ($feed as $i => $entry)
                @php($item = $entry['item'])
                @php($isLast = $i === count($feed) - 1)
                @if ($entry['type'] === 'line')
                    <li class="feed-line line-{{ $item->status }}" @if ($isLast) id="latest" aria-live="polite" @endif>
                        <div class="line-row">
                            <span class="line-mark" aria-hidden="true">{{ ['correct' => '✓', 'wrong' => '✗', 'unreadable' => '?'][$item->status] }}</span>
                            <span class="eq">{{ $item->read_as ?? $item->typed }}</span>
                            <span class="small line-status">
                                @if ($item->status === 'correct') {{ $item->is_final ? 'Correct — that\'s the answer' : 'Correct' }}
                                @elseif ($item->status === 'wrong') Doesn't follow from the line before
                                @else Couldn't read this line @endif
                            </span>
                        </div>
                        @if ($item->feedback)
                            <div class="tutor-says">{{ $item->feedback }}</div>
                        @endif
                    </li>
                @elseif ($entry['type'] === 'help')
                    <li class="feed-help" @if ($isLast) id="latest" aria-live="polite" @endif>
                        @if ($item->student_text)<div class="you-asked"><span class="small muted">You asked:</span> {{ $item->student_text }}</div>@endif
                        <div class="tutor-says">{{ $item->content }}</div>
                    </li>
                @else
                    <li class="feed-check" @if ($isLast) id="latest" aria-live="polite" @endif>
                        <div class="small" style="font-weight:600">Quick check on an earlier skill: {{ \App\Tutor\Skills::name($item->skill_code) }}</div>
                        <div class="small muted">Your mistake might come from this, so let's make sure.</div>
                        <div class="eq" style="font-size:20px;margin:6px 0">{{ $item->prompt }}</div>
                        @if ($item->isOpen())
                            <form method="POST" action="{{ route('tutor.check', $item) }}" class="row" style="gap:8px">
                                @csrf
                                <label class="visually-hidden" for="check{{ $item->id }}">Your answer</label>
                                <input id="check{{ $item->id }}" name="answer" class="eq-input" style="max-width:260px" autocomplete="off" autocapitalize="off" spellcheck="false" required>
                                <button class="btn" type="submit">Check</button>
                            </form>
                            @if ($checkResult && $checkResult['id'] === $item->id)
                                <div class="tutor-says" role="status">{{ $checkResult['message'] }}</div>
                            @endif
                        @else
                            <div class="line-row">
                                <span class="line-mark" aria-hidden="true">{{ $item->correct ? '✓' : '✗' }}</span>
                                <span class="eq">{{ $item->given }}</span>
                                <span class="small line-status">{{ $item->correct ? 'Correct' : 'Not quite — the answer is '.$item->answer }}</span>
                            </div>
                            @if ($checkResult && $checkResult['id'] === $item->id)
                                <div class="tutor-says" role="status">{{ $checkResult['message'] }}</div>
                            @endif
                        @endif
                    </li>
                @endif
            @endforeach
        </ol>
    </section>

    @if ($s->isSolved())
        <section class="card tutor-result" aria-labelledby="result-h">
            <h2 id="result-h">{{ $s->solvedUnaided() ? 'Solved on your own' : 'Solved with help' }}</h2>
            <p style="margin:0">
                @if ($s->solvedUnaided())
                    No hints, steps or corrections needed. That counts towards showing you can do this on your own.
                @else
                    You used help {{ $s->help_used }} {{ \Illuminate\Support\Str::plural('time', $s->help_used) }}. That's how learning works — now show you can do one alone.
                @endif
            </p>
            <p class="small muted" style="margin:0">{{ $skill['name'] }}: <strong>{{ $record->label() }}</strong> · {{ $record->unaided_successes }} on your own, {{ $record->assisted_successes }} with help.</p>
            <div class="row">
                <form method="POST" action="{{ route('tutor.next', $s) }}">
                    @csrf
                    <button class="btn" type="submit">Try a new one on my own</button>
                </form>
                <a class="btn secondary" href="{{ route('tutor.home') }}">Back to the tutor</a>
            </div>
        </section>
    @else
        <section class="card" aria-labelledby="work-h">
            <h2 id="work-h" style="font-size:18px">Your next line</h2>
            <form method="POST" action="{{ route('tutor.lines', $s) }}" class="tutor-form">
                @csrf
                <label for="working" class="small muted" style="margin:0">One step per line. You can type several lines at once.</label>
                <textarea id="working" name="working" class="eq-input" rows="2" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="1500" required placeholder="for example 6x − 8 = 10">{{ old('working') }}</textarea>
                @include('tutor._keys', ['for' => 'working'])
                <div><button class="btn" type="submit">Check my working</button></div>
            </form>
        </section>

        <section class="card" aria-labelledby="help-h">
            <h2 id="help-h" style="font-size:18px">{{ $s->isIndependent() ? 'Need help?' : 'Help, whenever you want it' }}</h2>
            <div class="help-buttons">
                @if ($s->isIndependent())
                    <form method="POST" action="{{ route('tutor.help', $s) }}">@csrf<input type="hidden" name="kind" value="stuck"><button class="btn secondary" type="submit">I'm stuck</button></form>
                @else
                    @foreach (['hint' => 'Give me a hint', 'step' => 'Show the next step', 'example' => 'Show an example', 'explain' => 'Explain it differently'] as $kind => $label)
                        <form method="POST" action="{{ route('tutor.help', $s) }}">@csrf<input type="hidden" name="kind" value="{{ $kind }}"><button class="btn secondary" type="submit">{{ $label }}</button></form>
                    @endforeach
                @endif
            </div>
            @unless ($s->isIndependent())
                <form method="POST" action="{{ route('tutor.help', $s) }}" class="tutor-form">
                    @csrf
                    <input type="hidden" name="kind" value="ask">
                    <label for="ask" class="small" style="margin:0">Or ask a question, like "why did you move the 5?"</label>
                    <div class="row" style="gap:8px;flex-wrap:nowrap">
                        <input id="ask" name="message" maxlength="500" style="flex:1;min-width:0" required>
                        <button class="btn secondary" type="submit">Ask</button>
                    </div>
                </form>
            @endunless
            <p class="small muted" style="margin:0">Using help is fine — it's recorded as "with help", and you'll get a fresh one to do on your own after.</p>
        </section>
    @endif
@endsection

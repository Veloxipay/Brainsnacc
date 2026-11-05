@extends('layouts.app')
@section('title', 'Maths tutor')

@section('content')
    <header>
        <p class="muted small" style="margin:0">Maths tutor · linear equations</p>
        <h1 style="margin:4px 0 0">What would you like help with today?</h1>
    </header>

    @if ($continue)
        <a class="card tutor-continue" href="{{ route('tutor.show', $continue) }}">
            <span class="small muted">Continue where you left off</span>
            <strong class="eq">{{ $continue->question }}</strong>
        </a>
    @endif

    <section class="card" aria-labelledby="hw-h">
        <h2 id="hw-h">Help with my homework</h2>
        <p class="muted" style="margin:0">Type the equation exactly as it is in your homework. I'll show you how I read it before we start.</p>
        <form method="POST" action="{{ route('tutor.read') }}" class="tutor-form">
            @csrf
            <label for="question" class="small" style="margin:0">Your question</label>
            <input id="question" name="question" class="eq-input" value="{{ old('question', request('question')) }}" placeholder="for example 2(3x − 4) = 10" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="200" required
                   @if (session('read_error')) aria-invalid="true" aria-describedby="read-error" @endif>
            @include('tutor._keys', ['for' => 'question'])
            @if (session('read_error'))
                <div id="read-error" class="feedback wrong" role="alert">
                    <strong>I couldn't read that yet.</strong>
                    <div>{{ session('read_error') }}</div>
                </div>
            @endif
            <div><button class="btn" type="submit">Read my question</button></div>
        </form>
        @if (Route::has('tutor.photo'))
            <p class="small" style="margin:0"><a href="{{ route('tutor.photo') }}">Or take a photo of your homework →</a></p>
        @endif
    </section>

    <section class="card" aria-labelledby="teach-h">
        <div class="row between">
            <h2 id="teach-h">Teach me linear equations</h2>
            <a class="small" href="{{ route('tutor.progress') }}">My progress →</a>
        </div>
        <p class="muted" style="margin:0">Start at the top, or pick the kind your class is doing. You'll get help as you go, then one to do on your own.</p>
        <ul class="skill-list">
            @foreach ($skills as $code => $skill)
                @php($record = $records->get($code))
                <li>
                    <div style="flex:1 1 220px">
                        <strong>{{ $skill['name'] }}</strong>
                        <div class="small muted">{{ $skill['objective'] }}</div>
                    </div>
                    <span class="state state-{{ $record->state ?? 'not_assessed' }}">{{ $record?->label() ?? 'Not yet assessed' }}</span>
                    <a class="btn secondary" href="{{ route('tutor.practice', $code) }}">Practise</a>
                </li>
            @endforeach
        </ul>
    </section>

    @if ($recent->isNotEmpty())
        <section class="card" aria-labelledby="recent-h">
            <h2 id="recent-h">Recent questions</h2>
            <ul class="skill-list">
                @foreach ($recent as $r)
                    <li>
                        <a class="eq" style="flex:1" href="{{ route('tutor.show', $r) }}">{{ $r->question }}</a>
                        <span class="small muted">
                            @if (! $r->isSolved()) In progress
                            @elseif ($r->solvedUnaided()) Solved on your own
                            @else Solved with help @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection

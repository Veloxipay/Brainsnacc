@extends('layouts.app')
@section('title', 'Check your question')

@section('content')
    <section class="card" aria-labelledby="confirm-h" style="max-width:640px">
        <h1 id="confirm-h" style="font-size:24px;margin:0">Is this your question?</h1>
        <p class="muted" style="margin:0">I read it as:</p>
        <p class="eq eq-big" style="margin:0">{{ $pending['read_as'] }}</p>
        <p class="small muted" style="margin:0">You typed: <span class="eq">{{ $pending['typed'] }}</span> · This is a question on <strong>{{ strtolower($skill['name']) }}</strong>.</p>
        <p style="margin:0">Check every sign, number and bracket matches your homework exactly.</p>
        <div class="row">
            <form method="POST" action="{{ route('tutor.store') }}">
                @csrf
                <input type="hidden" name="read_as" value="{{ $pending['read_as'] }}">
                <input type="hidden" name="source" value="{{ $pending['source'] === 'photo' ? 'photo' : 'homework' }}">
                <button class="btn" type="submit">Yes, that's right</button>
            </form>
            <a class="btn secondary" href="{{ route('tutor.home', ['question' => $pending['typed']]) }}#question">No, let me fix it</a>
        </div>
    </section>
@endsection

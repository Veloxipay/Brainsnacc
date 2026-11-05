@extends('layouts.app')
@section('title', 'Photo of your homework')

@section('content')
    <header>
        <a class="small" href="{{ route('tutor.home') }}">← Tutor</a>
        <h1 style="margin:4px 0 0">Photo of your homework</h1>
    </header>

    @if (session('read_error'))
        <div class="feedback wrong" role="alert"><strong>I couldn't use that one yet.</strong> <div>{{ session('read_error') }}</div></div>
    @endif

    @if ($found)
        <section class="card" aria-labelledby="found-h">
            <h2 id="found-h">Here's what I read</h2>
            <p class="muted" style="margin:0">Check each one against your sheet. Fix anything I got wrong, then choose the question you want help with.</p>
            <ul class="skill-list">
                @foreach ($found as $i => $q)
                    <li style="flex-direction:column;align-items:stretch">
                        <form method="POST" action="{{ route('tutor.read') }}" class="tutor-form">
                            @csrf
                            <input type="hidden" name="source" value="photo">
                            <label for="pq{{ $i }}" class="small" style="margin:0;font-weight:600">{{ $q['label'] !== '' ? 'Question '.$q['label'] : 'Question '.($i + 1) }}</label>
                            <input id="pq{{ $i }}" name="question" class="eq-input" value="{{ $q['read_as'] ?? $q['text'] }}" autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="200">
                            @if ($q['unclear'])
                                <div class="level-suggest small"><strong>Check this:</strong> I wasn't sure about: {{ $q['unclear'] }}.</div>
                            @endif
                            @if (! $q['supported'])
                                <div class="small muted">The tutor can't work line by line on this kind of question yet (it covers linear equations). If I misread it, fix it above.</div>
                            @endif
                            <div><button class="btn {{ $q['supported'] ? '' : 'secondary' }}" type="submit">Help me with this one</button></div>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="card" aria-labelledby="up-h">
        <h2 id="up-h">{{ $found ? 'Try another photo' : 'Take or choose a photo' }}</h2>
        @if (! $enabled)
            <p style="margin:0">Reading photos isn't switched on yet. <a href="{{ route('tutor.home') }}">Type your question instead →</a></p>
        @else
            <p class="muted" style="margin:0">Get the questions flat, bright and filling the frame. I'll read them and show you what I read before we start. The photo isn't kept.</p>
            <form method="POST" action="{{ route('tutor.photo.read') }}" enctype="multipart/form-data" class="tutor-form">
                @csrf
                <label for="photo" class="small" style="margin:0">Photo or PDF of your homework</label>
                <input id="photo" type="file" name="photo" accept="image/*,application/pdf" capture="environment" required>
                <div><button class="btn" type="submit">Read my homework</button></div>
            </form>
        @endif
    </section>
@endsection

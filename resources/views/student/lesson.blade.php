@extends('layouts.app')
@section('title', $lesson->title)

@section('content')
    @php($unit = $lesson->module->unit)
    @php($result = session('result'))
    @php($slides = $lesson->slides())
    @php($questions = $lesson->questions)

    <div class="row small muted">
        <a href="{{ route('course', [$subject, 'level' => $unit->level_id]) }}" style="font-weight:600">← Course map</a>
        <span>{{ $subject->name }} · {{ $unit->level->short_name }} · {{ $unit->code }} · {{ $lesson->module->title }}</span>
        @if ($position)<span style="margin-left:auto">Lesson {{ $position }} of {{ $count }}</span>@endif
    </div>

    @unless ($lesson->isPublished())
        <div class="errors">Draft — students can’t see this lesson yet.</div>
    @endunless

    {{-- Slide show: one section per screen. Without JavaScript every slide simply shows one after another. --}}
    <div class="deck" data-deck data-lesson="{{ $lesson->id }}" style="--accent: {{ $subject->color }}">
        <header class="deck-top">
            <h1 class="deck-title">{{ $lesson->title }}</h1>
            <label class="deck-jump">
                <span class="sr-only">Jump to</span>
                <select data-deck-menu aria-label="Jump to a part of the lesson"></select>
            </label>
        </header>
        <div class="deck-progress" aria-hidden="true"><span data-deck-bar></span></div>

        <div class="deck-stage" data-deck-stage tabindex="-1">
            @if ($lesson->video)
                <section class="slide" data-title="Watch the video">
                    <h2>Watch first</h2>
                    <figure class="lesson-video">
                        <video controls preload="metadata" playsinline src="{{ asset($lesson->video) }}"></video>
                        <figcaption class="small muted">A short video explainer with a real-life analogy. Then press Next to read the lesson.</figcaption>
                    </figure>
                </section>
            @endif

            @foreach ($slides as $slide)
                <section class="slide lesson-body" data-title="{{ $slide['title'] }}">
                    <h2>{{ $slide['section'] }}</h2>
                    {!! $slide['html'] !!}
                </section>
            @endforeach

            @foreach ($questions as $i => $question)
                <section class="slide" data-title="Question {{ $i + 1 }} of {{ $questions->count() }}">
                    @include('student._question', ['question' => $question, 'label' => 'Question '.($i + 1).' of '.$questions->count(), 'subject' => $subject, 'result' => $result])
                </section>
            @endforeach

            <section class="slide" data-title="Finish">
                <h2>{{ $completed ? 'Lesson done ✓' : 'Nearly there!' }}</h2>
                <p class="muted" style="margin:0">About {{ $lesson->minutes }} minutes · {{ $questions->count() }} practice questions</p>
                @if ($completed)
                    <p class="good" style="margin:0">You’ve finished this lesson.</p>
                @elseif ($lesson->isPublished())
                    <form method="POST" action="{{ route('lessons.complete', $lesson) }}">
                        @csrf
                        <button class="btn" type="submit">Mark lesson as done</button>
                    </form>
                @endif
                <div class="card" style="background:var(--ground)">
                    <strong>Ask the tutor</strong>
                    <span class="muted small">The AI tutor is coming in the next stage. It will give hints first, then full explanations once you’ve tried.</span>
                </div>
                <div class="row between">
                    @if ($previous)<a href="{{ route('lessons.show', $previous) }}" style="font-weight:600">← {{ $previous->title }}</a>@else<span></span>@endif
                    @if ($nextLesson)<a class="btn" href="{{ route('lessons.show', $nextLesson) }}">Next lesson: {{ $nextLesson->title }} →</a>@endif
                </div>
            </section>
        </div>

        <nav class="deck-bar" aria-label="Slides">
            <button type="button" class="btn secondary" data-deck-prev>← Back</button>
            <span class="small muted" data-deck-count aria-live="polite"></span>
            <button type="button" class="btn" data-deck-next>Next →</button>
        </nav>
    </div>

    <script>
    (function () {
        var deck = document.querySelector('[data-deck]');
        if (!deck) return;
        var slides = Array.prototype.slice.call(deck.querySelectorAll('.slide'));
        var stage = deck.querySelector('[data-deck-stage]');
        var menu = deck.querySelector('[data-deck-menu]');
        var bar = deck.querySelector('[data-deck-bar]');
        var count = deck.querySelector('[data-deck-count]');
        var prev = deck.querySelector('[data-deck-prev]');
        var next = deck.querySelector('[data-deck-next]');
        var key = 'brainsnacc-slide-' + deck.dataset.lesson;
        var current = 0;
        deck.classList.add('is-deck');

        slides.forEach(function (s, i) {
            s.id = s.id || ('slide-' + (i + 1));
            var o = document.createElement('option');
            o.value = i; o.textContent = (i + 1) + '. ' + s.dataset.title;
            menu.appendChild(o);
        });

        function show(i, focus) {
            current = Math.max(0, Math.min(slides.length - 1, i));
            slides.forEach(function (s, k) { s.hidden = k !== current; });
            menu.value = current;
            bar.style.width = ((current + 1) / slides.length * 100) + '%';
            count.textContent = (current + 1) + ' of ' + slides.length;
            prev.disabled = current === 0;
            next.disabled = current === slides.length - 1;
            stage.scrollTop = 0;
            slides.forEach(function (s, k) { if (k !== current) s.querySelectorAll('video').forEach(function (v) { v.pause(); }); });
            try { localStorage.setItem(key, String(current)); } catch (e) {}
            if (focus) stage.focus({ preventScroll: true });
        }

        prev.addEventListener('click', function () { show(current - 1, true); });
        next.addEventListener('click', function () { show(current + 1, true); });
        menu.addEventListener('change', function () { show(parseInt(menu.value, 10), true); });
        document.addEventListener('keydown', function (e) {
            var t = e.target.tagName;
            if (t === 'INPUT' || t === 'SELECT' || t === 'TEXTAREA' || t === 'VIDEO' || e.altKey || e.ctrlKey || e.metaKey) return;
            if (e.key === 'ArrowRight') { show(current + 1, true); e.preventDefault(); }
            if (e.key === 'ArrowLeft') { show(current - 1, true); e.preventDefault(); }
        });

        // Start where it makes sense: an answered question (#q123), otherwise where the student left off.
        var start = 0;
        var target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
        if (target) {
            slides.forEach(function (s, k) { if (s.contains(target)) start = k; });
        } else {
            try { start = parseInt(localStorage.getItem(key) || '0', 10) || 0; } catch (e) {}
        }
        show(start, false);
        if (target) deck.scrollIntoView({ block: 'start' });
    })();
    </script>
@endsection

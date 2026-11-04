@extends('layouts.app')
@section('title', $exam['title'])

@section('content')
    @php($md = fn ($t) => \App\Support\MockExams::md($t))
    <form method="POST" action="{{ route('mock-exams.submit', $attempt) }}" data-exam
          data-attempt="{{ $attempt->id }}"
          data-started="{{ $attempt->created_at->getTimestamp() }}"
          data-now="{{ now()->getTimestamp() }}"
          data-reading="{{ ($exam['reading_minutes'] ?? 15) * 60 }}"
          data-writing="{{ ($exam['writing_minutes'] ?? 150) * 60 }}">
        @csrf

        <div class="mx-timer" role="timer" aria-live="off">
            <strong data-phase>Reading time</strong>
            <span data-clock>--:--</span>
            <span class="small muted" data-answered></span>
            <button type="button" class="btn secondary small" data-skip>Start writing now</button>
            <button type="submit" class="btn small" data-handin>Hand in</button>
        </div>

        <header>
            <h1 style="margin:0">{{ $exam['title'] }}</h1>
            <p class="muted" style="margin:4px 0 0">Answer all questions. Section A: {{ count($exam['section_a']) }} multiple-choice questions ({{ $exam['marks_a'] }} marks). Section B: written answers ({{ $exam['marks_b'] }} marks).</p>
        </header>

        <h2 class="mx-section">Section A — Multiple choice</h2>
        @foreach ($exam['section_a'] as $i => $q)
            <fieldset class="card mx-q" id="a{{ $i + 1 }}">
                <legend class="pill">Question {{ $i + 1 }}</legend>
                <div class="lesson-body">{!! $md($q['prompt']) !!}</div>
                @foreach ($q['options'] as $letter => $text)
                    <label class="option" style="font-weight:400;margin:0">
                        <input type="radio" name="a[{{ $i }}]" value="{{ $letter }}">
                        <strong>{{ $letter }}</strong><span style="flex:1">{{ $text }}</span>
                    </label>
                @endforeach
            </fieldset>
        @endforeach

        <h2 class="mx-section">Section B — Written answers</h2>
        @foreach ($exam['section_b'] as $q)
            <section class="card mx-q" id="b{{ $q['number'] }}">
                <span class="pill">Question {{ $q['number'] }} ({{ collect($q['parts'])->sum('marks') }} marks)</span>
                @if (! empty($q['stem']))<div class="lesson-body">{!! $md($q['stem']) !!}</div>@endif
                @foreach ($q['parts'] as $part)
                    @php($key = \App\Support\MockExams::partKey($q, $part))
                    <div class="mx-part">
                        <div class="lesson-body"><strong>{{ $part['label'] }}.</strong> {!! $md($part['prompt']) !!} <span class="muted small">({{ $part['marks'] }} {{ \Illuminate\Support\Str::plural('mark', $part['marks']) }})</span></div>
                        <textarea name="b[{{ $key }}]" rows="{{ min(12, 2 + $part['marks'] * 2) }}" aria-label="Answer to question {{ $key }}"></textarea>
                    </div>
                @endforeach
            </section>
        @endforeach

        <div class="card" style="align-items:flex-start">
            <strong>Finished?</strong>
            <span class="muted small">Check you’ve answered every question, then hand in. You can’t change answers after this.</span>
            <button type="submit" class="btn">Hand in my exam</button>
        </div>
    </form>

    <script>
    (function () {
        var form = document.querySelector('[data-exam]');
        var key = 'brainsnacc-exam-' + form.dataset.attempt;
        var reading = +form.dataset.reading, writing = +form.dataset.writing;
        var offset = +form.dataset.now * 1000 - Date.now();
        var started = +form.dataset.started * 1000;
        var phase = form.querySelector('[data-phase]'), clock = form.querySelector('[data-clock]');
        var skip = form.querySelector('[data-skip]'), answered = form.querySelector('[data-answered]');
        var fields = form.querySelectorAll('input[type=radio], textarea');
        var skipAt = null, submitting = false;

        // Restore answers saved on this device.
        try {
            var saved = JSON.parse(localStorage.getItem(key) || '{}');
            skipAt = typeof saved.__skipAt === 'number' ? saved.__skipAt : null;
            fields.forEach(function (f) {
                if (f.type === 'radio') { if (saved[f.name] === f.value) f.checked = true; }
                else if (saved[f.name]) f.value = saved[f.name];
            });
        } catch (e) {}

        function save() {
            var data = { __skipAt: skipAt };
            fields.forEach(function (f) { if (f.type === 'radio') { if (f.checked) data[f.name] = f.value; } else if (f.value) data[f.name] = f.value; });
            try { localStorage.setItem(key, JSON.stringify(data)); } catch (e) {}
            var a = form.querySelectorAll('input[type=radio]:checked').length;
            var b = Array.prototype.filter.call(form.querySelectorAll('textarea'), function (t) { return t.value.trim(); }).length;
            answered.textContent = 'Answered: ' + a + ' MC · ' + b + ' written';
        }
        form.addEventListener('input', save); form.addEventListener('change', save);
        skip.addEventListener('click', function () { skipAt = (Date.now() + offset - started) / 1000; save(); tick(); });

        function fmt(s) { s = Math.max(0, Math.floor(s)); var h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), x = s % 60; return (h ? h + ':' + String(m).padStart(2, '0') : m) + ':' + String(x).padStart(2, '0'); }
        function tick() {
            var elapsed = (Date.now() + offset - started) / 1000;
            var writeFrom = skipAt !== null ? Math.min(skipAt, reading) : reading; // skipping reading time starts the writing clock early
            var inReading = elapsed < writeFrom;
            var writingLeft = writing - (elapsed - writeFrom);
            fields.forEach(function (f) { f.disabled = inReading; });
            skip.hidden = !inReading;
            if (inReading) { phase.textContent = 'Reading time (no writing yet)'; clock.textContent = fmt(reading - elapsed); }
            else if (writingLeft > 0) { phase.textContent = 'Writing time left'; clock.textContent = fmt(writingLeft); clock.classList.toggle('bad', writingLeft < 600); }
            else if (!submitting) { phase.textContent = 'Time’s up — handing in'; clock.textContent = '0:00'; submitting = true; fields.forEach(function (f) { f.disabled = false; }); form.submit(); }
        }
        form.addEventListener('submit', function (e) {
            fields.forEach(function (f) { f.disabled = false; });
            if (submitting) return;
            var blankA = form.querySelectorAll('fieldset.mx-q').length - form.querySelectorAll('input[type=radio]:checked').length;
            if (blankA > 0 && !confirm(blankA + ' multiple-choice questions are unanswered. Hand in anyway?')) { e.preventDefault(); tick(); return; }
            submitting = true;
            try { localStorage.removeItem(key); } catch (x) {}
        });
        save(); tick(); setInterval(tick, 1000);
    })();
    </script>
@endsection

@extends('layouts.app')
@section('title', $lesson->title)

@section('content')
    @php($unit = $lesson->module->unit)
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&display=swap" rel="stylesheet">

    @php($slides = $lesson->slides())
    <div class="row small muted">
        <a href="{{ route('course', [$subject, 'level' => $unit->level_id]) }}" style="font-weight:600">← Course map</a>
        <span>{{ $subject->name }} · {{ $unit->level->short_name }} · {{ $unit->code }} · {{ $lesson->module->title }}</span>
    </div>
    @unless ($lesson->isPublished())
        <div class="errors">Draft — students can’t see this lesson yet.</div>
    @endunless

    <div class="teach" data-teach data-csrf="{{ csrf_token() }}" data-lesson="{{ $lesson->id }}" style="--accent: {{ $subject->color }}">
        <header class="teach-top">
            <div>
                <p class="small muted" style="margin:0">Step by step</p>
                <h1 class="deck-title">{{ $lesson->title }}</h1>
            </div>
            <div class="teach-tools">
                <label class="toggle"><input type="checkbox" data-voice checked> Read aloud</label>
            </div>
        </header>
        <ol class="teach-path" aria-label="Where you are">
            @if ($lesson->video || $slides || $script || $topicVideo)<li data-path="topic">The topic</li>@endif
            @foreach ($plan['stages'] as $i => $stage)
                <li data-path="stage{{ $i }}">{{ $i + 1 }}. {{ $stage['title'] }}</li>
            @endforeach
            @if ($plan['mixed']->isNotEmpty())<li data-path="mix">Mix it up</li>@endif
            @if ($plan['test']->isNotEmpty())<li data-path="test">Quick test</li>@endif
        </ol>

        <noscript><p class="errors">This lesson needs JavaScript turned on in your browser.</p></noscript>

        <div class="teach-stage" data-stage tabindex="-1">
            {{-- 0: the topic — video and the lesson, read first --}}
            @if ($homework)
                <div class="homework-note" role="note" style="margin-bottom:16px">
                    <strong>This is homework{{ $homework->setter ? ' from '.$homework->setter->name : '' }}</strong>{{ $homework->due_on ? ' · due '.$homework->due_on->format('l j M') : '' }}
                    @if ($homework->note)<div class="small">“{{ $homework->note }}”</div>@endif
                </div>
            @endif
            @if ($lesson->video && ! $script)
                <section class="tstep" data-kind="read" data-path="topic" hidden>
                    <h2>Watch first</h2>
                    <figure class="lesson-video">
                        <video controls preload="metadata" playsinline src="{{ asset($lesson->video) }}"></video>
                    </figure>
                </section>
            @endif
            @if ($topicVideo)
                {{-- The topic as a video: the teacher writes and draws on the board and talks it through --}}
                <section class="tstep" data-kind="video" data-path="topic" hidden>
                    <h2>Watch and write along</h2>
                    <figure class="lesson-video">
                        <video controls preload="metadata" playsinline src="{{ asset($topicVideo) }}" data-topic-video></video>
                    </figure>
                    <p class="small muted" style="margin:0" data-video-tip>Pause any time to copy the board into your book. Next unlocks when the video ends.</p>
                </section>
            @elseif ($script)
                {{-- The topic at the board: one short sentence at a time, with what to write and a picture where it helps --}}
                @foreach ($script as $f => $frame)
                    <section class="tstep" data-kind="learn" data-path="topic" hidden>
                        <p class="pill" style="align-self:flex-start">The topic · {{ $f + 1 }} of {{ count($script) }}</p>
                        <h2 class="t-prompt">{{ $frame['heading'] }}</h2>
                        <div class="board board-topic" aria-live="polite">
                            @foreach ($frame['lines'] as $line)
                                <div class="board-line topic-line" data-line data-say="{{ $line['say'] ?? $line['write'] }}" hidden>
                                    @if ($line['write'])<span class="ink">{{ $line['write'] }}</span>@endif
                                    @if ($line['draw'])<div class="board-draw">{!! $line['draw'] !!}</div>@endif
                                    @if ($line['diagram'])
                                        <figure class="board-pic"><img src="{{ asset($line['diagram']) }}" alt="{{ $line['alt'] ?: 'Diagram' }}"></figure>
                                    @endif
                                    @if ($line['say'])<span class="say">{{ $line['say'] }}</span>@endif
                                </div>
                            @endforeach
                        </div>
                        <div class="row player" style="gap:10px;flex-wrap:wrap;align-items:center">
                            <button type="button" class="btn" data-play>▶ Play</button>
                            <button type="button" class="btn secondary" data-say-again hidden>Say it again</button>
                            <button type="button" data-next-line hidden></button>
                            <span class="player-bar" aria-hidden="true"><span data-bar></span></span>
                        </div>
                        <p class="small muted" style="margin:0" data-line-tip>Press Play. It writes and talks you through it. Pause any time to copy it into your book.</p>
                    </section>
                @endforeach
            @else
                @foreach ($slides as $slide)
                    <section class="tstep lesson-body" data-kind="read" data-path="topic" hidden>
                        <h2>{{ $slide['section'] }}</h2>
                        {!! $slide['html'] !!}
                    </section>
                @endforeach
            @endif

            {{-- 1–3: watch and write, then your turn --}}
            @foreach ($plan['stages'] as $i => $stage)
                @php($ex = $stage['example'])
                <section class="tstep" data-kind="learn" data-path="stage{{ $i }}" hidden>
                    <p class="pill" style="align-self:flex-start">Part {{ $i + 1 }} · {{ $stage['title'] }}</p>
                    <h2>{{ $i === 0 ? 'Watch me do one. Write it in your book with me.' : 'Now with more steps. Write along again.' }}</h2>
                    <p class="t-prompt">{{ $ex->prompt }}</p>
                    @include('student._diagram', ['question' => $ex])
                    <div class="board" aria-live="polite">
                        @foreach (array_values($ex->steps ?? []) as $n => $line)
                            <div class="board-line" data-line hidden><span class="board-n">{{ $n + 1 }}</span><span class="ink">{{ $line }}</span></div>
                        @endforeach
                        <div class="board-line board-answer" data-line hidden><span class="board-n">✓</span><span class="ink">Answer: {{ \App\Support\Teach::answerText($ex) }}</span></div>
                    </div>
                    <div class="row" style="gap:10px;flex-wrap:wrap">
                        <button type="button" class="btn" data-next-line>Write the first step</button>
                        <button type="button" class="btn secondary" data-say-again hidden>Say it again</button>
                    </div>
                    <p class="small muted" style="margin:0" data-line-tip>Copy each step as it appears. Press the button when you’ve written it.</p>
                </section>

                @foreach ($stage['tries'] as $q)
                    @include('student._teach_question', ['q' => $q, 'mode' => 'try', 'path' => 'stage'.$i, 'heading' => 'Your turn. Do this one in your book.'])
                @endforeach
            @endforeach

            {{-- 4: mixed --}}
            @if ($plan['mixed']->isNotEmpty())
                <section class="tstep" data-kind="info" data-path="mix" hidden>
                    <h2>Mix it up</h2>
                    <p>{{ $plan['mixed']->count() }} questions that use the steps in different ways. Some are short, some need every step. Work out which steps each one needs.</p>
                </section>
                @foreach ($plan['mixed'] as $n => $q)
                    @include('student._teach_question', ['q' => $q, 'mode' => 'mix', 'path' => 'mix', 'heading' => 'Mixed question '.($n + 1).' of '.$plan['mixed']->count()])
                @endforeach
            @endif

            {{-- 5: quick test --}}
            @if ($plan['test']->isNotEmpty())
            <section class="tstep" data-kind="info" data-path="test" hidden>
                <h2>Quick test</h2>
                <p>{{ $plan['test']->count() }} questions on your own. No help, and you’ll see your score at the end.</p>
            </section>
            @endif
            @foreach ($plan['test'] as $n => $q)
                @include('student._teach_question', ['q' => $q, 'mode' => 'test', 'path' => 'test', 'heading' => 'Test question '.($n + 1).' of '.$plan['test']->count()])
            @endforeach

            <section class="tstep" data-kind="end" data-path="test" hidden>
                <h2>All done!</h2>
                <p class="t-score" data-score></p>
                <ul class="t-review" data-review></ul>
                @if ($lesson->isPublished())
                    <form method="POST" action="{{ route('lessons.complete', $lesson) }}" data-finish>
                        @csrf
                        <button class="btn" type="submit">{{ $homework ? 'Hand in homework' : 'Finish the lesson' }}</button>
                    </form>
                @endif
                @if ($nextLesson)<a href="{{ route('lessons.show', $nextLesson) }}">Next lesson: {{ $nextLesson->title }} →</a>@endif
            </section>
        </div>

        <details class="pad-wrap">
            <summary>Write on the screen instead of in your book</summary>
            <canvas class="pad" data-pad aria-label="Writing pad"></canvas>
            <button type="button" class="btn secondary small" data-pad-clear>Clear</button>
        </details>

        <nav class="deck-bar teach-bar" aria-label="Steps">
            <button type="button" class="btn secondary" data-prev>← Back</button>
            <span class="small muted" data-count aria-live="polite"></span>
            <button type="button" class="btn" data-next>Next →</button>
        </nav>
    </div>

    <script>
    (function () {
        var root = document.querySelector('[data-teach]');
        if (!root) return;
        var steps = Array.prototype.slice.call(root.querySelectorAll('.tstep'));
        var prev = root.querySelector('[data-prev]'), next = root.querySelector('[data-next]'), count = root.querySelector('[data-count]');
        var voiceBox = root.querySelector('[data-voice]');
        var csrf = root.dataset.csrf;
        var current = 0, results = [];
        var saveKey = 'brainsnacc-steps-' + root.dataset.lesson;
        function save() { try { localStorage.setItem(saveKey, JSON.stringify({ at: current, results: results })); } catch (e) {} }

        /* ---- voice out: the browser's own voice, Australian if there is one ---- */
        var synth = window.speechSynthesis, voice = null;
        function pickVoice() {
            if (!synth) return;
            var vs = synth.getVoices();
            voice = vs.filter(function (v) { return /en-AU/i.test(v.lang); })[0] || vs.filter(function (v) { return /^en/i.test(v.lang); })[0] || null;
        }
        if (synth) { pickVoice(); synth.onvoiceschanged = pickVoice; } else { voiceBox.closest('label').hidden = true; }
        function sayable(t) {
            return String(t).replace(/−|–/g, ' minus ').replace(/×/g, ' times ').replace(/÷/g, ' divided by ')
                .replace(/=/g, ' equals ').replace(/\^2|²/g, ' squared ').replace(/\^3|³/g, ' cubed ')
                .replace(/(\d)\s*\/\s*(\d)/g, '$1 over $2').replace(/\s-\s/g, ' minus ').replace(/√/g, ' the square root of ');
        }
        function say(t) {
            if (!synth || !voiceBox.checked || !t) return;
            synth.cancel();
            var u = new SpeechSynthesisUtterance(sayable(t));
            if (voice) u.voice = voice;
            u.lang = voice ? voice.lang : 'en-AU';
            u.rate = 0.95;
            synth.speak(u);
        }
        voiceBox.addEventListener('change', function () { if (!voiceBox.checked && synth) synth.cancel(); });

        /* ---- voice in: say your answer (where the browser supports it) ---- */
        var Rec = window.SpeechRecognition || window.webkitSpeechRecognition;

        /* ---- moving through the steps ---- */
        function canLeave(s) {
            if (s.dataset.kind === 'video') return s.dataset.watched === '1';
            if (s.dataset.kind === 'learn') return s.dataset.written === '1';
            if (s.dataset.kind === 'q') return s.dataset.done === '1';
            return true;
        }
        function show(i) {
            current = Math.max(0, Math.min(steps.length - 1, i));
            steps.forEach(function (s, k) { s.hidden = k !== current; });
            var s = steps[current];
            root.querySelectorAll('.teach-path li').forEach(function (li) {
                li.classList.toggle('on', li.dataset.path === s.dataset.path);
            });
            prev.disabled = current === 0 || s.dataset.mode === 'test' || s.dataset.kind === 'end';
            next.hidden = s.dataset.kind === 'end';
            next.disabled = !canLeave(s);
            count.textContent = (current + 1) + ' of ' + steps.length;
            root.querySelector('[data-stage]').focus({ preventScroll: true });
            var top = root.getBoundingClientRect().top;
            if (top < 0 || top > window.innerHeight * 0.4) root.scrollIntoView({ block: 'start' });
            if (synth) synth.cancel();
            if (s.dataset.kind === 'q' && s.dataset.done !== '1') {
                say(s.querySelector('.t-prompt').textContent + '. What answer did you get?');
                var input = s.querySelector('[data-said]');
                if (input && window.innerWidth > 760) input.focus({ preventScroll: true });
            }
            if (s.dataset.kind === 'learn' && s.dataset.started !== '1' && !s.querySelector('.board-topic')) say(s.querySelector('.t-prompt').textContent);
            // The topic plays like a video: carry on playing into the next screen of the topic.
            if (s.querySelector('.board-topic')) { if (playing) { clearTimeout(timer); timer = setTimeout(step, 700); } else { updatePlay(); } }
            if (s.dataset.kind === 'info') say(s.querySelector('h2').textContent + '. ' + s.querySelector('p').textContent);
            if (s.dataset.kind === 'end') finish(s);
            var hw = root.querySelector('.homework-note'); if (hw) hw.hidden = current !== 0;
            root.querySelectorAll('video').forEach(function (v) { if (!s.contains(v)) v.pause(); });
            save();
        }
        prev.addEventListener('click', function () { show(current - 1); });
        next.addEventListener('click', function () { if (canLeave(steps[current])) { if (typeof stop === 'function' && !isTopic(steps[current + 1])) stop(); show(current + 1); } });

        /* ---- watch and write: one line at a time ---- */
        steps.filter(function (s) { return s.dataset.kind === 'learn'; }).forEach(function (s) {
            var lines = Array.prototype.slice.call(s.querySelectorAll('[data-line]'));
            var btn = s.querySelector('[data-next-line]'), again = s.querySelector('[data-say-again]');
            var shown = 0;
            var topic = !!s.querySelector('.board-topic'), bar = s.querySelector('[data-bar]');
            // Show the next line; returns it, or null when the board is full.
            s._reveal = function () {
                s.dataset.started = '1';
                if (shown >= lines.length) return null;
                var line = lines[shown++];
                line.hidden = false;
                line.classList.add('writing');
                again.hidden = false;
                if (bar) bar.style.width = (shown / lines.length * 100) + '%';
                if (shown === lines.length) {
                    s.dataset.written = '1';
                    if (!steps[current] || steps[current] === s) next.disabled = false;
                }
                return line;
            };
            s._lines = lines;
            s._replay = function () { shown = 0; if (bar) bar.style.width = '0%'; };
            btn.addEventListener('click', function () {
                if (shown >= lines.length) return;
                var line = s._reveal();
                say(line.dataset.say || line.querySelector('.ink').textContent);
                if (topic) return;
                if (shown === lines.length) {
                    btn.textContent = 'I’ve written it all ✓';
                    btn.disabled = true;
                    s.querySelector('[data-line-tip]').textContent = 'Check your book matches the board, then press Next.';
                } else {
                    btn.textContent = 'I’ve written it. Next step';
                }
            });
            again.addEventListener('click', function () {
                var last = lines[Math.max(0, shown - 1)];
                say(last.dataset.say || last.querySelector('.ink').textContent);
            });
        });

        /* ---- topic video: Next unlocks once it has been watched to the end ---- */
        root.querySelectorAll('[data-topic-video]').forEach(function (v) {
            var s = v.closest('.tstep');
            function done() { s.dataset.watched = '1'; if (steps[current] === s) next.disabled = false; s.querySelector('[data-video-tip]').textContent = 'Well done. Press Next to try it yourself.'; }
            v.addEventListener('ended', done);
            v.addEventListener('timeupdate', function () { if (v.duration && v.currentTime >= v.duration - 1.5) done(); });
            v.addEventListener('play', function () { if (synth) synth.cancel(); });
        });

        /* ---- the topic plays like a video: write a line, say it, give time to copy it, next line ---- */
        var playing = false, timer = null;
        function isTopic(s) { return s && !!s.querySelector('.board-topic'); }
        function sayThen(text, done) {
            var finished = false;
            function fin() { if (finished) return; finished = true; clearTimeout(timer); if (playing) done(); }
            if (!synth || !voiceBox.checked || !text) { timer = setTimeout(fin, 1400 + String(text).length * 55); return; }
            synth.cancel();
            var u = new SpeechSynthesisUtterance(sayable(text));
            if (voice) u.voice = voice;
            u.lang = voice ? voice.lang : 'en-AU';
            u.rate = 0.95;
            u.onend = fin; u.onerror = fin;
            timer = setTimeout(fin, 3000 + String(text).length * 110); // in case the browser never says it's finished
            synth.speak(u);
        }
        function step() {
            if (!playing) return;
            var s = steps[current];
            if (!isTopic(s)) { stop(); return; }
            var line = s._reveal();
            if (line) {
                var ink = line.querySelector('.ink');
                sayThen(line.dataset.say || (ink ? ink.textContent : ''), function () {
                    // Time to copy what was written into their book.
                    timer = setTimeout(step, ink ? 1200 + ink.textContent.length * 70 : 600);
                });
            } else if (isTopic(steps[current + 1])) {
                timer = setTimeout(function () { if (playing) show(current + 1); }, 900);
            } else {
                stop();
                next.disabled = false;
                s.querySelector('[data-line-tip]').textContent = 'That’s the topic. Press Next to try it yourself.';
            }
        }
        function updatePlay() {
            root.querySelectorAll('[data-play]').forEach(function (b) {
                var st = b.closest('.tstep');
                var full = st.dataset.written === '1';
                b.textContent = playing ? '❚❚ Pause' : (full ? '↺ Play again' : (st.dataset.started === '1' ? '▶ Carry on' : '▶ Play'));
            });
        }
        function stop() { playing = false; clearTimeout(timer); if (synth) synth.cancel(); updatePlay(); }
        root.querySelectorAll('[data-play]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (playing) { stop(); return; }
                var s = b.closest('.tstep');
                if (s.dataset.written === '1') {
                    // Play this screen again from the top.
                    s._lines.forEach(function (l) { l.hidden = true; l.classList.remove('writing'); });
                    s._replay();
                }
                playing = true; updatePlay(); step();
            });
        });
        prev.addEventListener('click', stop);

        /* ---- "What answer did you get?" ---- */
        steps.filter(function (s) { return s.dataset.kind === 'q'; }).forEach(function (s) {
            var form = s.querySelector('[data-answer]'), input = s.querySelector('[data-said]');
            var fb = s.querySelector('[data-feedback]'), choices = s.querySelector('[data-choices]');
            var mic = s.querySelector('[data-mic]'), mode = s.dataset.mode;

            function send(body) {
                body.mode = mode;
                fb.hidden = false;
                fb.className = 't-feedback';
                fb.textContent = 'Checking…';
                return fetch(s.dataset.check, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(body)
                }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                  .then(function (res) { answered(res); })
                  .catch(function () { fb.className = 't-feedback wrong'; fb.textContent = 'That didn’t send. Check your internet and try again.'; });
            }
            function answered(res) {
                if (res.result === 'unmatched') {
                    fb.className = 't-feedback';
                    fb.textContent = 'I couldn’t match that to an answer. Which one did you get?';
                    say('Which one did you get?');
                    choices.hidden = false;
                    return;
                }
                s.dataset.done = '1';
                next.disabled = false;
                form.querySelectorAll('input,button').forEach(function (el) { el.disabled = true; });
                choices.querySelectorAll('button').forEach(function (el) { el.disabled = true; });
                results = results.filter(function (r) { return r.id !== s.dataset.check + mode; });
                results.push({ id: s.dataset.check + mode, prompt: s.querySelector('.t-prompt').textContent, mode: mode, right: res.result === 'correct', given: res.given, answer: res.answer });
                save();
                fb.innerHTML = '';
                if (mode === 'test') {
                    fb.className = 't-feedback';
                    fb.textContent = 'Saved. Press Next for the next one.';
                    return;
                }
                fb.className = 't-feedback ' + (res.result === 'correct' ? 'right' : 'wrong');
                var head = document.createElement('strong');
                if (res.result === 'correct') {
                    head.textContent = 'Yes! ' + res.answer + ' is right.';
                    say('Yes! ' + res.answer + ' is right. Well done.');
                } else {
                    head.textContent = 'Not quite. You got ' + res.given + '. The answer is ' + res.answer + '.';
                    say('Not quite. The answer is ' + res.answer + '. Let\'s look at the steps.');
                }
                fb.appendChild(head);
                if (res.mistake) { var m = document.createElement('div'); m.textContent = res.mistake; fb.appendChild(m); }
                if (res.steps && res.steps.length) {
                    var wrap = res.result === 'correct' ? document.createElement('details') : document.createElement('div');
                    if (res.result === 'correct') { var sm = document.createElement('summary'); sm.textContent = 'Check your steps'; wrap.appendChild(sm); }
                    else { var p = document.createElement('div'); p.className = 'small'; p.textContent = 'Fix your working in your book so it matches:'; wrap.appendChild(p); }
                    var ol = document.createElement('ol'); ol.className = 'board board-small';
                    res.steps.forEach(function (t) { var li = document.createElement('li'); li.className = 'ink'; li.textContent = t; ol.appendChild(li); });
                    wrap.appendChild(ol);
                    fb.appendChild(wrap);
                }
            }
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!input.value.trim()) { input.focus(); return; }
                send({ said: input.value.trim() });
            });
            s.querySelector('[data-show-choices]').addEventListener('click', function () { choices.hidden = false; this.hidden = true; });
            choices.querySelectorAll('[data-letter]').forEach(function (b) {
                b.addEventListener('click', function () { send({ letter: b.dataset.letter }); });
            });

            if (Rec && mic) {
                mic.hidden = false;
                mic.addEventListener('click', function () {
                    var rec = new Rec();
                    rec.lang = 'en-AU';
                    rec.interimResults = false;
                    rec.maxAlternatives = 1;
                    if (synth) synth.cancel();
                    mic.classList.add('listening');
                    mic.textContent = 'Listening…';
                    rec.onresult = function (e) {
                        input.value = e.results[0][0].transcript;
                        send({ said: input.value });
                    };
                    rec.onerror = function () { fb.hidden = false; fb.className = 't-feedback'; fb.textContent = 'I didn’t catch that. Try again, or type it.'; };
                    rec.onend = function () { mic.classList.remove('listening'); mic.textContent = '🎤 Say it'; };
                    try { rec.start(); } catch (err) { rec.onend(); }
                });
            }
        });

        /* ---- the end: score and what to look at again ---- */
        function finish(s) {
            var test = results.filter(function (r) { return r.mode === 'test'; });
            var right = test.filter(function (r) { return r.right; }).length;
            s.querySelector('[data-score]').textContent = test.length
                ? 'Test score: ' + right + ' out of ' + test.length + (right === test.length ? '. Perfect!' : right / test.length >= 0.6 ? '. Good work.' : '. Go through the lesson again, then try once more.')
                : '';
            var list = s.querySelector('[data-review]');
            list.innerHTML = '';
            test.forEach(function (r) {
                var li = document.createElement('li');
                li.className = r.right ? 'good' : 'bad';
                li.textContent = (r.right ? '✓ ' : '✗ ') + r.prompt + (r.right ? '' : ' Answer: ' + r.answer + ' (you said ' + r.given + ')');
                list.appendChild(li);
            });
            say(s.querySelector('[data-score]').textContent);
        }

        /* ---- writing pad ---- */
        var pad = root.querySelector('[data-pad]');
        if (pad) {
            var ctx = pad.getContext('2d'), drawing = false;
            function size() {
                var r = pad.getBoundingClientRect(), d = window.devicePixelRatio || 1;
                if (!r.width) return;
                pad.width = r.width * d; pad.height = r.height * d;
                ctx.setTransform(d, 0, 0, d, 0, 0);
                ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#141A22';
            }
            root.querySelector('.pad-wrap').addEventListener('toggle', size);
            window.addEventListener('resize', size);
            function pos(e) { var r = pad.getBoundingClientRect(); return [e.clientX - r.left, e.clientY - r.top]; }
            pad.addEventListener('pointerdown', function (e) { drawing = true; pad.setPointerCapture(e.pointerId); var p = pos(e); ctx.beginPath(); ctx.moveTo(p[0], p[1]); });
            pad.addEventListener('pointermove', function (e) { if (!drawing) return; var p = pos(e); ctx.lineTo(p[0], p[1]); ctx.stroke(); });
            ['pointerup', 'pointercancel'].forEach(function (t) { pad.addEventListener(t, function () { drawing = false; }); });
            root.querySelector('[data-pad-clear]').addEventListener('click', function () { ctx.clearRect(0, 0, pad.width, pad.height); });
        }

        var form = root.querySelector('[data-finish]');
        if (form) form.addEventListener('submit', function () { try { localStorage.removeItem(saveKey); } catch (e) {} });

        // Pick up where they left off: everything before that point counts as done.
        var start = 0;
        try {
            var saved = JSON.parse(localStorage.getItem(saveKey) || 'null');
            if (saved && saved.at > 0 && saved.at < steps.length) {
                start = saved.at;
                results = saved.results || [];
                steps.slice(0, start).forEach(function (s) {
                    s.dataset.written = '1'; s.dataset.done = '1'; s.dataset.started = '1'; s.dataset.watched = '1';
                    s.querySelectorAll('[data-line]').forEach(function (l) { l.hidden = false; });
                    var b = s.querySelector('[data-next-line]'); if (b) { b.textContent = 'I’ve written it all ✓'; b.disabled = true; }
                    s.querySelectorAll('[data-answer] input, [data-answer] button, [data-choices] button').forEach(function (el) { el.disabled = true; });
                });
            }
        } catch (e) {}
        show(start);
    })();
    </script>
@endsection

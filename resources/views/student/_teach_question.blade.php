{{-- One "What answer did you get?" question in Teach me. Needs: $q, $mode (try|mix|test), $path, $heading. --}}
<section class="tstep" data-kind="q" data-mode="{{ $mode }}" data-path="{{ $path }}" data-check="{{ route('teach.check', $q) }}" hidden>
    <p class="pill" style="align-self:flex-start">{{ $heading }}</p>
    <p class="t-prompt">{{ $q->prompt }}</p>
    @include('student._diagram', ['question' => $q])

    <form class="t-answer" data-answer>
        <label for="said{{ $q->id }}-{{ $mode }}" style="margin:0">What answer did you get?</label>
        <div class="row" style="gap:8px;flex-wrap:wrap">
            <input id="said{{ $q->id }}-{{ $mode }}" data-said autocomplete="off" autocapitalize="off" spellcheck="false" maxlength="200" style="flex:1 1 180px" placeholder="Type your answer">
            <button type="button" class="btn secondary" data-mic hidden>🎤 Say it</button>
            <button class="btn" type="submit">Check</button>
        </div>
    </form>
    <button type="button" class="linklike small" data-show-choices style="align-self:flex-start">Show the choices instead</button>
    <div class="t-choices" data-choices hidden>
        @foreach ($q->options ?? [] as $letter => $text)
            <button type="button" class="option" data-letter="{{ $letter }}"><strong>{{ $letter }}</strong> <span>{{ $text }}</span></button>
        @endforeach
    </div>
    <div class="t-feedback" data-feedback role="status" hidden></div>
</section>

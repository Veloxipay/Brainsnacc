{{--
    One practice question card.
    Needs: $question, $label, $subject. Optional: $result (flashed answer result), $help (id of the help page's question).
    The correct answer is only shown once it has been earned: a right answer, or a second wrong try.
--}}
@php($mine = isset($result) && $result && $result['question_id'] === $question->id ? $result : null)
@php($reveal = $mine && ($mine['correct'] || ($mine['reveal'] ?? false)))
<section class="card" id="q{{ $question->id }}" aria-labelledby="qp{{ $question->id }}">
    <span class="pill {{ $subject->slug === 'biology' ? 'bio' : '' }}" style="align-self:flex-start">{{ $label }} · {{ $question->marks }} {{ \Illuminate\Support\Str::plural('mark', $question->marks) }}</span>
    <p id="qp{{ $question->id }}" style="margin:0;font-size:18px;font-weight:600">{{ $question->prompt }}</p>

    <form method="POST" action="{{ route('questions.answer', $question) }}" style="display:flex;flex-direction:column;gap:10px">
        @csrf
        @isset($help)<input type="hidden" name="help" value="{{ $help }}">@endisset
        <fieldset style="border:0;padding:0;margin:0;display:flex;flex-direction:column;gap:10px">
            <legend class="small muted" style="margin-bottom:8px">Choose one answer</legend>
            @foreach ($question->options ?? [] as $letter => $text)
                @php($state = $mine && $reveal && $letter === $question->answer ? 'correct' : ($mine && ! $mine['correct'] && $letter === $mine['answer'] ? 'wrong' : ''))
                <label class="option {{ $state }}" style="font-weight:400;margin:0">
                    <input type="radio" name="answer" value="{{ $letter }}" @checked($mine && $reveal && $mine['answer'] === $letter) required>
                    <strong>{{ $letter }}</strong>
                    <span style="flex:1">{{ $text }}</span>
                    @if ($state === 'correct')<span class="good small">Correct</span>@endif
                    @if ($state === 'wrong')<span class="bad small">Your answer</span>@endif
                </label>
            @endforeach
        </fieldset>

        @if ($mine)
            <div class="feedback {{ $mine['correct'] ? 'right' : 'wrong' }}" role="status">
                @if ($mine['correct'])
                    <strong>Correct!</strong>
                    @if ($question->explanation)<div>{{ $question->explanation }}</div>@endif
                @elseif ($reveal)
                    <strong>Not quite — here’s how it works.</strong>
                    @if ($question->explanation)<div>{{ $question->explanation }}</div>@endif
                    <div class="small" style="margin-top:6px">This one stays on your mistakes list until you get it right. Come back to it tomorrow.</div>
                @else
                    <strong>Not quite. Have another go.</strong>
                    <div>Read the question again carefully and think about why your answer doesn’t fit. If you get it wrong again, we’ll go over the topic together.</div>
                @endif
            </div>
        @endif

        <div><button class="btn" type="submit">{{ $mine ? 'Try again' : 'Check answer' }}</button></div>
    </form>
</section>

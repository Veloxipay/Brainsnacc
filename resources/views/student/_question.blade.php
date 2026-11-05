{{--
    One practice question card.
    Needs: $question, $label, $subject. Optional: $result (flashed answer result), $help (id of the help page's question).
    The correct answer is only shown once it has been earned: a right answer, or a second wrong try.
--}}
@php($mine = isset($result) && $result && $result['question_id'] === $question->id ? $result : null)
@php($reveal = $mine && ($mine['correct'] || ($mine['reveal'] ?? false)))
@php($check = session('stepcheck'))
@php($check = $check && $check['question_id'] === $question->id ? $check : null)
@php($level = $question->levelLabel())
@php($qhelp = \App\Support\QuestionHelp::state($question))
@php($maxHints = \App\Support\QuestionHelp::maxHints($question))
@php($tutorEq = $subject->slug === 'maths' ? \App\Support\QuestionHelp::tutorEquation($question) : null)
<section class="card" id="q{{ $question->id }}" aria-labelledby="qp{{ $question->id }}">
    <span class="pill {{ ['biology' => 'bio', 'maths' => 'maths'][$subject->slug] ?? '' }}" style="align-self:flex-start">{{ $label }}@if ($level) · <span class="level level-{{ $question->difficulty }}">{{ $level }}</span>@endif · {{ $question->marks }} {{ \Illuminate\Support\Str::plural('mark', $question->marks) }}</span>
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
                    @include('student._question_steps', ['question' => $question])
                @elseif ($reveal)
                    <strong>Not quite — here’s how it works.</strong>
                    @include('student._question_steps', ['question' => $question])
                    <div class="small" style="margin-top:6px">This one stays on your mistakes list until you get it right. Come back to it tomorrow.</div>
                @else
                    <strong>Not quite. Have another go.</strong>
                    @if ($check)
                        <div class="small" style="margin-top:6px">You wrote:</div>
                        <div class="steps-mine">{{ $check['steps'] }}</div>
                        <div class="steps-feedback">{{ $check['feedback'] }}</div>
                    @else
                        <div>What steps did you follow? Write them below and we’ll find the step that went wrong. Or read the question again and have another go.</div>
                    @endif
                @endif
            </div>
        @endif

        <div><button class="btn" type="submit">{{ $mine ? 'Try again' : 'Check answer' }}</button></div>
    </form>

    @if ($mine && ! empty($mine['suggest']))
        <div class="level-suggest" role="status">
            {{ $mine['suggest']['text'] }}
            <a href="{{ route('lessons.show', $question->lesson_id) }}#q{{ $mine['suggest']['target'] }}">Go to it →</a>
        </div>
    @endif

    @unless ($mine && $reveal)
        @if ($qhelp['hints'] > 0 || $qhelp['explain'])
            <div class="q-hints" aria-live="polite">
                @if ($qhelp['explain'])
                    <div class="tutor-says"><strong>What this question is asking:</strong> {{ $qhelp['explain'] }}</div>
                @endif
                @if ($qhelp['hints'] > 0)
                    <ol class="steps-list">
                        @foreach (array_slice($question->steps ?? [], 0, $qhelp['hints']) as $hint)
                            <li>{{ $hint }}</li>
                        @endforeach
                    </ol>
                    @if ($qhelp['hints'] >= $maxHints)
                        <div class="small muted">That's all the hints — the last step is yours.</div>
                    @endif
                @endif
            </div>
        @endif
        <div class="q-help-row">
            @if ($qhelp['hints'] < $maxHints)
                <form method="POST" action="{{ route('questions.hint', $question) }}">
                    @csrf
                    @isset($help)<input type="hidden" name="help" value="{{ $help }}">@endisset
                    <button class="btn secondary small" type="submit">{{ $qhelp['hints'] ? 'Next hint' : 'Show a hint' }}</button>
                </form>
            @endif
            @unless ($qhelp['explain'])
                <form method="POST" action="{{ route('questions.explain', $question) }}">
                    @csrf
                    @isset($help)<input type="hidden" name="help" value="{{ $help }}">@endisset
                    <button class="btn secondary small" type="submit">I don't understand the question</button>
                </form>
            @endunless
            @if ($tutorEq)
                <form method="POST" action="{{ route('tutor.store') }}">
                    @csrf
                    <input type="hidden" name="read_as" value="{{ $tutorEq }}">
                    <input type="hidden" name="source" value="lesson">
                    <button class="btn secondary small" type="submit">Work it out line by line</button>
                </form>
            @endif
        </div>
        @if ($maxHints > 0 || $qhelp['explain'])
            <div class="small muted">Hints are free. Answers given after a hint count as "with help" — try the next one without.</div>
        @endif
    @endunless

    @if ($mine && ! $mine['correct'] && ! $reveal)
        <form method="POST" action="{{ route('questions.steps', $question) }}" class="steps-form">
            @csrf
            @isset($help)<input type="hidden" name="help" value="{{ $help }}">@endisset
            <input type="hidden" name="answer" value="{{ $mine['answer'] }}">
            <label for="steps{{ $question->id }}" style="margin:0">{{ $check ? 'Still stuck? Write your new steps' : 'What steps did you follow?' }}</label>
            <textarea id="steps{{ $question->id }}" name="steps" rows="4" maxlength="2000" required
                      placeholder="One step per line, with your numbers. For example:&#10;Step 1: 3x + 7 = 31&#10;Step 2: take 7 from both sides, 3x = 24"></textarea>
            <div><button class="btn secondary" type="submit">Check my steps</button></div>
        </form>
    @endif
</section>

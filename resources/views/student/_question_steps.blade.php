{{-- The reasoning behind a question's answer: the explanation, then the worked steps (if the question has them). --}}
@if ($question->explanation)<div>{{ $question->explanation }}</div>@endif
@if (! empty($question->steps))
    <div class="small" style="margin-top:8px;font-weight:600">Step by step</div>
    <ol class="steps-list">
        @foreach ($question->steps as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ol>
@endif

{{-- A question's diagram, if it has one. --}}
@if ($question->diagram)
    <figure class="q-diagram">
        <img src="{{ asset($question->diagram) }}" alt="{{ $question->diagram_alt ?: 'Diagram for this question' }}" loading="lazy">
    </figure>
@endif

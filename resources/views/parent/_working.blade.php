{{-- One Maths tutor problem, line by line, as the student typed it. Needs: $session (with lines). --}}
<div class="small"><span class="muted">Problem:</span> <strong>{{ $session->question }}</strong></div>
@if ($session->lines->isEmpty())
    <p class="small muted" style="margin:0">No working typed yet.</p>
@else
    <ol class="working-lines">
        @foreach ($session->lines as $line)
            <li class="{{ $line->status === 'correct' ? 'good' : ($line->status === 'wrong' ? 'bad' : 'muted') }}">
                <code>{{ $line->typed }}</code>
                @if ($line->status === 'wrong' && $line->feedback)<div class="small">{{ $line->feedback }}</div>@endif
                @if ($line->status === 'unreadable')<span class="small"> (couldn’t read this line)</span>@endif
            </li>
        @endforeach
    </ol>
@endif

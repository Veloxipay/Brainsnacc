@extends('layouts.app')
@section('title', $lesson->exists ? 'Edit: '.$lesson->title : 'New lesson')

@section('content')
    <header class="row between">
        <div>
            <a class="small" href="{{ route('admin.lessons.index') }}">← All lessons</a>
            <h1>{{ $lesson->exists ? $lesson->title : 'New lesson' }}</h1>
        </div>
        @if ($lesson->exists)
            <a class="btn secondary" href="{{ route('lessons.show', $lesson) }}">Preview as student</a>
        @endif
    </header>

    <form method="POST" action="{{ $lesson->exists ? route('admin.lessons.update', $lesson) : route('admin.lessons.store') }}" class="card">
        @csrf
        @if ($lesson->exists) @method('PUT') @endif

        <div class="form-grid">
            <div class="field" style="grid-column:1/-1">
                <label for="module_id">Where it goes</label>
                <select id="module_id" name="module_id" required>
                    @foreach ($modules as $id => $label)
                        <option value="{{ $id }}" @selected(old('module_id', $lesson->module_id) == $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="title">Title</label><input id="title" type="text" name="title" value="{{ old('title', $lesson->title) }}" required></div>
            <div class="field"><label for="minutes">Minutes</label><input id="minutes" type="number" name="minutes" min="1" max="240" value="{{ old('minutes', $lesson->minutes) }}" required></div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" @selected(old('status', $lesson->status) === 'draft')>Draft (hidden)</option>
                    <option value="published" @selected(old('status', $lesson->status) === 'published')>Live</option>
                </select>
            </div>
            <div class="field" style="grid-column:1/-1"><label for="summary">One-line summary</label><input id="summary" type="text" name="summary" value="{{ old('summary', $lesson->summary) }}"></div>
            <div class="field" style="grid-column:1/-1">
                <label for="body">Lesson notes</label>
                <textarea id="body" name="body" rows="18">{{ old('body', $lesson->body) }}</textarea>
                <span class="muted small" style="margin-top:6px">Write in Markdown: <code>## Heading</code>, <code>**bold**</code>, <code>- list item</code>.</span>
            </div>
        </div>
        <div><button class="btn" type="submit">{{ $lesson->exists ? 'Save lesson' : 'Create lesson' }}</button></div>
    </form>

    @if ($lesson->exists)
        <section class="card" id="questions" aria-labelledby="qh">
            <h2 id="qh">Practice questions ({{ $lesson->questions->count() }})</h2>

            @foreach ($lesson->questions as $i => $q)
                <div style="border:1px solid var(--line);border-radius:12px;padding:16px">
                    <div class="row between">
                        <strong>{{ $i + 1 }}. {{ $q->prompt }}</strong>
                        <form method="POST" action="{{ route('admin.questions.destroy', $q) }}" onsubmit="return confirm('Remove this question?')">
                            @csrf @method('DELETE')
                            <button class="btn danger" type="submit">Remove</button>
                        </form>
                    </div>
                    <ul class="small" style="margin:8px 0 0">
                        @foreach ($q->options ?? [] as $letter => $text)
                            <li class="{{ $letter === $q->answer ? 'good' : '' }}">{{ $letter }}. {{ $text }}@if ($letter === $q->answer) ✓@endif</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <form method="POST" action="{{ route('admin.questions.store', $lesson) }}" style="display:flex;flex-direction:column;gap:12px;border-top:1px solid var(--line);padding-top:16px">
                @csrf
                <h3 style="margin:0;font-size:16px">Add a multiple-choice question</h3>
                <div class="field"><label for="prompt">Question</label><input id="prompt" type="text" name="prompt" value="{{ old('prompt') }}" required></div>
                <div class="form-grid">
                    @foreach (['A', 'B', 'C', 'D'] as $letter)
                        <div class="field">
                            <label for="opt{{ $letter }}">Option {{ $letter }}{{ in_array($letter, ['A', 'B']) ? '' : ' (optional)' }}</label>
                            <input id="opt{{ $letter }}" type="text" name="options[{{ $letter }}]" value="{{ old('options.'.$letter) }}" @if (in_array($letter, ['A', 'B'])) required @endif>
                        </div>
                    @endforeach
                    <div class="field">
                        <label for="answer">Correct answer</label>
                        <select id="answer" name="answer">
                            @foreach (['A', 'B', 'C', 'D'] as $letter)
                                <option value="{{ $letter }}" @selected(old('answer') === $letter)>{{ $letter }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="field"><label for="explanation">Why that’s the answer</label><input id="explanation" type="text" name="explanation" value="{{ old('explanation') }}"></div>
                <div><button class="btn" type="submit">Add question</button></div>
            </form>
        </section>
    @endif
@endsection

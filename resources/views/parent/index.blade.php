@extends('layouts.app')
@section('title', 'My children')

@section('content')
    <header>
        <p class="muted small" style="margin:0">This week · from {{ $since->format('l j F') }}</p>
        <h1>How they’re going</h1>
    </header>

    @forelse ($rows as $row)
        @php($child = $row['child'])
        <section class="card" aria-labelledby="c-{{ $child->id }}">
            <div class="row between">
                <h2 id="c-{{ $child->id }}" style="font-size:22px">{{ $child->name }}</h2>
                <span class="muted small">{{ $child->level?->name }}</span>
            </div>

            <div class="grid-4">
                <div><div class="muted small">Lessons finished</div><div class="big-number">{{ $row['lessonsThisWeek'] }}</div></div>
                <div><div class="muted small">Questions answered</div><div class="big-number">{{ $row['questionsThisWeek'] }}</div></div>
                <div><div class="muted small">Average score</div><div class="big-number">{{ $row['accuracy'] !== null ? $row['accuracy'].'%' : '—' }}</div></div>
                <div><div class="muted small">Days studied</div><div class="big-number">{{ $row['daysThisWeek'] }} of 7</div></div>
            </div>

            <div>
                <h3 style="font-size:16px;margin:0 0 8px">Needs more work</h3>
                @forelse ($row['weak'] as $w)
                    <div class="row between"><span>{{ $w['lesson']->title }}</span><span class="bad">{{ $w['score'] }}%</span></div>
                @empty
                    <p class="muted small" style="margin:0">Nothing flagged. Topics under 60% will show here.</p>
                @endforelse
            </div>
        </section>
    @empty
        <section class="card">
            <p class="muted" style="margin:0">No children linked to your account yet.</p>
        </section>
    @endforelse

    <p class="muted small" style="margin:0">Tutor chats stay private to your child.</p>
@endsection

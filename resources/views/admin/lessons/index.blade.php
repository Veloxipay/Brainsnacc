@extends('layouts.app')
@section('title', 'Lessons')

@section('content')
    <header class="row between">
        <h1>Lessons</h1>
        <a class="btn" href="{{ route('admin.lessons.create') }}">+ New lesson</a>
    </header>

    @foreach ($levels as $level)
        <h2 style="font-size:22px">{{ $level->name }}</h2>
        @foreach ($level->units as $unit)
            <section class="card">
                <div class="row between">
                    <h2>{{ $unit->subject->name }} · {{ $unit->code }} · {{ $unit->title }}</h2>
                </div>
                @foreach ($unit->modules as $module)
                    <div>
                        <div class="row between">
                            <strong>{{ $module->title }}</strong>
                            <a class="small" href="{{ route('admin.lessons.create', ['module' => $module->id]) }}">+ Add lesson</a>
                        </div>
                        @forelse ($module->lessons as $lesson)
                            <div class="row between small" style="padding:6px 0">
                                <a href="{{ route('admin.lessons.edit', $lesson) }}">{{ $lesson->title }}</a>
                                <span class="{{ $lesson->isPublished() ? 'good' : 'muted' }}">{{ $lesson->isPublished() ? 'Live' : 'Draft' }}</span>
                            </div>
                        @empty
                            <p class="muted small" style="margin:4px 0">No lessons yet.</p>
                        @endforelse
                    </div>
                @endforeach
            </section>
        @endforeach
    @endforeach
@endsection

@extends('layouts.app')
@section('title', 'Course structure')

@section('content')
    <header>
        <h1>Course structure</h1>
        <p class="muted" style="margin:4px 0 0">Levels → units → modules. Rename anything, change the order number, or add new units and modules. Lessons are added from the Lessons screen.</p>
    </header>

    <form method="POST" action="{{ route('admin.units.store') }}" class="card">
        @csrf
        <h2>Add a unit</h2>
        <div class="form-grid">
            <div class="field">
                <label for="new-level">Level</label>
                <select id="new-level" name="level_id" required>
                    @foreach ($levels as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="new-subject">Subject</label>
                <select id="new-subject" name="subject_id" required>
                    @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div class="field"><label for="new-code">Code</label><input id="new-code" type="text" name="code" placeholder="e.g. CHM2911" required></div>
            <div class="field"><label for="new-title">Title</label><input id="new-title" type="text" name="title" required></div>
        </div>
        <div><button class="btn" type="submit">Add unit</button></div>
    </form>

    @foreach ($levels as $level)
        <h2 style="font-size:22px">{{ $level->name }}</h2>

        @foreach ($level->units as $unit)
            <section class="card" aria-label="{{ $unit->subject->name }} {{ $unit->code }}">
                <form method="POST" action="{{ route('admin.units.update', $unit) }}" class="row" style="align-items:flex-end">
                    @csrf @method('PUT')
                    <span class="pill {{ ['biology' => 'bio', 'maths' => 'maths', 'selective' => 'sel'][$unit->subject->slug] ?? '' }}">{{ $unit->subject->name }}</span>
                    <div class="field" style="flex:0 1 140px"><label for="uc{{ $unit->id }}">Code</label><input id="uc{{ $unit->id }}" type="text" name="code" value="{{ $unit->code }}" required></div>
                    <div class="field" style="flex:1 1 320px"><label for="ut{{ $unit->id }}">Unit title</label><input id="ut{{ $unit->id }}" type="text" name="title" value="{{ $unit->title }}" required></div>
                    <div class="field" style="flex:0 1 90px"><label for="us{{ $unit->id }}">Order</label><input id="us{{ $unit->id }}" type="number" min="0" name="sort" value="{{ $unit->sort }}" required></div>
                    <button class="btn secondary" type="submit">Save unit</button>
                </form>

                <div style="display:flex;flex-direction:column;gap:8px">
                    @foreach ($unit->modules as $module)
                        <form method="POST" action="{{ route('admin.modules.update', $module) }}" class="row" style="align-items:flex-end;padding-left:16px;border-left:3px solid var(--line)">
                            @csrf @method('PUT')
                            <div class="field" style="flex:0 1 90px"><label for="ms{{ $module->id }}">Order</label><input id="ms{{ $module->id }}" type="number" min="0" name="sort" value="{{ $module->sort }}" required></div>
                            <div class="field" style="flex:1 1 360px"><label for="mt{{ $module->id }}">Module</label><input id="mt{{ $module->id }}" type="text" name="title" value="{{ $module->title }}" required></div>
                            <span class="small muted" style="padding-bottom:12px">{{ $module->lessons_count }} {{ \Illuminate\Support\Str::plural('lesson', $module->lessons_count) }}</span>
                            <button class="btn secondary" type="submit">Save</button>
                            <a class="small" style="padding-bottom:12px" href="{{ route('admin.lessons.create', ['module' => $module->id]) }}">+ Lesson</a>
                        </form>
                    @endforeach

                    <form method="POST" action="{{ route('admin.modules.store', $unit) }}" class="row" style="align-items:flex-end;padding-left:16px">
                        @csrf
                        <div class="field" style="flex:1 1 360px"><label for="nm{{ $unit->id }}">New module</label><input id="nm{{ $unit->id }}" type="text" name="title" placeholder="Module title" required></div>
                        <button class="btn" type="submit">Add module</button>
                    </form>
                </div>
            </section>
        @endforeach
    @endforeach
@endsection

@extends('layouts.app')
@section('title', $me->isTeacher() ? 'My students' : ($me->isAdmin() ? 'All students' : 'My children'))

@section('content')
    <header class="row between">
        <div>
            <p class="muted small" style="margin:0">This week runs from {{ $since->format('l j F') }}</p>
            <h1 style="margin:0">{{ $me->isTeacher() ? 'My students' : ($me->isAdmin() ? 'All students' : 'My children') }}</h1>
        </div>
        <a class="btn" href="{{ route('family.students.create') }}">+ Add a student</a>
    </header>

    @if ($rows->isEmpty())
        <section class="card">
            <h2 style="margin:0;font-size:20px">No students yet</h2>
            <p class="muted" style="margin:0">Create an account for each {{ $me->isTeacher() ? 'student' : 'child' }}. You choose their login email and password, and their progress shows up here.</p>
            <div><a class="btn" href="{{ route('family.students.create') }}">Add your first student</a></div>
        </section>
    @else
        <section class="card" style="padding:0;overflow:hidden">
            <div class="table-scroll">
                <table class="progress-table">
                    <thead>
                        <tr>
                            <th scope="col">Student</th>
                            <th scope="col">Course progress</th>
                            <th scope="col">This week</th>
                            <th scope="col">Average score</th>
                            <th scope="col">Last active</th>
                            <th scope="col">Needs work</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php($s = $row['student'])
                            <tr>
                                <td>
                                    <a href="{{ route('family.students.show', $s) }}" style="font-weight:600">{{ $s->name }}</a>
                                    <div class="small muted">{{ $s->level?->short_name ?? 'No year set' }}</div>
                                </td>
                                <td style="min-width:170px">
                                    <div class="small">{{ $row['lessonsDone'] }} of {{ $row['lessonsTotal'] }} lessons · {{ $row['percent'] }}%</div>
                                    <div class="bar" style="margin-top:6px"><span style="width:{{ $row['percent'] }}%"></span></div>
                                </td>
                                <td class="small">{{ $row['lessonsThisWeek'] }} {{ \Illuminate\Support\Str::plural('lesson', $row['lessonsThisWeek']) }}<br>{{ $row['questionsThisWeek'] }} questions</td>
                                <td>
                                    @if ($row['accuracy'] === null)
                                        <span class="muted">—</span>
                                    @else
                                        <span class="score-pill {{ $row['accuracy'] >= 75 ? 'good' : ($row['accuracy'] >= 60 ? 'ok' : 'low') }}">{{ $row['accuracy'] }}%</span>
                                    @endif
                                </td>
                                <td class="small">{{ $row['lastActive'] ? $row['lastActive']->diffForHumans() : 'Not started' }}</td>
                                <td class="small">
                                    @forelse ($row['weak'] as $w)
                                        <div>{{ $w['lesson']->title }} <span class="bad">{{ $w['score'] }}%</span></div>
                                    @empty
                                        <span class="muted">Nothing flagged</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <p class="muted small" style="margin:0">Click a name to see full progress, change their year level or reset their password.</p>
    @endif
@endsection

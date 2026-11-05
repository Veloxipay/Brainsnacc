@extends('layouts.app')
@section('title', 'Add a student')

@section('content')
    <div class="row small muted"><a href="{{ route('family') }}" style="font-weight:600">← Back</a></div>
    <h1 style="margin:0">Add a student</h1>
    <section class="card" style="max-width:560px">
        <form method="POST" action="{{ route('family.students.store') }}" style="display:flex;flex-direction:column;gap:14px">
            @csrf
            <label>Student’s name
                <input name="name" value="{{ old('name') }}" required autocomplete="off">
            </label>
            <label>Login email
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="off">
                <span class="small muted">The student logs in with this. It must be an email nobody else uses on Brainsnacc.</span>
            </label>
            <label>Year level
                <select name="level_id" required>
                    <option value="">Choose…</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Password for the student
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
            </label>
            <label>Type the password again
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </label>
            <span class="small muted">Give the student their email and password. They can change the password later under “My account”.</span>
            <div><button class="btn" type="submit">Create student account</button></div>
        </form>
    </section>
@endsection

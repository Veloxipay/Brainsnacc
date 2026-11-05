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
            <label>Username (for logging in)
                <input name="username" value="{{ old('username') }}" required minlength="3" maxlength="30" pattern="[a-z0-9._\-]+" autocapitalize="none" spellcheck="false" placeholder="e.g. mahek" data-username>
                <span class="small muted">Short and easy: lower-case letters and numbers, no spaces. The student types this to log in.</span>
            </label>
            <label>Email <span class="small muted">(optional)</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="off">
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
            <span class="small muted">Give the student their username and password. They can change the password later under “My account”.</span>
            <div><button class="btn" type="submit">Create student account</button></div>
        </form>
    </section>
    <script>
        // Suggest a username from the first name, e.g. "Mahek Dhawan" -> "mahek".
        (function () {
            var name = document.querySelector('input[name=name]'), user = document.querySelector('[data-username]'), touched = !!user.value;
            user.addEventListener('input', function () { touched = true; user.value = user.value.toLowerCase().replace(/[^a-z0-9._-]/g, ''); });
            name.addEventListener('input', function () { if (!touched) user.value = (name.value.trim().split(/\s+/)[0] || '').toLowerCase().replace(/[^a-z0-9]/g, ''); });
        })();
    </script>
@endsection

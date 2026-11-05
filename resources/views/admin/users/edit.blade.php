@extends('layouts.app')
@section('title', 'Edit '.$user->name)

@section('content')
    <div class="row small muted"><a href="{{ route('admin.users.index') }}" style="font-weight:600">← Accounts</a></div>
    <header>
        <h1 style="margin:0">{{ $user->name }}</h1>
        <p class="muted" style="margin:0">{{ ucfirst($user->role) }} · logs in with <strong>{{ $user->loginName() }}</strong>@if ($user->created_at) · joined {{ $user->created_at->timezone('Australia/Melbourne')->format('j M Y') }}@endif</p>
    </header>

    @php($isMe = $user->is(auth()->user()))
    <form method="POST" action="{{ route('admin.users.update', $user) }}" style="display:flex;flex-direction:column;gap:16px;max-width:620px">
        @csrf
        @method('PUT')

        <section class="card">
            <h2 style="margin:0;font-size:20px">Reset password</h2>
            <label style="display:flex;gap:10px;align-items:flex-start;font-weight:400">
                <input type="radio" name="temporary" value="1" checked style="margin-top:4px" data-pw-mode>
                <span><strong>Make a temporary password</strong><br><span class="small muted">Brainsnacc creates one and shows it to you once. Give it to {{ $user->name }}; they change it under My account.</span></span>
            </label>
            <label style="display:flex;gap:10px;align-items:flex-start;font-weight:400">
                <input type="radio" name="temporary" value="0" style="margin-top:4px" data-pw-mode>
                <span><strong>Type a new password myself</strong></span>
            </label>
            <div data-pw-fields hidden class="pw-fields">
                <label>New password <input type="password" name="password" minlength="8" autocomplete="new-password"></label>
                <label>Type it again <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password"></label>
            </div>
            <label style="display:flex;gap:10px;align-items:flex-start;font-weight:400">
                <input type="radio" name="temporary" value="keep" style="margin-top:4px" data-pw-mode>
                <span>Don’t change the password</span>
            </label>
        </section>

        <section class="card">
            <h2 style="margin:0;font-size:20px">Account details</h2>
            <label>Name <input name="name" value="{{ old('name', $user->name) }}" required></label>
            <label>Username <span class="small muted">(optional, short login name)</span>
                <input name="username" value="{{ old('username', $user->username) }}" minlength="3" maxlength="30" pattern="[a-z0-9._\-]+" autocapitalize="none" spellcheck="false">
            </label>
            <label>Email <span class="small muted">(optional for students)</span>
                <input type="email" name="email" value="{{ old('email', $user->email) }}">
            </label>
            @unless ($isMe)
                <label>Role
                    <select name="role" data-role>
                        @foreach (['student' => 'Student', 'parent' => 'Parent', 'teacher' => 'Teacher', 'admin' => 'Admin'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endunless
            <label data-level>Year level
                <select name="level_id">
                    <option value="">—</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected(old('level_id', $user->level_id) == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </label>
        </section>

        <div><button class="btn" type="submit">Save</button></div>
    </form>

    <script>
    (function () {
        var fields = document.querySelector('[data-pw-fields]');
        document.querySelectorAll('[data-pw-mode]').forEach(function (r) {
            r.addEventListener('change', function () { fields.hidden = r.value !== '0' || !r.checked; });
        });
        var role = document.querySelector('[data-role]'), level = document.querySelector('[data-level]');
        function sync() { level.hidden = role ? role.value !== 'student' : {{ $user->isStudent() ? 'false' : 'true' }}; }
        if (role) role.addEventListener('change', sync);
        sync();
    })();
    </script>
@endsection

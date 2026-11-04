@extends('layouts.app')
@section('title', 'New account')

@section('content')
    <header class="row between">
        <h1>New account</h1>
        <a href="{{ route('admin.users.index') }}">← Accounts</a>
    </header>

    <form method="POST" action="{{ route('admin.users.store') }}" class="card">
        @csrf
        <div class="form-grid">
            <div class="field"><label for="name">Name</label><input id="name" type="text" name="name" value="{{ old('name') }}" required></div>
            <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required></div>
            <div class="field"><label for="password">Password (8+ characters)</label><input id="password" type="password" name="password" required autocomplete="new-password"></div>
            <div class="field">
                <label for="role">Account type</label>
                <select id="role" name="role">
                    <option value="student" @selected(old('role', 'student') === 'student')>Student</option>
                    <option value="parent" @selected(old('role') === 'parent')>Parent</option>
                </select>
            </div>
            <div class="field">
                <label for="level_id">Level (students)</label>
                <select id="level_id" name="level_id">
                    <option value="">—</option>
                    @foreach ($levels as $l)
                        <option value="{{ $l->id }}" @selected(old('level_id') == $l->id)>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="parent_id">Parent (students)</label>
                <select id="parent_id" name="parent_id">
                    <option value="">—</option>
                    @foreach ($parents as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="muted small" style="margin:0">Tip: create the parent account first, then the children, so you can link them.</p>
        <div><button class="btn" type="submit">Create account</button></div>
    </form>
@endsection

@extends('layouts.app')
@section('title', 'My account')

@section('content')
    <h1 style="margin:0">My account</h1>
    <section class="card" style="max-width:560px">
        <form method="POST" action="{{ route('account.update') }}" style="display:flex;flex-direction:column;gap:14px">
            @csrf
            @method('PUT')
            <label>Name
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
            </label>
            <label>Username <span class="small muted">(short login name, optional)</span>
                <input name="username" value="{{ old('username', $user->username) }}" minlength="3" maxlength="30" pattern="[a-z0-9._\-]+" autocapitalize="none" spellcheck="false" placeholder="e.g. puneet">
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email', $user->email) }}" @unless ($user->isStudent()) required @endunless autocomplete="email">
            </label>
            <span class="small muted">You can log in with either your username or your email.</span>
            <fieldset style="border:1px solid var(--line);border-radius:12px;padding:12px 16px;display:flex;flex-direction:column;gap:12px">
                <legend class="small muted">New password (leave blank to keep your current one)</legend>
                <label>New password
                    <input type="password" name="password" autocomplete="new-password" minlength="10">
                </label>
                <label>Type the new password again
                    <input type="password" name="password_confirmation" autocomplete="new-password" minlength="10">
                </label>
                <span class="small muted">At least 10 characters, with letters and numbers.</span>
            </fieldset>
            <label>Current password <span class="small muted">(needed to save any change)</span>
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>
            <div><button class="btn" type="submit">Save changes</button></div>
        </form>
    </section>
@endsection

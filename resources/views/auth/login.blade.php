<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login">
    <form method="POST" action="{{ url('/login') }}" class="card">
        @csrf
        <img class="auth-logo" src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" width="260" height="66">
        <p class="muted" style="margin:0">Chemistry and Biology, Year 10 to University Year 1.</p>

        @if ($errors->any())
            <div class="errors" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>
        <label style="display:flex;gap:8px;align-items:center;font-weight:400">
            <input type="checkbox" name="remember" value="1"> Keep me signed in
        </label>
        <button type="submit" class="btn">Log in</button>
        <p class="small muted" style="margin:0">New here? <a href="{{ route('register') }}">Create an account</a> — for parents, teachers and students.</p>
    </form>
</div>
</body>
</html>

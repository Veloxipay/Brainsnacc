<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password · {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login">
    <form method="POST" action="{{ route('password.email') }}" class="card">
        @csrf
        <img class="auth-logo" src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" width="260" height="66">
        <h1 style="font-size:24px">Forgot your password?</h1>
        <p class="muted" style="margin:0">Type the email on your account and we’ll send you a link to choose a new password.</p>
        @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="errors" role="alert">{{ $errors->first() }}</div>@endif
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>
        <button type="submit" class="btn">Send reset link</button>
        <p class="small muted" style="margin:0">Students who log in with a username: ask your parent or teacher to reset your password from their Brainsnacc page.</p>
        <p class="small muted" style="margin:0"><a href="{{ route('login') }}">← Back to log in</a></p>
    </form>
</div>
</body>
</html>

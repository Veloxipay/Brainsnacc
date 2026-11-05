<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a new password · {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login">
    <form method="POST" action="{{ route('password.update') }}" class="card">
        @csrf
        <img class="auth-logo" src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" width="260" height="66">
        <h1 style="font-size:24px">Choose a new password</h1>
        @if ($errors->any())<div class="errors" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
        </div>
        <div class="field">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" required minlength="10" autocomplete="new-password">
            <span class="small muted">At least 10 characters, with letters and numbers.</span>
        </div>
        <div class="field">
            <label for="password_confirmation">Type it again</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
        </div>
        <button type="submit" class="btn">Save new password</button>
    </form>
</div>
</body>
</html>

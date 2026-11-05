<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign up · {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="login">
    <form method="POST" action="{{ route('register') }}" class="card" style="max-width:480px">
        @csrf
        <img class="auth-logo" src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" width="260" height="66">
        <h1 style="font-size:24px">Create your account</h1>
        <p class="muted" style="margin:0">Maths from Year 7, plus Chemistry and Biology from Year 10 to University Year 1.</p>

        @if ($errors->any())
            <div class="errors" role="alert">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <fieldset class="role-pick">
            <legend>I am a…</legend>
            @foreach (['parent' => ['Parent', 'Add your children and follow their progress'], 'teacher' => ['Teacher', 'Add your students and see the whole class'], 'student' => ['Student', 'Study on my own']] as $value => [$label, $hint])
                <label>
                    <input type="radio" name="role" value="{{ $value }}" @checked(old('role', 'parent') === $value) required>
                    <span><strong>{{ $label }}</strong><span class="small muted">{{ $hint }}</span></span>
                </label>
            @endforeach
        </fieldset>

        <div class="field">
            <label for="name">Your name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
        </div>
        <div class="field">
            <label for="email">Email (you’ll log in with this)</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </div>
        <div class="field" data-level>
            <label for="level_id">Year level</label>
            <select id="level_id" name="level_id">
                <option value="">Choose…</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required minlength="10" autocomplete="new-password">
            <span class="small muted">At least 10 characters, with letters and numbers.</span>
        </div>
        <div class="field">
            <label for="password_confirmation">Type the password again</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
        </div>
        <div style="position:absolute;left:-9999px" aria-hidden="true">
            <label>Website <input name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <button type="submit" class="btn">Create my account</button>
        <p class="small muted" style="margin:0">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </form>
</div>
<script>
    (function () {
        var level = document.querySelector('[data-level]');
        function sync() {
            var role = (document.querySelector('input[name=role]:checked') || {}).value;
            level.hidden = role !== 'student';
        }
        document.querySelectorAll('input[name=role]').forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    })();
</script>
</body>
</html>

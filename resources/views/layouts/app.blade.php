<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Brainsnacc') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="shell">
    @php($user = auth()->user())
    <nav class="nav" aria-label="Main">
        <div class="brand">{{ config('app.name') }}</div>

        @if ($user->isStudent() || $user->isAdmin())
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            @php($navLevels = \App\Models\Level::orderBy('sort')->with(['units' => fn ($q) => $q->with('subject')])->get())
            @php($navLevelId = request()->routeIs('course') ? (int) (request()->query('level') ?: ($user->level_id ?: $navLevels->first()?->id)) : null)
            @php($navSubjectSlug = request()->routeIs('course') ? request()->route('subject')?->slug : null)
            <a href="{{ route('past-papers') }}" class="{{ request()->routeIs('past-papers') ? 'active' : '' }}">VCE exams</a>
            <div class="nav-section">Courses</div>
            @foreach ($navLevels as $navLevel)
                @php($navSubjects = $navLevel->units->pluck('subject')->filter()->unique('id')->sortBy('name'))
                @continue($navSubjects->isEmpty())
                <div class="nav-level {{ $navLevel->id == $navLevelId ? 'open' : '' }}">
                    {{ $navLevel->name }} @if ($user->level_id == $navLevel->id)<span class="nav-mine">Your year</span>@endif
                </div>
                @foreach ($navSubjects as $navSubject)
                    <a class="nav-sub {{ $navLevel->id == $navLevelId && $navSubjectSlug === $navSubject->slug ? 'active' : '' }}"
                       href="{{ route('course', [$navSubject, 'level' => $navLevel->id]) }}">
                        <span class="nav-dot" style="background:{{ $navSubject->color }}"></span>{{ $navSubject->name }}
                    </a>
                @endforeach
            @endforeach
        @endif

        @if ($user->isParent() || $user->isAdmin())
            <a href="{{ route('family') }}" class="{{ request()->routeIs('family') ? 'active' : '' }}">{{ $user->isAdmin() ? 'Parent view' : 'My children' }}</a>
        @endif

        @if ($user->isAdmin())
            <a href="{{ route('admin.structure') }}" class="{{ request()->routeIs('admin.structure') ? 'active' : '' }}">Course structure</a>
            <a href="{{ route('admin.lessons.index') }}" class="{{ request()->routeIs('admin.lessons.*') ? 'active' : '' }}">Lessons</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Accounts</a>
        @endif

        <div class="who">Signed in as {{ $user->name }}@if ($user->level) · {{ $user->level->short_name }}@endif</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Log out</button>
        </form>
    </nav>

    <main class="main">
        @if (session('status'))
            <div class="flash" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="errors" role="alert">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>

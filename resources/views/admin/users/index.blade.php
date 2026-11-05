@extends('layouts.app')
@section('title', 'Accounts')

@section('content')
    <header class="row between">
        <div>
            <h1 style="margin:0">Accounts</h1>
            <p class="muted small" style="margin:0">Everyone who can log in to Brainsnacc, including people who signed up themselves.</p>
        </div>
        <a class="btn" href="{{ route('admin.users.create') }}">+ New account</a>
    </header>

    @php($tabs = ['all' => 'All', 'student' => 'Students', 'parent' => 'Parents', 'teacher' => 'Teachers', 'admin' => 'Admins'])
    <div class="acct-tools">
        <nav class="segmented" aria-label="Filter by role" data-tabs>
            @foreach ($tabs as $key => $label)
                <button type="button" data-tab="{{ $key }}" class="{{ $key === 'all' ? 'on' : '' }}">{{ $label }} <span class="small">{{ $key === 'all' ? $users->count() : ($counts[$key] ?? 0) }}</span></button>
            @endforeach
        </nav>
        <input type="search" placeholder="Search name, username or email…" data-search aria-label="Search accounts">
    </div>

    <section class="card" style="padding:0;overflow:hidden">
        <div class="table-scroll">
            <table class="progress-table">
                <thead>
                    <tr><th>Name</th><th>Logs in with</th><th>Role</th><th>Year</th><th>Linked to</th><th>Lessons done</th><th>Last active</th><th>Joined</th></tr>
                </thead>
                <tbody>
                @foreach ($users as $u)
                    @php($links = $u->isStudent() ? $u->supervisors : $u->students)
                    <tr data-role="{{ $u->role }}" data-text="{{ strtolower($u->name.' '.$u->username.' '.$u->email) }}">
                        <td>
                            @if ($u->isStudent())
                                <a href="{{ route('family.students.show', $u) }}" style="font-weight:600">{{ $u->name }}</a>
                            @else
                                <strong>{{ $u->name }}</strong>
                            @endif
                        </td>
                        <td class="small">
                            @if ($u->username)<div><strong>{{ $u->username }}</strong></div>@endif
                            <div class="muted">{{ $u->email ?: '—' }}</div>
                        </td>
                        <td><span class="role-pill role-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
                        <td class="small">{{ $u->level?->short_name ?? '—' }}</td>
                        <td class="small">
                            @forelse ($links as $l)
                                <div>{{ $l->name }} <span class="muted">({{ $l->role }})</span></div>
                            @empty
                                <span class="muted">—</span>
                            @endforelse
                        </td>
                        <td class="small">{{ $u->isStudent() ? $u->completed_lessons_count : '—' }}</td>
                        <td class="small">{{ $u->attempts_max_created_at ? \Carbon\Carbon::parse($u->attempts_max_created_at)->diffForHumans() : ($u->isStudent() ? 'Not started' : '—') }}</td>
                        <td class="small">{{ $u->created_at?->timezone('Australia/Melbourne')->format('j M Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="muted small" data-empty hidden style="padding:16px;margin:0">No accounts match.</p>
    </section>
    <p class="muted small" style="margin:0">Click a student’s name to see their full progress, change their year or reset their password.</p>

    <script>
    (function () {
        var role = 'all', q = '';
        var rows = document.querySelectorAll('tr[data-role]'), empty = document.querySelector('[data-empty]');
        function apply() {
            var shown = 0;
            rows.forEach(function (r) {
                var ok = (role === 'all' || r.dataset.role === role) && (!q || r.dataset.text.indexOf(q) !== -1);
                r.hidden = !ok; if (ok) shown++;
            });
            empty.hidden = shown > 0;
        }
        document.querySelectorAll('[data-tab]').forEach(function (b) {
            b.addEventListener('click', function () {
                role = b.dataset.tab;
                document.querySelectorAll('[data-tab]').forEach(function (x) { x.classList.toggle('on', x === b); });
                apply();
            });
        });
        document.querySelector('[data-search]').addEventListener('input', function (e) { q = e.target.value.trim().toLowerCase(); apply(); });
    })();
    </script>
@endsection

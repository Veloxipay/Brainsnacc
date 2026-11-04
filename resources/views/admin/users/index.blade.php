@extends('layouts.app')
@section('title', 'Accounts')

@section('content')
    <header class="row between">
        <h1>Accounts</h1>
        <a class="btn" href="{{ route('admin.users.create') }}">+ New account</a>
    </header>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Level</th><th>Parent</th></tr></thead>
                <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ ucfirst($u->role) }}</td>
                        <td>{{ $u->level?->short_name ?? '—' }}</td>
                        <td>{{ $u->parentUser?->name ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection

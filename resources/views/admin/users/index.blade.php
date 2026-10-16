
@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'Users')
@section('subheading', 'Every account on the platform')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid filters">
        <div>
            <label class="label" for="search">Search</label>
            <input class="field" id="search" name="search" value="{{ request('search') }}" placeholder="Name, email or code">
        </div>
        <div>
            <label class="label" for="status">Status</label>
            <select class="field" id="status" name="status">
                <option value="">Any</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
            </select>
        </div>
        <div>
            <label class="label" for="role">Role</label>
            <select class="field" id="role" name="role">
                <option value="">Any</option>
                <option value="user" @selected(request('role') === 'user')>User</option>
                <option value="admin" @selected(request('role') === 'admin')>Administrator</option>
            </select>
        </div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table-app">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Credits</th>
                    <th>Audits</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="font-semibold">{{ $user->name }}</a>
                            <div class="faint text-xs">{{ $user->email }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $user->isAdmin() ? 'badge-info' : 'badge-muted' }}">{{ ucfirst($user->role) }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $user->isSuspended() ? 'badge-fail' : 'badge-pass' }}">{{ ucfirst($user->status) }}</span>
                        </td>
                        <td class="num">{{ number_format((float) $user->credits_balance, 0) }}</td>
                        <td class="num muted">{{ $user->audits_count }}</td>
                        <td class="text-sm muted">{{ $user->created_at->format('j M Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-quiet btn-sm">Open</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>

@endsection

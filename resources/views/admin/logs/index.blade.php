
@extends('layouts.admin')

@section('title', 'Audit log')
@section('heading', 'Audit log')
@section('subheading', 'A record of privileged actions')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid filters">
        <div>
            <label class="label" for="search">Search</label>
            <input class="field" id="search" name="search" value="{{ request('search') }}" placeholder="Action or email">
        </div>
        <div></div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table-app">
            <thead><tr><th>Action</th><th>User</th><th>IP</th><th>Details</th><th>When</th></tr></thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="text-sm font-semibold">{{ $log->action }}</td>
                        <td class="text-sm muted">{{ $log->user?->email ?? 'system' }}</td>
                        <td class="mono text-xs muted">{{ $log->ip ?? 'unknown' }}</td>
                        <td class="text-xs muted truncate" style="max-width:260px;">{{ $log->meta ? json_encode($log->meta) : '' }}</td>
                        <td class="text-sm muted">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>

@endsection

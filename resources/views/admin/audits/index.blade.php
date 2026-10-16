
@extends('layouts.admin')

@section('title', 'Audits')
@section('heading', 'Audits')
@section('subheading', 'Every audit run on the platform')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid filters">
        <div>
            <label class="label" for="search">Search</label>
            <input class="field" id="search" name="search" value="{{ request('search') }}" placeholder="Domain or URL">
        </div>
        <div>
            <label class="label" for="status">Status</label>
            <select class="field" id="status" name="status">
                <option value="">Any</option>
                @foreach (['queued', 'running', 'completed', 'failed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="mode">Mode</label>
            <select class="field" id="mode" name="mode">
                <option value="">Any</option>
                @foreach (['normal', 'standard', 'premium'] as $mode)
                    <option value="{{ $mode }}" @selected(request('mode') === $mode)>{{ ucfirst($mode) }}</option>
                @endforeach
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
                <tr><th>Site</th><th>User</th><th>Mode</th><th>Readiness</th><th>Status</th><th>Duration</th><th>Date</th></tr>
            </thead>
            <tbody>
                @foreach ($audits as $audit)
                    <tr>
                        <td><a href="{{ route('admin.audits.show', $audit) }}" class="font-semibold">{{ $audit->domain }}</a></td>
                        <td class="text-sm muted">{{ $audit->user?->email }}</td>
                        <td class="text-sm muted">{{ ucfirst($audit->mode) }}</td>
                        <td class="num">{{ $audit->score !== null ? $audit->score.'/100' : '--' }}</td>
                        <td>
                            <span class="badge {{ $audit->status === 'completed' ? 'badge-pass' : ($audit->status === 'failed' ? 'badge-fail' : 'badge-info') }}">
                                {{ ucfirst($audit->status) }}
                            </span>
                        </td>
                        <td class="num muted">{{ $audit->duration_ms ? round($audit->duration_ms / 1000).'s' : '--' }}</td>
                        <td class="text-sm muted">{{ $audit->created_at->format('j M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $audits->links() }}</div>
</div>

@endsection


@extends('layouts.admin')

@section('title', 'Audit '.$audit->domain)
@section('heading', $audit->domain ?? 'Audit')
@section('subheading', $audit->url)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Summary</h2>
            <a href="{{ route('audits.report', $audit) }}" class="btn btn-ghost btn-sm">PDF report</a>
        </div>

        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between"><dt class="muted">User</dt><dd>{{ $audit->user?->email }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Mode</dt><dd>{{ ucfirst($audit->mode) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Status</dt><dd>{{ ucfirst($audit->status) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Public-site readiness index</dt><dd class="mono">{{ $audit->score ?? '--' }}/100</dd></div>
            <p class="hint">Internal heuristic from sampled public pages; not an approval prediction.</p>
            <div class="flex justify-between"><dt class="muted">Score</dt><dd class="mono">{{ $audit->score ?? '--' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Pages crawled</dt><dd class="mono">{{ $audit->pages_crawled }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Findings</dt><dd class="mono">{{ $audit->findings_count }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Model</dt><dd class="mono text-xs">{{ $audit->model ?? 'rules only' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Tokens</dt><dd class="mono">{{ $audit->tokens_used }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Duration</dt><dd class="mono">{{ $audit->duration_ms ? round($audit->duration_ms / 1000).' seconds' : '--' }}</dd></div>
        </dl>

        @if ($audit->error_message)
            <div class="flash flash-error mt-4">{{ $audit->error_message }}</div>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Readiness by area</h2>
        <div class="space-y-3">
            @foreach ($categories as $category)
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span>{{ $category['label'] }}</span>
                        <span class="mono muted">{{ $category['score'] === null ? 'Not assessed' : (int) round($category['score']) }}</span>
                    </div>
                    @if ($category['score'] !== null)
                        <div class="meter"><span style="width:{{ max(2, min(100, (int) round($category['score']))) }}%;"></span></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="panel mt-4">
    <h2 class="text-lg mb-3">Findings</h2>
    <div class="table-wrap">
        <table class="table-app">
            <thead><tr><th>Severity</th><th>Category</th><th>Finding</th><th>Weight</th></tr></thead>
            <tbody>
                @foreach ($audit->findings as $finding)
                    <tr>
                        <td>
                            <span class="badge {{ $finding->severity === 'critical' ? 'badge-fail' : ($finding->severity === 'warning' ? 'badge-warn' : ($finding->severity === 'pass' ? 'badge-pass' : 'badge-muted')) }}">
                                {{ $finding->severityLabel() }}
                            </span>
                        </td>
                        <td class="text-sm muted">{{ $finding->category }}</td>
                        <td>
                            <strong>{{ $finding->title }}</strong>
                            @if ($finding->detail)
                                <div class="faint text-xs">{{ Str::limit($finding->detail, 140) }}</div>
                            @endif
                        </td>
                        <td class="num muted">{{ $finding->weight }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection


@extends('layouts.app')

@section('title', 'Audits')
@section('heading', 'Audits')
@section('subheading', 'Every site you have checked, newest first')

@section('actions')
    <a href="{{ route('audits.compare') }}" class="btn btn-ghost btn-sm">Compare runs</a>
@endsection

@section('content')

<div class="panel">
    @if ($audits->isEmpty())
        <div class="text-center py-10">
            <span class="icon-box" style="margin:0 auto 1rem;">
                @include('partials.icons', ['name' => 'shield', 'class' => 'w-5 h-5'])
            </span>
            <h2 class="text-lg mb-2">No audits yet</h2>
            <p class="muted text-sm mb-5">Run your first audit and the full report lands here.</p>
            <a href="{{ route('audits.create') }}" class="btn btn-primary">Start an audit</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>Mode</th>
                        <th>Probability</th>
                        <th>Verdict</th>
                        <th>Pages</th>
                        <th>Finished</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($audits as $audit)
                        <tr>
                            <td>
                                <a href="{{ route('audits.show', $audit) }}" class="font-semibold">{{ $audit->domain }}</a>
                                <div class="faint text-xs truncate" style="max-width:240px;">{{ $audit->url }}</div>
                            </td>
                            <td class="text-sm muted">{{ ucfirst($audit->mode) }}</td>
                            <td class="num">
                                @if ($audit->status === 'completed')
                                    <strong>{{ $audit->probability }}%</strong>
                                @else
                                    <span class="faint">--</span>
                                @endif
                            </td>
                            <td>
                                @if ($audit->status === 'completed')
                                    <span class="badge {{ $audit->probability >= 78 ? 'badge-pass' : ($audit->probability >= 55 ? 'badge-warn' : 'badge-fail') }}">{{ $audit->verdictLabel() }}</span>
                                @elseif ($audit->status === 'failed')
                                    <span class="badge badge-fail">Failed</span>
                                @else
                                    <span class="badge badge-info">Running</span>
                                @endif
                            </td>
                            <td class="num muted">{{ $audit->pages_crawled ?: 0 }}</td>
                            <td class="text-sm muted">{{ $audit->completed_at?->diffForHumans() ?? 'in progress' }}</td>
                            <td class="text-right">
                                <a href="{{ route('audits.show', $audit) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $audits->links() }}
        </div>
    @endif
</div>

@endsection

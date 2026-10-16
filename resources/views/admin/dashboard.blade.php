
@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Control panel')
@section('subheading', 'Revenue, accounts and the state of the platform')

@section('content')

<div class="grid grid-4" style="gap:1rem;">
    <div class="stat">
        <div class="k">Revenue</div>
        <div class="v">${{ number_format($revenue, 2) }}</div>
        <div class="s">${{ number_format($revenueThisMonth, 2) }} this month</div>
    </div>
    <div class="stat">
        <div class="k">Accounts</div>
        <div class="v">{{ number_format($users) }}</div>
        <div class="s">{{ $newUsers }} joined this month</div>
    </div>
    <div class="stat">
        <div class="k">Subscriptions</div>
        <div class="v">{{ number_format($activeSubscriptions) }}</div>
        <div class="s">active or on trial</div>
    </div>
    <div class="stat">
        <div class="k">Audits</div>
        <div class="v">{{ number_format($audits) }}</div>
        <div class="s">{{ $failedAudits }} failed</div>
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Revenue, last 30 days</h2>
            <span class="chip">${{ number_format(array_sum(array_column($chart, 'amount')), 2) }}</span>
        </div>

        @php
            $max = max(1, max(array_map(fn ($point) => $point['amount'], $chart)));
        @endphp

        <div style="display:flex;align-items:flex-end;gap:3px;height:150px;">
            @foreach ($chart as $point)
                <div style="flex:1;background:{{ $point['amount'] > 0 ? 'var(--color-brand)' : 'var(--color-line)' }};height:{{ $point['amount'] > 0 ? max(3, round($point['amount'] / $max * 150)) : 3 }}px;border-radius:1px;"
                     title="{{ $point['date'] }}: ${{ number_format($point['amount'], 2) }}"></div>
            @endforeach
        </div>

        <div class="flex justify-between mt-2">
            <span class="faint text-xs mono">{{ $chart[0]['date'] ?? '' }}</span>
            <span class="faint text-xs mono">{{ $chart[count($chart) - 1]['date'] ?? '' }}</span>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Revenue by gateway</h2>
        </div>

        @if ($byGateway->isEmpty())
            <p class="muted text-sm">No payments have been recorded yet.</p>
        @else
            <div class="space-y-3">
                @foreach ($byGateway as $gateway => $amount)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span>{{ ucfirst($gateway) }}</span>
                            <span class="mono muted">${{ number_format($amount, 2) }}</span>
                        </div>
                        <div class="meter">
                            <span style="width:{{ max(2, round($amount / max(1, $revenue) * 100)) }}%;"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="rule my-4"></div>

        <div class="grid grid-2" style="gap:.75rem;">
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Plans live</div>
                <div class="v" style="font-size:1.3rem;">{{ $plans }}</div>
            </div>
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Gateways ready</div>
                <div class="v" style="font-size:1.3rem;">{{ $readyGateways }}/{{ $gateways }}</div>
            </div>
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Articles</div>
                <div class="v" style="font-size:1.3rem;">{{ number_format($documents) }}</div>
            </div>
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Suspended</div>
                <div class="v" style="font-size:1.3rem;">{{ $suspendedUsers }}</div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Strongest recent audits</h2>
            <a href="{{ route('admin.audits.index') }}" class="btn btn-quiet btn-sm">All audits</a>
        </div>

        @if ($topAudits->isEmpty())
            <p class="muted text-sm">Nothing yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>Site</th><th>Readiness</th><th>Mode</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($topAudits as $audit)
                            <tr>
                                <td><a href="{{ route('admin.audits.show', $audit) }}" class="font-semibold">{{ $audit->domain }}</a></td>
                                <td class="num">{{ $audit->score }}/100</td>
                                <td class="text-sm muted">{{ ucfirst($audit->mode) }}</td>
                                <td class="text-sm muted">{{ $audit->completed_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Recent payments</h2>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-quiet btn-sm">Invoices</a>
        </div>

        @if ($recentPayments->isEmpty())
            <p class="muted text-sm">Nothing yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>Gateway</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($recentPayments as $payment)
                            <tr>
                                <td class="text-sm">{{ ucfirst($payment->gateway) }}</td>
                                <td class="num">${{ number_format((float) $payment->amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $payment->status === 'paid' ? 'badge-pass' : 'badge-fail' }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                                <td class="text-sm muted">{{ $payment->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection

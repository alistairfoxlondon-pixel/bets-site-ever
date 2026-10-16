
@extends('layouts.admin')

@section('title', 'Affiliate')
@section('heading', 'Affiliate programme')
@section('subheading', 'Commissions, top earners and withdrawal requests')

@section('content')

<div class="grid grid-3" style="gap:1rem;">
    <div class="stat">
        <div class="k">Commission rate</div>
        <div class="v">{{ (int) $rate }}%</div>
        <div class="s">of the first payment</div>
    </div>
    <div class="stat">
        <div class="k">Minimum payout</div>
        <div class="v">${{ number_format($minimum, 0) }}</div>
        <div class="s">balance needed to withdraw</div>
    </div>
    <div class="stat">
        <div class="k">Owed</div>
        <div class="v">${{ number_format($totalOwed, 2) }}</div>
        <div class="s">pending and approved</div>
    </div>
</div>

<div class="panel mt-4">
    <h2 class="text-lg mb-3">Programme settings</h2>
    <form method="POST" action="{{ route('admin.affiliate.settings') }}" class="form-grid">
        @csrf
        <div>
            <label class="label" for="commission_rate">Commission rate (percent)</label>
            <input class="field" id="commission_rate" name="commission_rate" type="number" step="0.1" min="0" max="100" value="{{ $rate }}" required>
        </div>
        <div>
            <label class="label" for="payout_minimum">Minimum withdrawal (USD)</label>
            <input class="field" id="payout_minimum" name="payout_minimum" type="number" step="1" min="0" value="{{ $minimum }}" required>
        </div>
        <div>
            <label class="label" for="cookie_days">Attribution window (days)</label>
            <input class="field" id="cookie_days" name="cookie_days" type="number" min="1" max="365" value="30" required>
        </div>
        <div class="flex items-end">
            <button class="btn btn-primary" type="submit">Save the settings</button>
        </div>
    </form>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Withdrawal requests</h2>
        @if ($payouts->isEmpty())
            <p class="muted text-sm">Nothing requested yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($payouts as $payout)
                            <tr>
                                <td class="text-sm">{{ $payout->user?->email }}</td>
                                <td class="num">${{ number_format((float) $payout->amount, 2) }}</td>
                                <td class="text-sm muted">{{ ucfirst($payout->method) }}</td>
                                <td>
                                    <span class="badge {{ $payout->status === 'paid' ? 'badge-pass' : ($payout->status === 'rejected' ? 'badge-fail' : 'badge-warn') }}">
                                        {{ ucfirst($payout->status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.affiliate.payout', $payout) }}" class="btn btn-quiet btn-sm">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $payouts->links() }}</div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Top earners</h2>
            @if ($top->isEmpty())
                <p class="muted text-sm">Nothing yet.</p>
            @else
                <ul class="space-y-2" style="font-size:.92rem;">
                    @foreach ($top as $user)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="truncate">{{ $user->name }}</a>
                            <span class="mono faint">{{ $user->earnings_count }} sales</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Recent commissions</h2>
            @if ($earnings->isEmpty())
                <p class="muted text-sm">Nothing yet.</p>
            @else
                <ul class="space-y-2" style="font-size:.9rem;">
                    @foreach ($earnings as $earning)
                        <li class="flex items-center justify-between gap-3">
                            <span class="truncate muted">{{ $earning->description }}</span>
                            <span class="mono shrink-0">${{ number_format((float) $earning->amount, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

@endsection

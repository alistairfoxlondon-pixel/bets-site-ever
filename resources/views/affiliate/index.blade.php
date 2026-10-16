
@extends('layouts.app')

@section('title', 'Referrals')
@section('heading', 'Referral programme')
@section('subheading', 'Share your link, track every signup, withdraw what you earn')

@section('content')

<div class="grid grid-4" style="gap:1rem;">
    <div class="stat">
        <div class="k">Clicks</div>
        <div class="v">{{ number_format($stats['clicks']) }}</div>
        <div class="s">last 30 days and before</div>
    </div>
    <div class="stat">
        <div class="k">Signups</div>
        <div class="v">{{ number_format($stats['referrals']) }}</div>
        <div class="s">{{ $stats['conversions'] }} converted</div>
    </div>
    <div class="stat">
        <div class="k">Earned</div>
        <div class="v">${{ number_format($stats['earnings_total'], 2) }}</div>
        <div class="s">${{ number_format($stats['earnings_approved'], 2) }} cleared</div>
    </div>
    <div class="stat">
        <div class="k">Balance</div>
        <div class="v">${{ number_format($stats['balance'], 2) }}</div>
        <div class="s">ready to withdraw</div>
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Your link</h2>
        <div class="flex gap-2">
            <input class="field mono" id="ref-link" value="{{ $link }}" readonly style="font-size:.9rem;">
            <button class="btn btn-primary btn-sm shrink-0" type="button" data-copy="#ref-link">Copy</button>
        </div>
        <p class="hint mt-2">
            Every signup through this link is attached to your account for 30 days. The commission
            is {{ (int) $stats['rate'] }}% of their first payment.
        </p>

        <div class="rule my-4"></div>

        <h3 class="text-lg mb-3">Request a withdrawal</h3>
        <form method="POST" action="{{ route('affiliate.payout') }}" class="space-y-3">
            @csrf

            <div>
                <label class="label" for="amount">Amount in US dollars</label>
                <input class="field" id="amount" name="amount" type="number" step="0.01" min="1"
                       max="{{ $stats['balance'] }}" value="{{ old('amount', number_format($stats['balance'], 2)) }}" required>
                <p class="hint mt-1">Minimum withdrawal is ${{ number_format($stats['payout_minimum'], 2) }}.</p>
            </div>

            <div>
                <label class="label" for="method">Pay me through</label>
                <select class="field" id="method" name="method" required>
                    <option value="paypal">PayPal</option>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="bank">Bank transfer</option>
                </select>
            </div>

            <div>
                <label class="label" for="paypal_email">PayPal email</label>
                <input class="field" id="paypal_email" name="paypal_email" type="email" placeholder="you@example.com">
            </div>

            <div>
                <label class="label" for="mobile">Mobile number for bKash or Nagad</label>
                <input class="field" id="mobile" name="mobile" placeholder="01XXXXXXXXX">
            </div>

            <div class="form-grid">
                <div>
                    <label class="label" for="bank_name">Bank name</label>
                    <input class="field" id="bank_name" name="bank_name">
                </div>
                <div>
                    <label class="label" for="account_number">Account number</label>
                    <input class="field" id="account_number" name="account_number">
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Request the withdrawal</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Earnings</h2>
            @if ($earnings->isEmpty())
                <p class="muted text-sm">No commissions yet. Share your link to get started.</p>
            @else
                <div class="table-wrap">
                    <table class="table-app">
                        <thead>
                            <tr><th>Source</th><th>Rate</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($earnings as $earning)
                                <tr>
                                    <td class="text-sm">{{ $earning->description }}</td>
                                    <td class="num muted">{{ (int) $earning->rate }}%</td>
                                    <td class="num">${{ number_format((float) $earning->amount, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $earning->status === 'paid' ? 'badge-pass' : ($earning->status === 'approved' ? 'badge-info' : 'badge-warn') }}">
                                            {{ ucfirst($earning->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $earnings->links() }}</div>
            @endif
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Withdrawals</h2>
            @if ($payouts->isEmpty())
                <p class="muted text-sm">No withdrawals requested yet.</p>
            @else
                <div class="table-wrap">
                    <table class="table-app">
                        <thead>
                            <tr><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($payouts as $payout)
                                <tr>
                                    <td class="num">${{ number_format((float) $payout->amount, 2) }}</td>
                                    <td class="text-sm muted">{{ ucfirst($payout->method) }}</td>
                                    <td>
                                        <span class="badge {{ $payout->status === 'paid' ? 'badge-pass' : ($payout->status === 'rejected' ? 'badge-fail' : 'badge-warn') }}">
                                            {{ ucfirst($payout->status) }}
                                        </span>
                                    </td>
                                    <td class="text-sm muted">{{ $payout->created_at->format('j M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $payouts->links() }}</div>
            @endif
        </div>
    </div>
</div>

@endsection

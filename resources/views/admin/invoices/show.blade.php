
@extends('layouts.admin')

@section('title', 'Invoice '.$invoice->number)
@section('heading', 'Invoice '.$invoice->number)
@section('subheading', $invoice->user?->email)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Details</h2>
            <span class="badge {{ $invoice->status === 'paid' ? 'badge-pass' : 'badge-warn' }}">{{ ucfirst($invoice->status) }}</span>
        </div>

        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between"><dt class="muted">Customer</dt><dd>{{ $invoice->user?->name }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Description</dt><dd class="text-right">{{ $invoice->description }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Amount</dt><dd class="mono">${{ number_format((float) $invoice->amount, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Taka</dt><dd class="mono">৳{{ number_format((float) $invoice->amount_bdt, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Period</dt><dd>{{ $invoice->period_start?->format('j M Y') }} to {{ $invoice->period_end?->format('j M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Paid</dt><dd>{{ $invoice->paid_at?->format('j M Y, H:i') ?? 'not paid' }}</dd></div>
        </dl>

        @if (! $invoice->isPaid())
            <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="mt-5">
                @csrf
                <button class="btn btn-primary" type="submit">Mark as paid and activate</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Payments</h2>
        @if ($invoice->payments->isEmpty())
            <p class="muted text-sm">No payment attempt recorded.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>Gateway</th><th>Reference</th><th>Status</th><th>When</th></tr></thead>
                    <tbody>
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td class="text-sm">{{ ucfirst($payment->gateway) }}</td>
                                <td class="mono text-xs">{{ $payment->gateway_transaction_id }}</td>
                                <td>
                                    <span class="badge {{ $payment->status === 'paid' ? 'badge-pass' : 'badge-fail' }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                                <td class="text-sm muted">{{ $payment->created_at->format('j M H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($invoice->subscription)
            <div class="rule my-4"></div>
            <h3 class="text-lg mb-2">Subscription</h3>
            <dl class="space-y-2" style="font-size:.92rem;">
                <div class="flex justify-between"><dt class="muted">Plan</dt><dd>{{ $invoice->subscription->plan?->name }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Status</dt><dd>{{ ucfirst($invoice->subscription->status) }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Renews</dt><dd>{{ $invoice->subscription->renews_at?->format('j M Y') ?? 'not set' }}</dd></div>
            </dl>
        @endif
    </div>
</div>

@endsection


@extends('layouts.app')

@section('title', 'Invoice '.$invoice->number)
@section('heading', 'Invoice '.$invoice->number)
@section('subheading', $invoice->description)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Details</h2>
            <span class="badge {{ $invoice->status === 'paid' ? 'badge-pass' : 'badge-warn' }}">{{ ucfirst($invoice->status) }}</span>
        </div>

        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between">
                <dt class="muted">Number</dt>
                <dd class="mono">{{ $invoice->number }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Amount</dt>
                <dd class="mono">
                    {{ $invoice->currency === 'BDT' ? '৳'.number_format((float) $invoice->amount_bdt, 2) : '$'.number_format((float) $invoice->amount, 2) }}
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Period</dt>
                <dd>{{ $invoice->period_start?->format('j M Y') }} to {{ $invoice->period_end?->format('j M Y') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Created</dt>
                <dd>{{ $invoice->created_at->format('j M Y, H:i') }}</dd>
            </div>
            @if ($invoice->paid_at)
                <div class="flex justify-between">
                    <dt class="muted">Paid</dt>
                    <dd>{{ $invoice->paid_at->format('j M Y, H:i') }}</dd>
                </div>
            @endif
        </dl>

        @if (! $invoice->isPaid() && $gateways->isNotEmpty())
            <div class="rule my-4"></div>
            <h3 class="text-lg mb-3">Pay this invoice</h3>
            <form method="POST" action="{{ route('billing.process', $invoice) }}" class="space-y-3">
                @csrf
                <div class="space-y-2">
                    @foreach ($gateways as $gateway)
                        <label class="card-flat" style="padding:.7rem .9rem;cursor:pointer;display:block;">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="gateway" value="{{ $gateway->slug }}" @checked($loop->first)>
                                <img src="{{ $gateway->logoUrl() }}" alt="{{ $gateway->name }}" height="20"
                                     onerror="this.style.display='none'">
                                <span class="text-sm">{{ $gateway->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-block" type="submit">Continue to payment</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">What happens next</h2>
        <ol class="space-y-3" style="font-size:.93rem;">
            <li class="flex gap-3">
                <span class="step-number shrink-0">1</span>
                <span>Choose a payment method and complete the payment.</span>
            </li>
            <li class="flex gap-3">
                <span class="step-number shrink-0">2</span>
                <span>The gateway confirms the payment and the invoice is marked as paid automatically.</span>
            </li>
            <li class="flex gap-3">
                <span class="step-number shrink-0">3</span>
                <span>Your plan and credits are applied to the account straight away.</span>
            </li>
        </ol>

        @if (! $invoice->isPaid())
            <div class="rule my-4"></div>
            <form method="POST" action="{{ route('billing.manual', $invoice) }}">
                @csrf
                <p class="muted text-sm mb-3">Paid by bank transfer? Tell us and we will confirm it by hand.</p>
                <button class="btn btn-ghost btn-sm btn-block" type="submit">I have paid by transfer</button>
            </form>
        @endif
    </div>
</div>

@endsection

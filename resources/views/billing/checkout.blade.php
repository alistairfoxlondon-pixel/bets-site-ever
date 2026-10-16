
@extends('layouts.app')

@section('title', 'Checkout')
@section('heading', 'Checkout')
@section('subheading', 'Invoice '.$invoice->number)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Payment method</h2>

        @if ($gateways->isEmpty())
            <p class="muted mb-4">
                No payment method is switched on right now. Contact support and we will take the
                payment by hand.
            </p>
        @else
            <form method="POST" action="{{ route('billing.process', $invoice) }}" class="space-y-3">
                @csrf
                <div class="space-y-2">
                    @foreach ($gateways as $gateway)
                        <label class="card-flat" style="padding:.85rem 1rem;cursor:pointer;display:block;">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="gateway" value="{{ $gateway->slug }}" @checked($loop->first)>
                                <img src="{{ $gateway->logoUrl() }}" alt="{{ $gateway->name }}" height="22"
                                     onerror="this.style.display='none'">
                                <span>
                                    <span class="block font-semibold text-sm">{{ $gateway->name }}</span>
                                    <span class="faint text-xs">{{ $gateway->description }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Pay now</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Order summary</h2>
        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between">
                <dt class="muted">Description</dt>
                <dd class="text-right">{{ $invoice->description }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Period</dt>
                <dd>{{ $invoice->period_start?->format('j M Y') }} to {{ $invoice->period_end?->format('j M Y') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Currency</dt>
                <dd class="mono">{{ $invoice->currency }}</dd>
            </div>
            <div class="rule"></div>
            <div class="flex justify-between items-baseline">
                <dt><strong>Total</strong></dt>
                <dd class="mono" style="font-size:1.3rem;">
                    {{ $invoice->currency === 'BDT' ? '৳'.number_format((float) $invoice->amount_bdt, 2) : '$'.number_format((float) $invoice->amount, 2) }}
                </dd>
            </div>
        </dl>
    </div>
</div>

@endsection

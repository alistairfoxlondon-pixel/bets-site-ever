
@extends('layouts.app')

@section('title', 'Payment instructions')
@section('heading', 'Payment instructions')
@section('subheading', 'Invoice '.$invoice->number)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">How to pay</h2>
        <ul class="space-y-2.5" style="font-size:.95rem;">
            @foreach ($instructions as $instruction)
                <li class="flex gap-2.5">
                    @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                    <span>{{ $instruction }}</span>
                </li>
            @endforeach
        </ul>

        <div class="rule my-4"></div>

        <p class="muted text-sm mb-3">Include the invoice number so we can match the payment.</p>
        <p class="mono" style="font-size:1.2rem;">{{ $invoice->number }}</p>
        <p class="mono" style="font-size:1.2rem;">
            {{ $invoice->currency === 'BDT' ? '৳'.number_format((float) $invoice->amount_bdt, 2) : '$'.number_format((float) $invoice->amount, 2) }}
        </p>

        <form method="POST" action="{{ route('billing.manual', $invoice) }}" class="mt-5">
            @csrf
            <button class="btn btn-primary btn-block" type="submit">I have made the payment</button>
        </form>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">After you pay</h2>
        <p class="muted mb-4">
            We check incoming payments by hand, so the account is usually live within a few
            hours. If nothing happens after a day, write to support with the invoice number.
        </p>
        <a href="{{ route('support.index') }}" class="btn btn-ghost btn-sm">Message support</a>
    </div>
</div>

@endsection


@extends('layouts.app')

@section('title', 'Billing')
@section('heading', 'Billing and plans')
@section('subheading', 'Choose a plan, top up your credits or manage what you already pay for')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Your plan</h2>
            @if ($subscription)
                <span class="badge {{ $subscription->isOnTrial() ? 'badge-warn' : 'badge-pass' }}">{{ ucfirst($subscription->status) }}</span>
            @endif
        </div>

        @if ($subscription)
            <div class="flex items-baseline justify-between mb-3">
                <div>
                    <strong class="text-lg">{{ $subscription->plan?->name ?? 'Subscription' }}</strong>
                    <p class="muted text-sm">
                        {{ ucfirst($subscription->billing_cycle) }} billing &middot;
                        {{ $subscription->currency === 'BDT' ? '৳'.number_format((float) $subscription->amount_bdt, 2) : '$'.number_format((float) $subscription->amount, 2) }}
                    </p>
                </div>
            </div>

            @if ($subscription->renews_at)
                <p class="muted text-sm mb-4">Next renewal {{ $subscription->renews_at->format('j F Y') }}.</p>
            @endif

            <div class="flex flex-wrap gap-2">
                @if ($subscription->status === 'cancelled')
                    <form method="POST" action="{{ route('billing.resume', $subscription) }}">
                        @csrf
                        <button class="btn btn-primary btn-sm" type="submit">Resume subscription</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('billing.cancel', $subscription) }}"
                          onsubmit="return confirm('Cancel the subscription at the end of the paid period?');">
                        @csrf
                        <button class="btn btn-ghost btn-sm" type="submit">Cancel subscription</button>
                    </form>
                @endif
                <a href="{{ route('billing.invoices') }}" class="btn btn-ghost btn-sm">Invoices</a>
            </div>
        @else
            <p class="muted mb-4">You are not on a paid plan. Pick one below, or keep using the free credits.</p>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Credits</h2></div>
        <p class="display" style="font-size:2.6rem;color:var(--color-brand);">{{ number_format($credits, 0) }}</p>
        <p class="muted text-sm mb-4">Available now. A Standard audit uses three credits.</p>
        <p class="text-sm muted mb-4">
            Need more? Pick a plan above, or write to us and we will add the credits to your account.
        </p>
        <a href="{{ route('contact.create', ['reason' => 'billing']) }}" class="btn btn-ghost btn-sm">Ask for more credits</a>
    </div>
</div>

<h2 class="text-xl mt-6 mb-4">Plans</h2>

<div class="grid grid-3" style="gap:1.25rem;align-items:start;">
    @foreach ($plans as $plan)
        <div class="card" style="padding:1.5rem;@if ($plan->is_featured) border-color:var(--color-brand); @endif">
            <div class="flex items-center justify-between gap-2 mb-2">
                <h3 class="text-lg">{{ $plan->name }}</h3>
                @if ($plan->is_featured)
                    <span class="badge badge-info">Popular</span>
                @endif
            </div>
            <p class="muted text-sm mb-4">{{ $plan->tagline }}</p>

            <p class="display" style="font-size:1.9rem;line-height:1;">
                ${{ number_format((float) $plan->price_monthly, 0) }}<span class="muted" style="font-size:.95rem;">/month</span>
            </p>
            <p class="faint text-xs mono mt-1">
                ৳{{ number_format((float) $plan->price_monthly_bdt, 0) }} per month &middot;
                ${{ number_format((float) $plan->price_yearly, 0) }} per year
            </p>

            <ul class="mt-4 space-y-1.5" style="font-size:.9rem;">
                @foreach (array_slice((array) $plan->features, 0, 4) as $feature)
                    <li class="flex gap-2">
                        @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 space-y-2">
                <form method="POST" action="{{ route('billing.subscribe', [$plan, 'monthly']) }}">
                    @csrf
                    <button class="btn {{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} btn-block btn-sm" type="submit">Pay monthly</button>
                </form>
                <form method="POST" action="{{ route('billing.subscribe', [$plan, 'yearly']) }}">
                    @csrf
                    <button class="btn btn-ghost btn-block btn-sm" type="submit">Pay yearly</button>
                </form>
            </div>
        </div>
    @endforeach
</div>

@if ($gateways->isNotEmpty())
    <div class="panel mt-4">
        <h2 class="text-lg mb-3">Payment methods</h2>
        <div class="logo-strip">
            @foreach ($gateways as $gateway)
                <img src="{{ $gateway->logoUrl() }}" alt="{{ $gateway->name }}" height="24"
                     onerror="this.style.display='none'">
            @endforeach
        </div>
    </div>
@endif

@endsection

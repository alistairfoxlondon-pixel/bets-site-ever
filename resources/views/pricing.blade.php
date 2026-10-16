
@extends('layouts.marketing')

@section('title', 'Pricing | '.config('app.name'))
@section('description', 'Transparent plans for the AdReady auditor, writer and indexing tools. Pay in US dollars or Bangladeshi taka.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="text-center" style="max-width:640px;margin-inline:auto;">
            <p class="eyebrow mb-3">Pricing</p>
            <h1 class="mb-4">Simple plans, priced in dollars and taka</h1>
            <p class="lede">
                Every paid plan includes the full auditor, the PDF report, the writer and the
                indexing tool. Credits are the only thing that changes.
            </p>
        </div>

        <div class="grid grid-3 mt-9" style="gap:1.25rem;align-items:start;">
            @foreach ($plans as $plan)
                <div class="card" style="padding:1.7rem;@if ($plan->is_featured) border-color:var(--color-brand); @endif">
                    @if ($plan->is_featured)
                        <span class="badge badge-info mb-3">Most popular</span>
                    @endif

                    <h3 class="mb-1">{{ $plan->name }}</h3>
                    <p class="muted text-sm mb-5">{{ $plan->tagline }}</p>

                    <div class="flex items-baseline gap-2">
                        <span class="display" style="font-size:2.4rem;line-height:1;">
                            {{ $currency === 'BDT' ? '৳' : '$' }}{{ number_format($currency === 'BDT' ? (float) $plan->price_monthly_bdt : (float) $plan->price_monthly, 0) }}
                        </span>
                        <span class="muted text-sm">per month</span>
                    </div>

                    <p class="faint text-xs mt-2 mono">
                        or {{ $currency === 'BDT' ? '৳' : '$' }}{{ number_format($currency === 'BDT' ? (float) $plan->price_yearly_bdt : (float) $plan->price_yearly, 0) }} per year
                        @if ($plan->savingsPercent($currency) > 0)
                            (save {{ $plan->savingsPercent($currency) }}%)
                        @endif
                    </p>

                    <ul class="mt-5 space-y-2" style="font-size:.93rem;">
                        @foreach ((array) $plan->features as $feature)
                            <li class="flex gap-2">
                                @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('billing.index') }}?cycle=monthly&plan={{ $plan->slug }}"
                       class="btn {{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} btn-block mt-6">
                        Choose {{ $plan->name }}
                    </a>
                </div>
            @endforeach
        </div>

        <div class="card mt-8" style="padding:1.4rem 1.6rem;">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg">Pay in taka if that is easier</h3>
                    <p class="muted text-sm">Every price is stored in US dollars and converted at the live rate. Switch the currency on any page.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('pricing', ['currency' => 'USD']) }}" class="btn btn-sm {{ $currency === 'USD' ? 'btn-primary' : 'btn-ghost' }}">USD</a>
                    <a href="{{ route('pricing', ['currency' => 'BDT']) }}" class="btn btn-sm {{ $currency === 'BDT' ? 'btn-primary' : 'btn-ghost' }}">BDT</a>
                </div>
            </div>
            <p class="faint text-xs mono mt-3">1 USD = ৳{{ number_format($rates['bdt_rate'] ?? 0, 2) }}</p>
        </div>
    </div>
</section>

<section class="band band-line band-tight">
    <div class="wrap">
        <h2 class="text-center mb-6">Pay with the method you already use</h2>
        <div class="logo-strip justify-center" style="gap:1.6rem 2rem;">
            @foreach ($gateways as $gateway)
                <img src="{{ $gateway->logoUrl() }}" alt="{{ $gateway->name }}" height="26"
                     onerror="this.style.display='none'">
            @endforeach
        </div>
        <p class="text-center muted text-sm mt-5">
            Local mobile wallets, card processors and bank transfer are all supported. The admin
            switches each one on and drops in the credentials.
        </p>
    </div>
</section>

<section class="band">
    <div class="wrap-narrow">
        <h2 class="mb-5">Questions about billing</h2>
        <div class="card" style="padding:0 1.4rem;">
            @foreach ([
                ['What counts as a credit?', 'A Normal audit uses one credit, a Standard audit three, and a Premium audit six. A written article uses two credits, or five for the high quality writer.'],
                ['What happens when I run out?', 'Audits and writing pause, but your account, your saved reports and your history stay exactly where they are. Add credits or upgrade and you carry on.'],
                ['Can I cancel any time?', 'Yes. You keep every plan feature until the end of the period you already paid for, and your saved audits stay in the account.'],
                ['Do you refund credits?', 'Unused credits are refunded if you cancel within the first seven days of a purchase. After that they are still yours to use at any time.'],
                ['How do referrals pay out?', 'You earn a share of the first payment from every account you refer. Withdraw from the affiliate page once you clear the minimum balance.'],
            ] as $index => $faq)
                <details class="qa" @if ($index === 0) open @endif>
                    <summary>{{ $faq[0] }}</summary>
                    <div class="a">{{ $faq[1] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>

@endsection

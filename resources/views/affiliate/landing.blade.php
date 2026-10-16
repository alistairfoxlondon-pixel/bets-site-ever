
@extends('layouts.marketing')

@section('title', 'Affiliate program | '.config('app.name'))
@section('description', 'Earn a share of every subscription you send to AdReady. Track every click, every signup and every payout.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">Affiliate program</p>
                <h1 class="mb-4">Earn {{ (int) $rate }}% of every subscription you send</h1>
                <p class="lede mb-6">
                    One link. Every signup and every payment is tracked. When somebody you
                    referred upgrades, the commission lands in your wallet and you can withdraw it.
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('affiliate.index') : route('register') }}" class="btn btn-primary btn-lg">
                        {{ auth()->check() ? 'Open your dashboard' : 'Join and get your link' }}
                    </a>
                    <a href="{{ route('pricing') }}" class="btn btn-ghost btn-lg">See the plans</a>
                </div>

                <div class="grid grid-3 mt-8" style="gap:1rem;">
                    <div class="stat">
                        <div class="k">Commission</div>
                        <div class="v">{{ (int) $rate }}%</div>
                        <div class="s">of the first payment</div>
                    </div>
                    <div class="stat">
                        <div class="k">Minimum payout</div>
                        <div class="v">${{ number_format($minimum, 0) }}</div>
                        <div class="s">paid to your account</div>
                    </div>
                    <div class="stat">
                        <div class="k">Cookie</div>
                        <div class="v">30d</div>
                        <div class="s">attribution window</div>
                    </div>
                </div>
            </div>

            <div class="visual-frame">
                @include('partials.visuals.referral-card')
            </div>
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <h2 class="mb-6">How it works</h2>
        <div class="grid grid-3" style="gap:1.25rem;">
            @foreach ([
                ['title' => 'Share your link', 'text' => 'Your personal link sits in the affiliate page. Share it where your audience already trusts you.'],
                ['title' => 'We track the click', 'text' => 'The referral is attached to the browser for 30 days, so the signup is credited to you.'],
                ['title' => 'Get paid on their payment', 'text' => 'When they pay for a plan, the commission is credited to your wallet and you request a withdrawal.'],
            ] as $index => $step)
                <div class="feature">
                    <span class="step-number">{{ $index + 1 }}</span>
                    <h3 class="mt-3">{{ $step['title'] }}</h3>
                    <p>{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection

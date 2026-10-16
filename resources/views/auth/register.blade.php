
@extends('layouts.auth')

@section('title', 'Create your account')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Create your account</h1>
    <p class="muted text-sm mb-6">Two free audits are waiting for you.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        @if (request('ref') || app(\App\Services\Affiliate\AffiliateService::class)->pendingReferralCode())
            <input type="hidden" name="referral_code" value="{{ request('ref') ?? app(\App\Services\Affiliate\AffiliateService::class)->pendingReferralCode() }}">
            <div class="flash flash-success">
                You were referred by a member. The referral is attached to your account.
            </div>
        @endif

        <div>
            <label class="label" for="name">Name</label>
            <input class="field" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </div>

        <div>
            <label class="label" for="password">Password</label>
            <input class="field" id="password" type="password" name="password" required autocomplete="new-password">
            <p class="hint mt-1">At least eight characters with a number.</p>
        </div>

        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input class="field" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <label class="flex items-start gap-2.5" style="font-size:.9rem;">
            <input type="checkbox" name="terms" value="1" class="mt-1" required>
            <span class="muted">I agree to the <a href="{{ route('page.show', ['slug' => 'terms']) }}" class="underline">terms of service</a> and the <a href="{{ route('page.show', ['slug' => 'privacy']) }}" class="underline">privacy policy</a>.</span>
        </label>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Create account</button>
    </form>

    @if (config('services.google.client_id'))
        <div class="flex items-center gap-3 my-5">
            <span class="rule flex-1"></span>
            <span class="faint text-xs uppercase tracking-widest">or</span>
            <span class="rule flex-1"></span>
        </div>

        <a href="{{ route('google.redirect') }}" class="btn btn-ghost btn-block">
            <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.4a5.5 5.5 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.6-5.2 3.6-8.8z"/>
                <path fill="#34A853" d="M12 24c3.2 0 6-1.1 8-2.9l-3.9-3a7.2 7.2 0 0 1-10.7-3.8H1.3v3.1A12 12 0 0 0 12 24z"/>
                <path fill="#FBBC05" d="M5.4 14.3a7.2 7.2 0 0 1 0-4.6V6.6H1.3a12 12 0 0 0 0 10.8z"/>
                <path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A11.6 11.6 0 0 0 12 0 12 12 0 0 0 1.3 6.6l4.1 3.1A7.2 7.2 0 0 1 12 4.8z"/>
            </svg>
            Continue with Google
        </a>
    @endif

    <p class="text-center muted text-sm mt-6">
        Already registered? <a href="{{ route('login') }}" class="underline">Sign in</a>
    </p>
</div>

@endsection

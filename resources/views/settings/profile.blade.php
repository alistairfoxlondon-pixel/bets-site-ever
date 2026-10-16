
@extends('layouts.app')

@section('title', 'Profile')
@section('heading', 'Settings')
@section('subheading', 'Your profile, security and notification preferences')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Profile</h2></div>

        <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="name">Name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div>
                <label class="label" for="phone">Phone</label>
                <input class="field" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>

            <div>
                <label class="label" for="country">Country</label>
                <input class="field" id="country" name="country" value="{{ old('country', $user->country) }}" placeholder="Bangladesh">
            </div>

            <div>
                <label class="label" for="currency">Preferred currency</label>
                <select class="field" id="currency" name="currency">
                    <option value="USD" @selected(old('currency', $user->currency ?: 'USD') === 'USD')>US dollars</option>
                    <option value="BDT" @selected(old('currency', $user->currency) === 'BDT')>Bangladeshi taka</option>
                </select>
            </div>

            <div>
                <label class="label" for="timezone">Timezone</label>
                <input class="field" id="timezone" name="timezone" value="{{ old('timezone', $user->timezone ?: 'Asia/Dhaka') }}">
            </div>

            <label class="flex items-center gap-2.5" style="font-size:.93rem;">
                <input type="checkbox" name="email_notifications" value="1" @checked(old('email_notifications', $user->email_notifications))>
                <span>Send me an email when an audit finishes or a payment is confirmed</span>
            </label>

            <button class="btn btn-primary" type="submit">Save the profile</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Account</h2>
            <dl class="space-y-3" style="font-size:.93rem;">
                <div class="flex justify-between">
                    <dt class="muted">Email</dt>
                    <dd>
                        {{ $user->email }}
                        @if ($user->hasVerifiedEmail())
                            <span class="badge badge-pass">Verified</span>
                        @else
                            <span class="badge badge-warn">Not verified</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Referral code</dt>
                    <dd class="mono">{{ $user->referral_code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Credits</dt>
                    <dd class="mono">{{ number_format((float) $user->credits_balance, 0) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Member since</dt>
                    <dd>{{ $user->created_at->format('j F Y') }}</dd>
                </div>
            </dl>

            <div class="rule my-4"></div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('settings.security') }}" class="btn btn-ghost btn-sm">Security</a>
                <a href="{{ route('settings.two-factor') }}" class="btn btn-ghost btn-sm">Two factor</a>
                <a href="{{ route('billing.index') }}" class="btn btn-ghost btn-sm">Billing</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Close the account</h2>
            <p class="muted text-sm mb-4">
                Your audits and invoices stay on record for accounting. The account itself is
                removed and you can always create a new one.
            </p>
            <form method="POST" action="{{ route('settings.destroy') }}"
                  onsubmit="return confirm('Close your account permanently? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <div class="mb-3">
                    <label class="label" for="destroy-password">Confirm your password</label>
                    <input class="field" id="destroy-password" name="password" type="password" required>
                </div>
                <button class="btn btn-danger btn-sm" type="submit">Close the account</button>
            </form>
        </div>
    </div>
</div>

@endsection

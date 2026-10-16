
@extends('layouts.app')

@section('title', 'Two factor authentication')
@section('heading', 'Two factor authentication')
@section('subheading', 'A second step at sign in, using any authenticator app')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        @if ($user->hasTwoFactorEnabled())
            <h2 class="text-lg mb-3">Two factor is on</h2>
            <p class="muted text-sm mb-5">
                Every sign in asks for a six digit code from your authenticator app. If you lose
                the device, use one of the recovery codes below.
            </p>

            <div class="rule my-4"></div>

            <h3 class="text-lg mb-3">Recovery codes</h3>
            <p class="muted text-sm mb-3">Each code works once. Store them somewhere safe, away from your phone.</p>

            <div class="card-flat" style="padding:1rem;background:var(--color-canvas);">
                <div class="grid grid-2" style="gap:.4rem;">
                    @foreach ($user->two_factor_recovery_codes ?? [] as $code)
                        <span class="mono text-sm">{{ $code }}</span>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('settings.two-factor.recovery') }}" class="mt-4">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit">Generate a fresh set of codes</button>
            </form>

            <div class="rule my-5"></div>

            <h3 class="text-lg mb-3">Turn it off</h3>
            <form method="POST" action="{{ route('settings.two-factor.disable') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="label" for="disable-password">Confirm your password</label>
                    <input class="field" id="disable-password" name="password" type="password" required>
                </div>
                <button class="btn btn-danger btn-sm" type="submit">Turn off two factor</button>
            </form>
        @elseif (filled($user->two_factor_secret))
            <h2 class="text-lg mb-3">Finish the setup</h2>
            <p class="muted text-sm mb-5">Add this key to your authenticator app, then enter the code it shows.</p>

            <div class="card-flat" style="padding:1rem;background:var(--color-canvas);">
                <p class="label">Setup key</p>
                <p class="mono" style="word-break:break-all;font-size:.95rem;">{{ $user->two_factor_secret }}</p>
            </div>

            <form method="POST" action="{{ route('settings.two-factor.confirm') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="label" for="code">Code from the app</label>
                    <input class="field mono" id="code" name="code" inputmode="numeric" maxlength="6"
                           style="letter-spacing:.3em;text-align:center;font-size:1.2rem;" required>
                </div>
                <button class="btn btn-primary" type="submit">Confirm and turn on</button>
            </form>
        @else
            <h2 class="text-lg mb-3">Turn on two factor</h2>
            <p class="muted text-sm mb-5">
                Two factor authentication asks for a code from an authenticator app every time
                you sign in. It is the single best protection for an account that runs paid audits.
            </p>

            <ol class="space-y-3" style="font-size:.93rem;">
                <li class="flex gap-3"><span class="step-number shrink-0">1</span><span>Install an authenticator app such as 1Password, Authy or Google Authenticator.</span></li>
                <li class="flex gap-3"><span class="step-number shrink-0">2</span><span>Press the button below. A setup key appears on this page.</span></li>
                <li class="flex gap-3"><span class="step-number shrink-0">3</span><span>Add the key to the app, then type the six digit code it shows.</span></li>
            </ol>

            <form method="POST" action="{{ route('settings.two-factor.enable') }}" class="mt-5">
                @csrf
                <button class="btn btn-primary" type="submit">Start the setup</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Why bother</h2>
        <p class="muted mb-4">
            An audit account holds paid reports and a payment history. Passwords get reused and
            leaked. A second factor removes that risk almost completely.
        </p>

        <div class="rule my-4"></div>

        <h3 class="text-lg mb-3">If you lose your phone</h3>
        <p class="muted text-sm mb-4">
            Use a recovery code. We give you a set of single use codes when you turn two factor
            on, and you can regenerate them at any time from this page.
        </p>

        @if (session('recovery_codes'))
            <div class="flash flash-success">
                <strong>Save these recovery codes now.</strong>
                <div class="grid grid-2 mt-2" style="gap:.3rem;">
                    @foreach (session('recovery_codes') as $code)
                        <span class="mono text-sm">{{ $code }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

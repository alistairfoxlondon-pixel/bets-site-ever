
@extends('layouts.auth')

@section('title', 'Two factor check')

@section('content')

<div class="card" style="padding:1.8rem;">
    <span class="icon-box" style="margin:0 0 1rem;">
        @include('partials.icons', ['name' => 'lock', 'class' => 'w-5 h-5'])
    </span>

    <h1 class="text-2xl mb-1">Confirm it is you</h1>
    <p class="muted text-sm mb-6">Enter the six digit code from your authenticator app.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="code">Authentication code</label>
            <input class="field mono" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="12"
                   style="font-size:1.3rem;letter-spacing:.35em;text-align:center;" required autofocus>
            <p class="hint mt-1">A recovery code works here too.</p>
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Verify</button>
    </form>

    <p class="mt-4 text-sm">
        <a class="link" href="{{ route('two-factor.recovery') }}">Use a recovery code instead</a>
    </p>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button class="btn btn-quiet btn-block" type="submit">Sign out instead</button>
    </form>
</div>

@endsection

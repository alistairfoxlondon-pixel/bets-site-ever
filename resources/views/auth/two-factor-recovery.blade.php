@extends('layouts.auth')

@section('title', 'Use a recovery code')

@section('content')

<div class="card" style="padding:1.8rem;">
    <span class="icon-box" style="margin:0 0 1rem;">
        @include('partials.icons', ['name' => 'key', 'class' => 'w-5 h-5'])
    </span>

    <h1 class="text-2xl mb-1">Use a recovery code</h1>
    <p class="muted text-sm mb-6">Each recovery code works once. After you sign in, generate a new set from your security settings.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('two-factor.recovery') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="recovery_code">Recovery code</label>
            <input class="field mono" id="recovery_code" name="recovery_code" autocomplete="off" spellcheck="false"
                   style="font-size:1.1rem;letter-spacing:.18em;text-transform:uppercase;" required autofocus>
            @error('recovery_code')
                <p class="field-error mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
    </form>

    <p class="mt-4 text-sm">
        <a class="link" href="{{ route('two-factor.challenge') }}">Back to the authenticator code</a>
    </p>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button class="btn btn-quiet btn-block" type="submit">Sign out instead</button>
    </form>
</div>

@endsection

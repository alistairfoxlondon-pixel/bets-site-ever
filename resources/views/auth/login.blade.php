
@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Welcome back</h1>
    <p class="muted text-sm mb-6">Sign in to continue to your audits.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label class="label" for="password">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm muted" style="text-decoration:none;">Forgot it?</a>
            </div>
            <input class="field" id="password" type="password" name="password" required autocomplete="current-password">
        </div>

        <label class="flex items-center gap-2.5" style="font-size:.92rem;">
            <input type="checkbox" name="remember" value="1">
            <span>Keep me signed in on this device</span>
        </label>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
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
        No account yet? <a href="{{ route('register') }}" class="underline">Create one free</a>
    </p>
</div>

@endsection

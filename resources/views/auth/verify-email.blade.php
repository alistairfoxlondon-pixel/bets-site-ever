
@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')

<div class="card" style="padding:1.8rem;text-align:center;">
    <span class="icon-box" style="margin:0 auto 1rem;">
        @include('partials.icons', ['name' => 'mail', 'class' => 'w-5 h-5'])
    </span>

    <h1 class="text-2xl mb-2">Confirm your email</h1>
    <p class="muted text-sm mb-6">
        We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
        Confirming it keeps your account secure and lets us send audit results.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
        @csrf
        <button class="btn btn-primary btn-block" type="submit">Send the link again</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-quiet btn-block" type="submit">Sign out</button>
    </form>
</div>

@endsection

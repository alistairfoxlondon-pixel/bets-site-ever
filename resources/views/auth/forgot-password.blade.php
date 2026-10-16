
@extends('layouts.auth')

@section('title', 'Reset your password')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Reset your password</h1>
    <p class="muted text-sm mb-6">We will email you a link to set a new one.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Send the reset link</button>
    </form>

    <p class="text-center muted text-sm mt-6">
        <a href="{{ route('login') }}" class="underline">Back to sign in</a>
    </p>
</div>

@endsection

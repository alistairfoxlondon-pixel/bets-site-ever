
@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Choose a new password</h1>
    <p class="muted text-sm mb-6">Make it different from the one you just replaced.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
        </div>

        <div>
            <label class="label" for="password">New password</label>
            <input class="field" id="password" type="password" name="password" required autocomplete="new-password">
        </div>

        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input class="field" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Save the new password</button>
    </form>
</div>

@endsection

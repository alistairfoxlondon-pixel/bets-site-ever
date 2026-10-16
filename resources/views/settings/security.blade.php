
@extends('layouts.app')

@section('title', 'Security')
@section('heading', 'Security')
@section('subheading', 'Password, email address and sign in protection')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Change the password</h2>
        <form method="POST" action="{{ route('settings.password') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="current_password">Current password</label>
                <input class="field" id="current_password" name="current_password" type="password" required>
            </div>
            <div>
                <label class="label" for="password">New password</label>
                <input class="field" id="password" name="password" type="password" required>
                <p class="hint mt-1">At least eight characters with a number.</p>
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirm the new password</label>
                <input class="field" id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Update the password</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Change the email address</h2>
            <p class="muted text-sm mb-4">We will ask you to confirm the new address before it becomes active.</p>
            <form method="POST" action="{{ route('settings.email') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="label" for="email">New email</label>
                    <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="label" for="email_password">Current password</label>
                    <input class="field" id="email_password" name="password" type="password" required>
                </div>
                <button class="btn btn-ghost" type="submit">Update the email</button>
            </form>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Two factor authentication</h2>
            @if ($user->hasTwoFactorEnabled())
                <p class="muted text-sm mb-4">Two factor authentication is on. A code is required at every sign in.</p>
                <a href="{{ route('settings.two-factor') }}" class="btn btn-ghost btn-sm">Manage two factor</a>
            @else
                <p class="muted text-sm mb-4">Add a second step to your sign in with an authenticator app.</p>
                <a href="{{ route('settings.two-factor') }}" class="btn btn-primary btn-sm">Turn on two factor</a>
            @endif
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Recent sign ins</h2>
            <dl class="space-y-2" style="font-size:.9rem;">
                <div class="flex justify-between">
                    <dt class="muted">Last sign in</dt>
                    <dd>{{ $user->last_login_at?->diffForHumans() ?? 'never' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Sign in count</dt>
                    <dd class="mono">{{ $user->login_count }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Last IP</dt>
                    <dd class="mono">{{ $user->last_login_ip ?? 'unknown' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

@endsection

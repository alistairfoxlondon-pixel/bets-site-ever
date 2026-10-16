
@extends('layouts.admin')

@section('title', $user->name)
@section('heading', $user->name)
@section('subheading', $user->email)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Account</h2>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
            @csrf
            <div class="form-grid">
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="field" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div>
                    <label class="label" for="email">Email</label>
                    <input class="field" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div>
                    <label class="label" for="phone">Phone</label>
                    <input class="field" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                </div>
                <div>
                    <label class="label" for="country">Country</label>
                    <input class="field" id="country" name="country" value="{{ old('country', $user->country) }}">
                </div>
                <div>
                    <label class="label" for="currency">Currency</label>
                    <select class="field" id="currency" name="currency">
                        <option value="USD" @selected($user->currency === 'USD')>USD</option>
                        <option value="BDT" @selected($user->currency === 'BDT')>BDT</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="role">Role</label>
                    <select class="field" id="role" name="role">
                        <option value="user" @selected($user->role === 'user')>User</option>
                        <option value="admin" @selected($user->role === 'admin')>Administrator</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="label" for="status">Status</label>
                <select class="field" id="status" name="status">
                    <option value="active" @selected($user->status === 'active')>Active</option>
                    <option value="suspended" @selected($user->status === 'suspended')>Suspended</option>
                </select>
            </div>

            <button class="btn btn-primary" type="submit">Save the account</button>
        </form>

        <div class="rule my-5"></div>

        <div class="grid grid-2" style="gap:1rem;">
            <div>
                <h3 class="text-lg mb-3">Credits</h3>
                <p class="muted text-sm mb-2">Balance: <strong class="mono">{{ number_format((float) $user->credits_balance, 0) }}</strong></p>
                <form method="POST" action="{{ route('admin.users.credits', $user) }}" class="space-y-2">
                    @csrf
                    <input class="field" name="amount" type="number" step="1" placeholder="10" required>
                    <input class="field" name="reason" type="text" placeholder="Reason for the adjustment">
                    <button class="btn btn-ghost btn-sm btn-block" type="submit">Adjust credits</button>
                </form>
            </div>

            <div>
                <h3 class="text-lg mb-3">Password</h3>
                <form method="POST" action="{{ route('admin.users.password', $user) }}" class="space-y-2">
                    @csrf
                    <input class="field" name="password" type="password" placeholder="New password" required>
                    <input class="field" name="password_confirmation" type="password" placeholder="Confirm password" required>
                    <button class="btn btn-ghost btn-sm btn-block" type="submit">Reset the password</button>
                </form>
            </div>
        </div>

        <div class="rule my-5"></div>

        <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
            @csrf
            <input type="hidden" name="suspend" value="{{ $user->isSuspended() ? 0 : 1 }}">
            <button class="btn {{ $user->isSuspended() ? 'btn-primary' : 'btn-danger' }} btn-sm" type="submit">
                {{ $user->isSuspended() ? 'Restore the account' : 'Suspend the account' }}
            </button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Summary</h2>
            <dl class="space-y-2" style="font-size:.93rem;">
                <div class="flex justify-between"><dt class="muted">Audits</dt><dd class="mono">{{ $user->audits_count }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Articles</dt><dd class="mono">{{ $user->documents_count }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Referrals</dt><dd class="mono">{{ $user->referrals_count }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Wallet</dt><dd class="mono">${{ number_format((float) $user->wallet_balance, 2) }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Last sign in</dt><dd>{{ $user->last_login_at?->diffForHumans() ?? 'never' }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Two factor</dt><dd>{{ $user->hasTwoFactorEnabled() ? 'On' : 'Off' }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Referral code</dt><dd class="mono">{{ $user->referral_code }}</dd></div>
            </dl>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Recent audits</h2>
            @if ($audits->isEmpty())
                <p class="muted text-sm">None.</p>
            @else
                <ul class="space-y-2" style="font-size:.9rem;">
                    @foreach ($audits as $audit)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('admin.audits.show', $audit) }}" class="truncate">{{ $audit->domain }}</a>
                            <span class="mono faint">{{ $audit->score }}/100</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Invoices</h2>
            @if ($invoices->isEmpty())
                <p class="muted text-sm">None.</p>
            @else
                <ul class="space-y-2" style="font-size:.9rem;">
                    @foreach ($invoices as $invoice)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="mono">{{ $invoice->number }}</a>
                            <span class="badge {{ $invoice->status === 'paid' ? 'badge-pass' : 'badge-warn' }}">{{ ucfirst($invoice->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

@endsection

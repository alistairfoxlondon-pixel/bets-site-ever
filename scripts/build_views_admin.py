#!/usr/bin/env python3
"""Write the administration views."""
import os

ROOT = "/home/user/bets-site-ever/resources/views/admin"
FILES = {}

FILES['dashboard.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Control panel')
@section('subheading', 'Revenue, accounts and the state of the platform')

@section('content')

<div class="grid grid-4" style="gap:1rem;">
    <div class="stat">
        <div class="k">Revenue</div>
        <div class="v">${{ number_format($revenue, 2) }}</div>
        <div class="s">${{ number_format($revenueThisMonth, 2) }} this month</div>
    </div>
    <div class="stat">
        <div class="k">Accounts</div>
        <div class="v">{{ number_format($users) }}</div>
        <div class="s">{{ $newUsers }} joined this month</div>
    </div>
    <div class="stat">
        <div class="k">Subscriptions</div>
        <div class="v">{{ number_format($activeSubscriptions) }}</div>
        <div class="s">active or on trial</div>
    </div>
    <div class="stat">
        <div class="k">Audits</div>
        <div class="v">{{ number_format($audits) }}</div>
        <div class="s">{{ $failedAudits }} failed</div>
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Revenue, last 30 days</h2>
            <span class="chip">${{ number_format(array_sum(array_column($chart, 'amount')), 2) }}</span>
        </div>

        @php
            $max = max(1, max(array_map(fn ($point) => $point['amount'], $chart)));
        @endphp

        <div style="display:flex;align-items:flex-end;gap:3px;height:150px;">
            @foreach ($chart as $point)
                <div style="flex:1;background:{{ $point['amount'] > 0 ? 'var(--color-brand)' : 'var(--color-line)' }};height:{{ $point['amount'] > 0 ? max(3, round($point['amount'] / $max * 150)) : 3 }}px;border-radius:1px;"
                     title="{{ $point['date'] }}: ${{ number_format($point['amount'], 2) }}"></div>
            @endforeach
        </div>

        <div class="flex justify-between mt-2">
            <span class="faint text-xs mono">{{ $chart[0]['date'] ?? '' }}</span>
            <span class="faint text-xs mono">{{ $chart[count($chart) - 1]['date'] ?? '' }}</span>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Revenue by gateway</h2>
        </div>

        @if ($byGateway->isEmpty())
            <p class="muted text-sm">No payments have been recorded yet.</p>
        @else
            <div class="space-y-3">
                @foreach ($byGateway as $gateway => $amount)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span>{{ ucfirst($gateway) }}</span>
                            <span class="mono muted">${{ number_format($amount, 2) }}</span>
                        </div>
                        <div class="meter">
                            <span style="width:{{ max(2, round($amount / max(1, $revenue) * 100)) }}%;"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="rule my-4"></div>

        <div class="grid grid-2" style="gap:.75rem;">
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Plans live</div>
                <div class="v" style="font-size:1.3rem;">{{ $plans }}</div>
            </div>
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Gateways ready</div>
                <div class="v" style="font-size:1.3rem;">{{ $readyGateways }}/{{ $gateways }}</div>
            </div>
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Articles</div>
                <div class="v" style="font-size:1.3rem;">{{ number_format($documents) }}</div>
            </div>
            <div class="stat" style="padding:.7rem .8rem;">
                <div class="k">Suspended</div>
                <div class="v" style="font-size:1.3rem;">{{ $suspendedUsers }}</div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Strongest recent audits</h2>
            <a href="{{ route('admin.audits.index') }}" class="btn btn-quiet btn-sm">All audits</a>
        </div>

        @if ($topAudits->isEmpty())
            <p class="muted text-sm">Nothing yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>Site</th><th>Probability</th><th>Mode</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($topAudits as $audit)
                            <tr>
                                <td><a href="{{ route('admin.audits.show', $audit) }}" class="font-semibold">{{ $audit->domain }}</a></td>
                                <td class="num">{{ $audit->probability }}%</td>
                                <td class="text-sm muted">{{ ucfirst($audit->mode) }}</td>
                                <td class="text-sm muted">{{ $audit->completed_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Recent payments</h2>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-quiet btn-sm">Invoices</a>
        </div>

        @if ($recentPayments->isEmpty())
            <p class="muted text-sm">Nothing yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>Gateway</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($recentPayments as $payment)
                            <tr>
                                <td class="text-sm">{{ ucfirst($payment->gateway) }}</td>
                                <td class="num">${{ number_format((float) $payment->amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $payment->status === 'paid' ? 'badge-pass' : 'badge-fail' }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                                <td class="text-sm muted">{{ $payment->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
'''

FILES['users/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'Users')
@section('subheading', 'Every account on the platform')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;align-items:end;">
        <div>
            <label class="label" for="search">Search</label>
            <input class="field" id="search" name="search" value="{{ request('search') }}" placeholder="Name, email or code">
        </div>
        <div>
            <label class="label" for="status">Status</label>
            <select class="field" id="status" name="status">
                <option value="">Any</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
            </select>
        </div>
        <div>
            <label class="label" for="role">Role</label>
            <select class="field" id="role" name="role">
                <option value="">Any</option>
                <option value="user" @selected(request('role') === 'user')>User</option>
                <option value="admin" @selected(request('role') === 'admin')>Administrator</option>
            </select>
        </div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table-app">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Credits</th>
                    <th>Audits</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="font-semibold">{{ $user->name }}</a>
                            <div class="faint text-xs">{{ $user->email }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $user->isAdmin() ? 'badge-info' : 'badge-muted' }}">{{ ucfirst($user->role) }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $user->isSuspended() ? 'badge-fail' : 'badge-pass' }}">{{ ucfirst($user->status) }}</span>
                        </td>
                        <td class="num">{{ number_format((float) $user->credits_balance, 0) }}</td>
                        <td class="num muted">{{ $user->audits_count }}</td>
                        <td class="text-sm muted">{{ $user->created_at->format('j M Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-quiet btn-sm">Open</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>

@endsection
'''

FILES['users/show.blade.php'] = r'''
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
                            <span class="mono faint">{{ $audit->probability }}%</span>
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
'''

FILES['plans/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Plans')
@section('heading', 'Plans')
@section('subheading', 'Pricing, credits and trial length')

@section('content')

@foreach ($plans as $plan)
    <div class="panel mb-4">
        <div class="panel-head">
            <h2 class="text-lg">{{ $plan->name }}</h2>
            <div class="flex items-center gap-2">
                <span class="badge {{ $plan->is_active ? 'badge-pass' : 'badge-muted' }}">{{ $plan->is_active ? 'Live' : 'Hidden' }}</span>
                @if ($plan->is_featured)
                    <span class="badge badge-info">Featured</span>
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="space-y-4">
            @csrf
            <div class="form-grid">
                <div>
                    <label class="label">Name</label>
                    <input class="field" name="name" value="{{ $plan->name }}" required>
                </div>
                <div>
                    <label class="label">Tagline</label>
                    <input class="field" name="tagline" value="{{ $plan->tagline }}">
                </div>
                <div>
                    <label class="label">Monthly price in USD</label>
                    <input class="field" name="price_monthly" type="number" step="0.01" min="0" value="{{ $plan->price_monthly }}" required>
                </div>
                <div>
                    <label class="label">Yearly price in USD</label>
                    <input class="field" name="price_yearly" type="number" step="0.01" min="0" value="{{ $plan->price_yearly }}" required>
                </div>
                <div>
                    <label class="label">Monthly price in BDT</label>
                    <input class="field" name="price_monthly_bdt" type="number" step="0.01" min="0" value="{{ $plan->price_monthly_bdt }}" required>
                </div>
                <div>
                    <label class="label">Yearly price in BDT</label>
                    <input class="field" name="price_yearly_bdt" type="number" step="0.01" min="0" value="{{ $plan->price_yearly_bdt }}" required>
                </div>
                <div>
                    <label class="label">Credits per period</label>
                    <input class="field" name="credits" type="number" min="0" value="{{ $plan->credits }}" required>
                </div>
                <div>
                    <label class="label">Maximum tracked sites</label>
                    <input class="field" name="max_sites" type="number" min="1" value="{{ $plan->max_sites }}" required>
                </div>
                <div>
                    <label class="label">Trial days</label>
                    <input class="field" name="trial_days" type="number" min="0" value="{{ $plan->trial_days }}" required>
                </div>
                <div>
                    <label class="label">Sort order</label>
                    <input class="field" name="sort_order" type="number" min="0" value="{{ $plan->sort_order }}" required>
                </div>
            </div>

            <div>
                <label class="label">Features, one per line</label>
                <textarea class="field" name="features" rows="5">{{ is_array($plan->features) ? implode("\n", $plan->features) : '' }}</textarea>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2" style="font-size:.92rem;">
                    <input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Live on the pricing page
                </label>
                <label class="flex items-center gap-2" style="font-size:.92rem;">
                    <input type="checkbox" name="is_featured" value="1" @checked($plan->is_featured)> Mark as recommended
                </label>
                <button class="btn btn-primary btn-sm" type="submit">Save the plan</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="mt-3"
              onsubmit="return confirm('Delete this plan?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-quiet btn-sm" type="submit">Delete this plan</button>
        </form>
    </div>
@endforeach

<div class="panel">
    <h2 class="text-lg mb-3">Add a plan</h2>
    <form method="POST" action="{{ route('admin.plans.store') }}" class="space-y-4">
        @csrf
        <div class="form-grid">
            <div>
                <label class="label">Name</label>
                <input class="field" name="name" required>
            </div>
            <div>
                <label class="label">Tagline</label>
                <input class="field" name="tagline">
            </div>
            <div>
                <label class="label">Monthly price in USD</label>
                <input class="field" name="price_monthly" type="number" step="0.01" min="0" value="0" required>
            </div>
            <div>
                <label class="label">Yearly price in USD</label>
                <input class="field" name="price_yearly" type="number" step="0.01" min="0" value="0" required>
            </div>
            <div>
                <label class="label">Monthly price in BDT</label>
                <input class="field" name="price_monthly_bdt" type="number" step="0.01" min="0" value="0" required>
            </div>
            <div>
                <label class="label">Yearly price in BDT</label>
                <input class="field" name="price_yearly_bdt" type="number" step="0.01" min="0" value="0" required>
            </div>
            <div>
                <label class="label">Credits per period</label>
                <input class="field" name="credits" type="number" min="0" value="10" required>
            </div>
            <div>
                <label class="label">Maximum tracked sites</label>
                <input class="field" name="max_sites" type="number" min="1" value="1" required>
            </div>
            <div>
                <label class="label">Trial days</label>
                <input class="field" name="trial_days" type="number" min="0" value="0" required>
            </div>
            <div>
                <label class="label">Sort order</label>
                <input class="field" name="sort_order" type="number" min="0" value="0" required>
            </div>
        </div>

        <div>
            <label class="label">Features, one per line</label>
            <textarea class="field" name="features" rows="4"></textarea>
        </div>

        <button class="btn btn-primary" type="submit">Create the plan</button>
    </form>
</div>

@endsection
'''

FILES['gateways/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Gateways')
@section('heading', 'Payment gateways')
@section('subheading', 'Switch a method on, then drop in the credentials')

@section('content')

@foreach ($rows as $row)
    @php $gateway = $row['gateway']; @endphp
    <div class="panel mb-4">
        <div class="panel-head">
            <div class="flex items-center gap-3">
                <img src="{{ asset('brand/'.($gateway->logo ?: 'bank').'.svg') }}" alt="{{ $gateway->name }}" height="24"
                     onerror="this.style.display='none'">
                <h2 class="text-lg">{{ $gateway->name }}</h2>
                @if (! $row['supported'])
                    <span class="badge badge-warn">Driver not installed</span>
                @elseif (! $row['configured'])
                    <span class="badge badge-warn">Missing credentials</span>
                @else
                    <span class="badge badge-pass">Ready</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.gateways.toggle', $gateway) }}">
                @csrf
                <button class="btn btn-sm {{ $gateway->is_enabled ? 'btn-ghost' : 'btn-primary' }}" type="submit">
                    {{ $gateway->is_enabled ? 'Pause' : 'Enable' }}
                </button>
            </form>
        </div>

        <p class="muted text-sm mb-4">{{ $gateway->description }}</p>

        <form method="POST" action="{{ route('admin.gateways.update', $gateway) }}" class="space-y-4">
            @csrf
            <div class="form-grid">
                <div>
                    <label class="label">Name shown to customers</label>
                    <input class="field" name="name" value="{{ $gateway->name }}" required>
                </div>
                <div>
                    <label class="label">Logo file name</label>
                    <input class="field" name="logo" value="{{ $gateway->logo }}" placeholder="stripe.svg">
                </div>
                <div>
                    <label class="label">Accepted currencies</label>
                    <input class="field" name="currencies" value="{{ implode(', ', (array) $gateway->currencies) }}" placeholder="USD, BDT">
                </div>
                <div>
                    <label class="label">Countries</label>
                    <input class="field" name="countries" value="{{ implode(', ', (array) $gateway->countries) }}" placeholder="Bangladesh, India">
                </div>
                <div>
                    <label class="label">Sort order</label>
                    <input class="field" name="sort_order" type="number" min="0" value="{{ $gateway->sort_order }}" required>
                </div>
                <div>
                    <label class="label">Description</label>
                    <input class="field" name="description" value="{{ $gateway->description }}">
                </div>
            </div>

            <div>
                <p class="label">Credentials</p>
                <p class="hint mb-2">Leave a field empty to keep the value that is already stored. Values are encrypted in the database.</p>
                <div class="form-grid">
                    @foreach ($gateway->credentialFields() as $field => $placeholder)
                        <div>
                            <label class="label">{{ $field }}</label>
                            <input class="field" name="credentials[{{ $field }}]" type="text" value="" placeholder="{{ $placeholder }}">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2" style="font-size:.92rem;">
                    <input type="checkbox" name="is_test_mode" value="1" @checked($gateway->is_test_mode)> Test mode
                </label>
                <button class="btn btn-primary btn-sm" type="submit">Save the gateway</button>
            </div>
        </form>
    </div>
@endforeach

@endsection
'''

FILES['invoices/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Invoices')
@section('heading', 'Invoices')
@section('subheading', 'Every order raised in the platform')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem;align-items:end;">
        <div>
            <label class="label" for="search">Search</label>
            <input class="field" id="search" name="search" value="{{ request('search') }}" placeholder="Number or email">
        </div>
        <div>
            <label class="label" for="status">Status</label>
            <select class="field" id="status" name="status">
                <option value="">Any</option>
                @foreach (['pending', 'paid', 'failed', 'awaiting_review', 'refunded'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table-app">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    <tr>
                        <td class="mono text-sm">{{ $invoice->number }}</td>
                        <td class="text-sm">
                            {{ $invoice->user?->name }}
                            <div class="faint text-xs">{{ $invoice->user?->email }}</div>
                        </td>
                        <td class="num">
                            {{ $invoice->currency === 'BDT' ? '৳'.number_format((float) $invoice->amount_bdt, 2) : '$'.number_format((float) $invoice->amount, 2) }}
                        </td>
                        <td>
                            <span class="badge {{ $invoice->status === 'paid' ? 'badge-pass' : ($invoice->status === 'failed' ? 'badge-fail' : 'badge-warn') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="text-sm muted">{{ $invoice->created_at->format('j M Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-quiet btn-sm">Open</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
</div>

@endsection
'''

FILES['invoices/show.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Invoice '.$invoice->number)
@section('heading', 'Invoice '.$invoice->number)
@section('subheading', $invoice->user?->email)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Details</h2>
            <span class="badge {{ $invoice->status === 'paid' ? 'badge-pass' : 'badge-warn' }}">{{ ucfirst($invoice->status) }}</span>
        </div>

        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between"><dt class="muted">Customer</dt><dd>{{ $invoice->user?->name }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Description</dt><dd class="text-right">{{ $invoice->description }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Amount</dt><dd class="mono">${{ number_format((float) $invoice->amount, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Taka</dt><dd class="mono">৳{{ number_format((float) $invoice->amount_bdt, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Period</dt><dd>{{ $invoice->period_start?->format('j M Y') }} to {{ $invoice->period_end?->format('j M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Paid</dt><dd>{{ $invoice->paid_at?->format('j M Y, H:i') ?? 'not paid' }}</dd></div>
        </dl>

        @if (! $invoice->isPaid())
            <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}" class="mt-5">
                @csrf
                <button class="btn btn-primary" type="submit">Mark as paid and activate</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Payments</h2>
        @if ($invoice->payments->isEmpty())
            <p class="muted text-sm">No payment attempt recorded.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>Gateway</th><th>Reference</th><th>Status</th><th>When</th></tr></thead>
                    <tbody>
                        @foreach ($invoice->payments as $payment)
                            <tr>
                                <td class="text-sm">{{ ucfirst($payment->gateway) }}</td>
                                <td class="mono text-xs">{{ $payment->gateway_transaction_id }}</td>
                                <td>
                                    <span class="badge {{ $payment->status === 'paid' ? 'badge-pass' : 'badge-fail' }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                                <td class="text-sm muted">{{ $payment->created_at->format('j M H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($invoice->subscription)
            <div class="rule my-4"></div>
            <h3 class="text-lg mb-2">Subscription</h3>
            <dl class="space-y-2" style="font-size:.92rem;">
                <div class="flex justify-between"><dt class="muted">Plan</dt><dd>{{ $invoice->subscription->plan?->name }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Status</dt><dd>{{ ucfirst($invoice->subscription->status) }}</dd></div>
                <div class="flex justify-between"><dt class="muted">Renews</dt><dd>{{ $invoice->subscription->renews_at?->format('j M Y') ?? 'not set' }}</dd></div>
            </dl>
        @endif
    </div>
</div>

@endsection
'''

FILES['coupons/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Coupons')
@section('heading', 'Coupons')
@section('subheading', 'Discount codes for campaigns and partners')

@section('content')

<div class="panel mb-4">
    <h2 class="text-lg mb-3">Create a coupon</h2>
    <form method="POST" action="{{ route('admin.coupons.store') }}" class="space-y-4">
        @csrf
        <div class="form-grid">
            <div>
                <label class="label" for="code">Code</label>
                <input class="field mono" id="code" name="code" placeholder="LAUNCH20" required>
            </div>
            <div>
                <label class="label" for="type">Type</label>
                <select class="field" id="type" name="type">
                    <option value="percent">Percentage off</option>
                    <option value="fixed">Fixed amount off</option>
                </select>
            </div>
            <div>
                <label class="label" for="value">Value</label>
                <input class="field" id="value" name="value" type="number" step="0.01" min="0" value="20" required>
            </div>
            <div>
                <label class="label" for="max_uses">Maximum redemptions</label>
                <input class="field" id="max_uses" name="max_uses" type="number" min="1" placeholder="Unlimited">
            </div>
            <div>
                <label class="label" for="starts_at">Starts</label>
                <input class="field" id="starts_at" name="starts_at" type="date">
            </div>
            <div>
                <label class="label" for="expires_at">Expires</label>
                <input class="field" id="expires_at" name="expires_at" type="date">
            </div>
        </div>

        <label class="flex items-center gap-2" style="font-size:.92rem;">
            <input type="checkbox" name="is_active" value="1" checked> Active
        </label>

        <button class="btn btn-primary" type="submit">Create the coupon</button>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table-app">
            <thead>
                <tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Expires</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($coupons as $coupon)
                    <tr>
                        <td class="mono">{{ $coupon->code }}</td>
                        <td class="text-sm muted">{{ $coupon->type }}</td>
                        <td class="num">{{ $coupon->type === 'percent' ? $coupon->value.'%' : '$'.number_format((float) $coupon->value, 2) }}</td>
                        <td class="num muted">{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                        <td class="text-sm muted">{{ $coupon->expires_at?->format('j M Y') ?? 'never' }}</td>
                        <td>
                            <span class="badge {{ $coupon->isValid() ? 'badge-pass' : 'badge-muted' }}">{{ $coupon->isValid() ? 'Valid' : 'Inactive' }}</span>
                        </td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                  onsubmit="return confirm('Delete this coupon?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-quiet btn-sm" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
'''

FILES['audits/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Audits')
@section('heading', 'Audits')
@section('subheading', 'Every audit run on the platform')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid" style="grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;align-items:end;">
        <div>
            <label class="label" for="search">Search</label>
            <input class="field" id="search" name="search" value="{{ request('search') }}" placeholder="Domain or URL">
        </div>
        <div>
            <label class="label" for="status">Status</label>
            <select class="field" id="status" name="status">
                <option value="">Any</option>
                @foreach (['queued', 'running', 'completed', 'failed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="mode">Mode</label>
            <select class="field" id="mode" name="mode">
                <option value="">Any</option>
                @foreach (['normal', 'standard', 'premium'] as $mode)
                    <option value="{{ $mode }}" @selected(request('mode') === $mode)>{{ ucfirst($mode) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table-app">
            <thead>
                <tr><th>Site</th><th>User</th><th>Mode</th><th>Probability</th><th>Status</th><th>Duration</th><th>Date</th></tr>
            </thead>
            <tbody>
                @foreach ($audits as $audit)
                    <tr>
                        <td><a href="{{ route('admin.audits.show', $audit) }}" class="font-semibold">{{ $audit->domain }}</a></td>
                        <td class="text-sm muted">{{ $audit->user?->email }}</td>
                        <td class="text-sm muted">{{ ucfirst($audit->mode) }}</td>
                        <td class="num">{{ $audit->probability !== null ? $audit->probability.'%' : '--' }}</td>
                        <td>
                            <span class="badge {{ $audit->status === 'completed' ? 'badge-pass' : ($audit->status === 'failed' ? 'badge-fail' : 'badge-info') }}">
                                {{ ucfirst($audit->status) }}
                            </span>
                        </td>
                        <td class="num muted">{{ $audit->duration_ms ? round($audit->duration_ms / 1000).'s' : '--' }}</td>
                        <td class="text-sm muted">{{ $audit->created_at->format('j M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $audits->links() }}</div>
</div>

@endsection
'''

FILES['audits/show.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Audit '.$audit->domain)
@section('heading', $audit->domain ?? 'Audit')
@section('subheading', $audit->url)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Summary</h2>
            <a href="{{ route('audits.report', $audit) }}" class="btn btn-ghost btn-sm">PDF report</a>
        </div>

        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between"><dt class="muted">User</dt><dd>{{ $audit->user?->email }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Mode</dt><dd>{{ ucfirst($audit->mode) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Status</dt><dd>{{ ucfirst($audit->status) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Probability</dt><dd class="mono">{{ $audit->probability ?? '--' }}%</dd></div>
            <div class="flex justify-between"><dt class="muted">Score</dt><dd class="mono">{{ $audit->score ?? '--' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Pages crawled</dt><dd class="mono">{{ $audit->pages_crawled }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Findings</dt><dd class="mono">{{ $audit->findings_count }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Model</dt><dd class="mono text-xs">{{ $audit->model ?? 'rules only' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Tokens</dt><dd class="mono">{{ $audit->tokens_used }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Duration</dt><dd class="mono">{{ $audit->duration_ms ? round($audit->duration_ms / 1000).' seconds' : '--' }}</dd></div>
        </dl>

        @if ($audit->error_message)
            <div class="flash flash-error mt-4">{{ $audit->error_message }}</div>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Readiness by area</h2>
        <div class="space-y-3">
            @foreach ($categories as $category)
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span>{{ $category['label'] }}</span>
                        <span class="mono muted">{{ (int) round($category['score']) }}</span>
                    </div>
                    <div class="meter"><span style="width:{{ max(2, min(100, (int) round($category['score']))) }}%;"></span></div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="panel mt-4">
    <h2 class="text-lg mb-3">Findings</h2>
    <div class="table-wrap">
        <table class="table-app">
            <thead><tr><th>Severity</th><th>Category</th><th>Finding</th><th>Weight</th></tr></thead>
            <tbody>
                @foreach ($audit->findings as $finding)
                    <tr>
                        <td>
                            <span class="badge {{ $finding->severity === 'critical' ? 'badge-fail' : ($finding->severity === 'warning' ? 'badge-warn' : ($finding->severity === 'pass' ? 'badge-pass' : 'badge-muted')) }}">
                                {{ $finding->severityLabel() }}
                            </span>
                        </td>
                        <td class="text-sm muted">{{ $finding->category }}</td>
                        <td>
                            <strong>{{ $finding->title }}</strong>
                            @if ($finding->detail)
                                <div class="faint text-xs">{{ Str::limit($finding->detail, 140) }}</div>
                            @endif
                        </td>
                        <td class="num muted">{{ $finding->weight }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
'''

FILES['blog/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Blog')
@section('heading', 'Blog')
@section('subheading', 'Articles published on the public site')

@section('actions')
    <a href="{{ route('admin.blog.create') }}" class="btn btn-primary btn-sm">Write an article</a>
@endsection

@section('content')

<div class="panel">
    @if ($posts->isEmpty())
        <p class="muted">No articles yet.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr><th>Title</th><th>Author</th><th>Status</th><th>Published</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($posts as $post)
                        <tr>
                            <td>
                                <a href="{{ route('admin.blog.edit', $post) }}" class="font-semibold">{{ $post->title }}</a>
                                <div class="faint text-xs mono">/{{ $post->slug }}</div>
                            </td>
                            <td class="text-sm muted">{{ $post->author?->name ?? 'system' }}</td>
                            <td>
                                <span class="badge {{ $post->status === 'published' ? 'badge-pass' : 'badge-muted' }}">{{ ucfirst($post->status) }}</span>
                            </td>
                            <td class="text-sm muted">{{ $post->published_at?->format('j M Y') ?? 'not published' }}</td>
                            <td class="text-right">
                                <a href="{{ route('blog.show', $post->slug) }}" class="btn btn-quiet btn-sm" target="_blank" rel="noopener">View</a>
                                <a href="{{ route('admin.blog.edit', $post) }}" class="btn btn-quiet btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
'''

FILES['blog/edit.blade.php'] = r'''
@extends('layouts.admin')

@section('title', $post->exists ? 'Edit article' : 'New article')
@section('heading', $post->exists ? 'Edit article' : 'New article')
@section('subheading', $post->exists ? $post->title : 'Write something worth reading')

@section('content')

<form method="POST" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
      class="grid grid-2" style="gap:1.25rem;align-items:start;">
    @csrf
    @if ($post->exists)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div class="panel">
            <div class="mb-3">
                <label class="label" for="title">Title</label>
                <input class="field" id="title" name="title" value="{{ old('title', $post->title) }}" required>
            </div>

            <div class="mb-3">
                <label class="label" for="excerpt">Excerpt</label>
                <textarea class="field" id="excerpt" name="excerpt" rows="2">{{ old('excerpt', $post->excerpt) }}</textarea>
            </div>

            <div>
                <label class="label" for="body">Body</label>
                <div class="editor-shell">
                    <textarea class="field" id="body" name="body" rows="18" style="border:0;border-radius:0;" data-editor="body">{{ old('body', $post->body) }}</textarea>
                </div>
                <p class="hint mt-1">The editor keeps clean HTML so the article reads well on every device.</p>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Publishing</h2>

            <div class="mb-3">
                <label class="label" for="status">Status</label>
                <select class="field" id="status" name="status">
                    <option value="draft" @selected(old('status', $post->status ?: 'draft') === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="label" for="published_at">Publish date</label>
                <input class="field" id="published_at" name="published_at" type="datetime-local"
                       value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="mb-3">
                <label class="label" for="tags">Tags, comma separated</label>
                <input class="field" id="tags" name="tags" value="{{ old('tags', is_array($post->tags) ? implode(', ', $post->tags) : '') }}">
            </div>

            <div class="mb-3">
                <label class="label" for="cover_image">Cover image address</label>
                <input class="field" id="cover_image" name="cover_image" value="{{ old('cover_image', $post->cover_image) }}">
            </div>

            <button class="btn btn-primary btn-block" type="submit">{{ $post->exists ? 'Save the article' : 'Create the article' }}</button>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Search engine</h2>
            <div class="mb-3">
                <label class="label" for="meta_title">Meta title</label>
                <input class="field" id="meta_title" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}">
            </div>
            <div>
                <label class="label" for="meta_description">Meta description</label>
                <textarea class="field" id="meta_description" name="meta_description" rows="3">{{ old('meta_description', $post->meta_description) }}</textarea>
            </div>
        </div>

        @if ($post->exists)
            <form method="POST" action="{{ route('admin.blog.destroy', $post) }}"
                  onsubmit="return confirm('Delete this article?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm btn-block" type="submit">Delete the article</button>
            </form>
        @endif
    </div>
</form>

@endsection
'''

FILES['pages/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Pages')
@section('heading', 'Pages')
@section('subheading', 'The legal and static pages of the site')

@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm">New page</a>
@endsection

@section('content')

<div class="panel">
    @if ($pages->isEmpty())
        <p class="muted">No pages yet. The terms and privacy pages live here.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td><a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold">{{ $page->title }}</a></td>
                            <td class="mono text-xs">/{{ $page->slug }}</td>
                            <td>
                                <span class="badge {{ $page->status === 'published' ? 'badge-pass' : 'badge-muted' }}">{{ ucfirst($page->status) }}</span>
                            </td>
                            <td class="text-sm muted">{{ $page->updated_at->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('page.show', $page->slug) }}" class="btn btn-quiet btn-sm" target="_blank" rel="noopener">View</a>
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-quiet btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
'''

FILES['pages/edit.blade.php'] = r'''
@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'New page')
@section('heading', $page->exists ? 'Edit page' : 'New page')

@section('content')

<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
      class="grid grid-2" style="gap:1.25rem;align-items:start;">
    @csrf
    @if ($page->exists)
        @method('PUT')
    @endif

    <div class="panel">
        <div class="mb-3">
            <label class="label" for="title">Title</label>
            <input class="field" id="title" name="title" value="{{ old('title', $page->title) }}" required>
        </div>

        <div>
            <label class="label" for="body">Body</label>
            <div class="editor-shell">
                <textarea class="field" id="body" name="body" rows="20" style="border:0;border-radius:0;" data-editor="body">{{ old('body', $page->body) }}</textarea>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Publishing</h2>
            <div class="mb-3">
                <label class="label" for="status">Status</label>
                <select class="field" id="status" name="status">
                    <option value="draft" @selected(old('status', $page->status ?: 'draft') === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                </select>
            </div>
            <button class="btn btn-primary btn-block" type="submit">{{ $page->exists ? 'Save the page' : 'Create the page' }}</button>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Search engine</h2>
            <div class="mb-3">
                <label class="label" for="meta_title">Meta title</label>
                <input class="field" id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}">
            </div>
            <div>
                <label class="label" for="meta_description">Meta description</label>
                <textarea class="field" id="meta_description" name="meta_description" rows="3">{{ old('meta_description', $page->meta_description) }}</textarea>
            </div>
        </div>

        @if ($page->exists)
            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                  onsubmit="return confirm('Delete this page?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm btn-block" type="submit">Delete the page</button>
            </form>
        @endif
    </div>
</form>

@endsection
'''

FILES['messages/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Messages')
@section('heading', 'Contact messages')
@section('subheading', 'Everything sent through the public contact form')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem;align-items:end;">
        <div>
            <label class="label" for="status">Status</label>
            <select class="field" id="status" name="status">
                <option value="">Any</option>
                @foreach (['new', 'open', 'replied', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div></div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    @if ($messages->isEmpty())
        <p class="muted">No messages yet.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead><tr><th>From</th><th>Reason</th><th>Subject</th><th>Status</th><th>Received</th><th></th></tr></thead>
                <tbody>
                    @foreach ($messages as $message)
                        <tr>
                            <td>
                                <span class="font-semibold">{{ $message->name }}</span>
                                <div class="faint text-xs">{{ $message->email }}</div>
                            </td>
                            <td class="text-sm muted">{{ ucfirst(str_replace('_', ' ', $message->category)) }}</td>
                            <td class="text-sm">{{ $message->subject }}</td>
                            <td>
                                <span class="badge {{ $message->status === 'replied' ? 'badge-pass' : ($message->status === 'new' ? 'badge-info' : 'badge-muted') }}">
                                    {{ ucfirst($message->status) }}
                                </span>
                            </td>
                            <td class="text-sm muted">{{ $message->created_at->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $messages->links() }}</div>
    @endif
</div>

@endsection
'''

FILES['messages/show.blade.php'] = r'''
@extends('layouts.admin')

@section('title', $message->subject)
@section('heading', $message->subject)
@section('subheading', $message->name.' <'.$message->email.'>')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Message</h2>
        <p style="white-space:pre-line;font-size:.95rem;">{{ $message->message }}</p>

        <div class="rule my-4"></div>

        <dl class="space-y-2" style="font-size:.9rem;">
            <div class="flex justify-between"><dt class="muted">Reason</dt><dd>{{ ucfirst(str_replace('_', ' ', $message->category)) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Received</dt><dd>{{ $message->created_at->format('j M Y, H:i') }}</dd></div>
            <div class="flex justify-between"><dt class="muted">IP</dt><dd class="mono">{{ $message->ip ?? 'unknown' }}</dd></div>
            @if ($message->user)
                <div class="flex justify-between"><dt class="muted">Account</dt><dd>{{ $message->user->email }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Reply</h2>
        <form method="POST" action="{{ route('admin.messages.reply', $message) }}" class="space-y-3">
            @csrf
            <textarea class="field" name="reply" rows="8" required placeholder="Write the answer here.">{{ old('reply', $message->reply) }}</textarea>
            <button class="btn btn-primary btn-block" type="submit">Save the reply</button>
        </form>

        <div class="rule my-4"></div>

        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
              onsubmit="return confirm('Delete this message?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger btn-sm btn-block" type="submit">Delete the message</button>
        </form>
    </div>
</div>

@endsection
'''

FILES['chat/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Live chat')
@section('heading', 'Live chat')
@section('subheading', 'Conversations with signed in customers')

@section('content')

<div class="panel">
    @if ($conversations->isEmpty())
        <p class="muted">No conversations yet.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead><tr><th>Customer</th><th>Subject</th><th>Messages</th><th>Status</th><th>Last message</th><th></th></tr></thead>
                <tbody>
                    @foreach ($conversations as $conversation)
                        <tr>
                            <td>
                                <span class="font-semibold">{{ $conversation->user?->name }}</span>
                                <div class="faint text-xs">{{ $conversation->user?->email }}</div>
                            </td>
                            <td class="text-sm">{{ $conversation->subject }}</td>
                            <td class="num muted">{{ $conversation->messages_count }}</td>
                            <td>
                                <span class="badge {{ $conversation->status === 'open' ? 'badge-info' : 'badge-muted' }}">{{ ucfirst($conversation->status) }}</span>
                            </td>
                            <td class="text-sm muted">{{ $conversation->last_message_at?->diffForHumans() ?? 'never' }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.chat.show', $conversation) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $conversations->links() }}</div>
    @endif
</div>

@endsection
'''

FILES['chat/show.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Chat with '.$conversation->user?->name)
@section('heading', 'Chat with '.($conversation->user?->name ?? 'customer'))
@section('subheading', $conversation->subject)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Conversation</h2></div>

        <div class="space-y-3" style="max-height:60vh;overflow:auto;">
            @foreach ($messages as $message)
                <div style="display:flex;justify-content:{{ $message->sender_type === 'admin' ? 'flex-end' : 'flex-start' }};">
                    <div class="card-flat" style="padding:.7rem .9rem;max-width:80%;background:{{ $message->sender_type === 'admin' ? 'var(--color-brand-soft)' : 'var(--color-paper)' }};">
                        <p class="text-sm" style="white-space:pre-line;">{{ $message->body }}</p>
                        <p class="faint text-xs mt-1">
                            {{ $message->sender_type === 'admin' ? 'Support' : ($message->sender?->name ?? 'Customer') }}
                            &middot; {{ $message->created_at->format('j M, H:i') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Reply</h2>
        <form method="POST" action="{{ route('admin.chat.reply', $conversation) }}" class="space-y-3">
            @csrf
            <textarea class="field" name="body" rows="8" required placeholder="Write the reply here."></textarea>
            <button class="btn btn-primary btn-block" type="submit">Send the reply</button>
        </form>

        <div class="rule my-4"></div>

        <h3 class="text-lg mb-2">Customer</h3>
        <dl class="space-y-2" style="font-size:.92rem;">
            <div class="flex justify-between"><dt class="muted">Email</dt><dd>{{ $conversation->user?->email }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Plan</dt><dd>{{ $conversation->user?->activeSubscription?->plan?->name ?? 'free' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Credits</dt><dd class="mono">{{ number_format((float) ($conversation->user?->credits_balance ?? 0), 0) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Joined</dt><dd>{{ $conversation->user?->created_at->format('j M Y') }}</dd></div>
        </dl>
    </div>
</div>

@endsection
'''

FILES['affiliate/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Affiliate')
@section('heading', 'Affiliate programme')
@section('subheading', 'Commissions, top earners and withdrawal requests')

@section('content')

<div class="grid grid-3" style="gap:1rem;">
    <div class="stat">
        <div class="k">Commission rate</div>
        <div class="v">{{ (int) $rate }}%</div>
        <div class="s">of the first payment</div>
    </div>
    <div class="stat">
        <div class="k">Minimum payout</div>
        <div class="v">${{ number_format($minimum, 0) }}</div>
        <div class="s">balance needed to withdraw</div>
    </div>
    <div class="stat">
        <div class="k">Owed</div>
        <div class="v">${{ number_format($totalOwed, 2) }}</div>
        <div class="s">pending and approved</div>
    </div>
</div>

<div class="panel mt-4">
    <h2 class="text-lg mb-3">Programme settings</h2>
    <form method="POST" action="{{ route('admin.affiliate.settings') }}" class="form-grid">
        @csrf
        <div>
            <label class="label" for="commission_rate">Commission rate (percent)</label>
            <input class="field" id="commission_rate" name="commission_rate" type="number" step="0.1" min="0" max="100" value="{{ $rate }}" required>
        </div>
        <div>
            <label class="label" for="payout_minimum">Minimum withdrawal (USD)</label>
            <input class="field" id="payout_minimum" name="payout_minimum" type="number" step="1" min="0" value="{{ $minimum }}" required>
        </div>
        <div>
            <label class="label" for="cookie_days">Attribution window (days)</label>
            <input class="field" id="cookie_days" name="cookie_days" type="number" min="1" max="365" value="30" required>
        </div>
        <div class="flex items-end">
            <button class="btn btn-primary" type="submit">Save the settings</button>
        </div>
    </form>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Withdrawal requests</h2>
        @if ($payouts->isEmpty())
            <p class="muted text-sm">Nothing requested yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($payouts as $payout)
                            <tr>
                                <td class="text-sm">{{ $payout->user?->email }}</td>
                                <td class="num">${{ number_format((float) $payout->amount, 2) }}</td>
                                <td class="text-sm muted">{{ ucfirst($payout->method) }}</td>
                                <td>
                                    <span class="badge {{ $payout->status === 'paid' ? 'badge-pass' : ($payout->status === 'rejected' ? 'badge-fail' : 'badge-warn') }}">
                                        {{ ucfirst($payout->status) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.affiliate.payout', $payout) }}" class="btn btn-quiet btn-sm">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $payouts->links() }}</div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Top earners</h2>
            @if ($top->isEmpty())
                <p class="muted text-sm">Nothing yet.</p>
            @else
                <ul class="space-y-2" style="font-size:.92rem;">
                    @foreach ($top as $user)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="truncate">{{ $user->name }}</a>
                            <span class="mono faint">{{ $user->earnings_count }} sales</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Recent commissions</h2>
            @if ($earnings->isEmpty())
                <p class="muted text-sm">Nothing yet.</p>
            @else
                <ul class="space-y-2" style="font-size:.9rem;">
                    @foreach ($earnings as $earning)
                        <li class="flex items-center justify-between gap-3">
                            <span class="truncate muted">{{ $earning->description }}</span>
                            <span class="mono shrink-0">${{ number_format((float) $earning->amount, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

@endsection
'''

FILES['affiliate/payout.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Withdrawal request')
@section('heading', 'Withdrawal request')
@section('subheading', $payout->user?->email)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Request</h2>
        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between"><dt class="muted">User</dt><dd>{{ $payout->user?->name }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Amount</dt><dd class="mono">${{ number_format((float) $payout->amount, 2) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Method</dt><dd>{{ ucfirst($payout->method) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Requested</dt><dd>{{ $payout->created_at->format('j M Y, H:i') }}</dd></div>
        </dl>

        <div class="rule my-4"></div>

        <h3 class="text-lg mb-2">Account details</h3>
        <dl class="space-y-2" style="font-size:.92rem;">
            @foreach ((array) $payout->account_details as $key => $value)
                <div class="flex justify-between"><dt class="muted">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt><dd class="mono">{{ $value }}</dd></div>
            @endforeach
        </dl>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Decide</h2>
        <form method="POST" action="{{ route('admin.affiliate.payout.update', $payout) }}" class="space-y-3">
            @csrf
            <div>
                <label class="label" for="status">Status</label>
                <select class="field" id="status" name="status">
                    @foreach (['pending', 'approved', 'paid', 'rejected'] as $status)
                        <option value="{{ $status }}" @selected($payout->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="reference">Payment reference</label>
                <input class="field" id="reference" name="reference" value="{{ $payout->reference }}">
            </div>
            <div>
                <label class="label" for="admin_note">Note for the user</label>
                <textarea class="field" id="admin_note" name="admin_note" rows="3">{{ $payout->admin_note }}</textarea>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Save the decision</button>
            <p class="hint">Rejecting a request returns the amount to the member balance.</p>
        </form>
    </div>
</div>

@endsection
'''

FILES['settings/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Settings')
@section('heading', 'Settings')
@section('subheading', 'Keys, models, currency and indexing')

@section('content')

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf

    @foreach ($groups as $group => $label)
        <div class="panel mb-4">
            <h2 class="text-lg mb-3">{{ $label }}</h2>

            <div class="form-grid">
                @foreach ($settings as $key => $setting)
                    @if (($setting['meta']['group'] ?? '') !== $group)
                        @continue
                    @endif
                    <div @if (($setting['meta']['type'] ?? '') === 'textarea') class="col-span-2" @endif>
                        <label class="label">{{ $setting['meta']['label'] }}</label>
                        @if (($setting['meta']['type'] ?? '') === 'textarea')
                            <textarea class="field mono" name="settings[{{ $key }}]" rows="6" style="font-size:.82rem;">{{ $setting['value'] }}</textarea>
                        @elseif (($setting['meta']['type'] ?? '') === 'secret')
                            <input class="field mono" name="settings[{{ $key }}]" type="password"
                                   value="" placeholder="{{ filled($setting['value']) ? 'Stored. Type to replace.' : 'Not set' }}">
                        @else
                            <input class="field" name="settings[{{ $key }}]"
                                   type="{{ ($setting['meta']['type'] ?? '') === 'number' ? 'number' : 'text' }}"
                                   step="any" value="{{ $setting['value'] }}">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <button class="btn btn-primary" type="submit">Save the settings</button>
</form>

<div class="panel mt-4">
    <h2 class="text-lg mb-3">Store a raw value</h2>
    <p class="muted text-sm mb-3">
        Use this for credentials that do not have a field above, such as a gateway key or a
        webhook secret. The value is encrypted before it is written.
    </p>
    <form method="POST" action="{{ route('admin.settings.secret') }}" class="form-grid">
        @csrf
        <div>
            <label class="label" for="key">Key</label>
            <input class="field mono" id="key" name="key" placeholder="gateway.stripe.webhook_secret" required>
        </div>
        <div>
            <label class="label" for="value">Value</label>
            <input class="field mono" id="value" name="value" type="password">
        </div>
        <div class="col-span-2">
            <button class="btn btn-ghost" type="submit">Store the value</button>
        </div>
    </form>
</div>

@endsection
'''

FILES['prompts/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Prompts')
@section('heading', 'Prompts')
@section('subheading', 'The instructions sent to the model')

@section('content')

<div class="flash">
    These prompts are the behaviour of the product. Edit them carefully, and reset any prompt
    back to the built in version if a change does not work.
</div>

<form method="POST" action="{{ route('admin.prompts.update') }}" class="space-y-4">
    @csrf

    @foreach ($prompts as $key => $prompt)
        <div class="panel">
            <div class="panel-head">
                <h2 class="text-lg">{{ $prompt['label'] }}</h2>
                <span class="chip mono">{{ $key }}</span>
            </div>

            <textarea class="field mono" name="prompts[{{ $key }}]" rows="14" style="font-size:.82rem;line-height:1.6;">{{ $prompt['value'] }}</textarea>

            <p class="hint mt-2">
                @if (filled($prompt['value']))
                    A custom version is stored. The built in version is used while this box is empty.
                @else
                    Using the built in version.
                @endif
            </p>
        </div>
    @endforeach

    <button class="btn btn-primary" type="submit">Save the prompts</button>
</form>

<div class="panel mt-4">
    <h2 class="text-lg mb-3">Reset a prompt</h2>
    <form method="POST" action="{{ route('admin.prompts.reset') }}" class="flex gap-2 items-end">
        @csrf
        <div style="flex:1;">
            <label class="label" for="reset-key">Prompt</label>
            <select class="field" id="reset-key" name="key">
                @foreach ($prompts as $key => $prompt)
                    <option value="{{ $key }}">{{ $prompt['label'] }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-ghost" type="submit">Reset to the built in version</button>
    </form>
</div>

@endsection
'''

FILES['logs/index.blade.php'] = r'''
@extends('layouts.admin')

@section('title', 'Audit log')
@section('heading', 'Audit log')
@section('subheading', 'A record of privileged actions')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem;align-items:end;">
        <div>
            <label class="label" for="search">Search</label>
            <input class="field" id="search" name="search" value="{{ request('search') }}" placeholder="Action or email">
        </div>
        <div></div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table-app">
            <thead><tr><th>Action</th><th>User</th><th>IP</th><th>Details</th><th>When</th></tr></thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td class="text-sm font-semibold">{{ $log->action }}</td>
                        <td class="text-sm muted">{{ $log->user?->email ?? 'system' }}</td>
                        <td class="mono text-xs muted">{{ $log->ip ?? 'unknown' }}</td>
                        <td class="text-xs muted truncate" style="max-width:260px;">{{ $log->meta ? json_encode($log->meta) : '' }}</td>
                        <td class="text-sm muted">{{ $log->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>

@endsection
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} admin views")

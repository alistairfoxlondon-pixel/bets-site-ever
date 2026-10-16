
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

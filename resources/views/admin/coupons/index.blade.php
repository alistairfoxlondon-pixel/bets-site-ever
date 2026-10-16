
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

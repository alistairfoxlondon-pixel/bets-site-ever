
@extends('layouts.admin')

@section('title', 'Invoices')
@section('heading', 'Invoices')
@section('subheading', 'Every order raised in the platform')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid filters">
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

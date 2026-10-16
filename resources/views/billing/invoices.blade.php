
@extends('layouts.app')

@section('title', 'Invoices')
@section('heading', 'Invoices')
@section('subheading', 'Every payment you have made and every one that is waiting')

@section('content')

<div class="panel">
    @if ($invoices->isEmpty())
        <p class="muted">No invoices yet.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td class="mono text-sm">{{ $invoice->number }}</td>
                            <td class="text-sm muted">{{ $invoice->description }}</td>
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
                                <a href="{{ route('billing.invoice', $invoice) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $invoices->links() }}</div>
    @endif
</div>

@endsection

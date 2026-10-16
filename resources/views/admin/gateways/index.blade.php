
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
                <img src="{{ $gateway->logoUrl() }}" alt="{{ $gateway->name }}" height="24"
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

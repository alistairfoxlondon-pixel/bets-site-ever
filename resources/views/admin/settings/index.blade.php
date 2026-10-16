
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
                        @if (($setting['meta']['type'] ?? '') === 'secret_textarea')
                            <textarea class="field mono" name="settings[{{ $key }}]" rows="6" style="font-size:.82rem;"
                                      placeholder="{{ filled($setting['value']) ? 'Stored securely. Leave empty to keep it.' : 'Not set' }}"></textarea>
                        @elseif (($setting['meta']['type'] ?? '') === 'textarea')
                            <textarea class="field mono" name="settings[{{ $key }}]" rows="6" style="font-size:.82rem;">{{ $setting['value'] }}</textarea>
                        @elseif (($setting['meta']['type'] ?? '') === 'secret')
                            <input class="field mono" name="settings[{{ $key }}]" type="password"
                                   value="" placeholder="{{ filled($setting['value']) ? 'Stored securely. Type to replace.' : 'Not set' }}">
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

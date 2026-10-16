
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

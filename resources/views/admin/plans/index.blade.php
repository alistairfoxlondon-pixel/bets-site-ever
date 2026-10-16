
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

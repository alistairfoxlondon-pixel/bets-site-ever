
@extends('layouts.marketing')

@section('title', 'Publisher-site audit | '.config('app.name'))
@section('description', 'Review sampled public signals for Google AdSense, Google Ad Manager, Ezoic, Mediavine and Raptive with an evidence-linked report and practical next steps.')

@section('content')

<section class="band band-tight">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">Publisher-site audit</p>
                <h1 class="mb-4">Review the signals your public site shows</h1>
                <p class="lede">
                    Enter a domain. We crawl a limited set of public pages and return an internal
                    readiness index with evidence-linked findings and practical improvements. It is
                    not a prediction of network eligibility or approval.
                </p>

                <form method="GET" action="{{ auth()->check() ? route('audits.create') : route('register') }}" class="card mt-7" style="padding:1.3rem;">
                    <label class="label" for="url">Site address</label>
                    <div class="flex flex-wrap gap-2">
                        <input class="field" id="url" name="url" type="text" inputmode="url" placeholder="example.com" value="{{ $url }}" style="flex:1 1 240px;">
                        <button class="btn btn-primary" type="submit">
                            {{ auth()->check() ? 'Start audit' : 'Create free account' }}
                        </button>
                    </div>
                    <p class="hint mt-2">We read public pages only. Nothing is changed on the site you enter.</p>
                </form>
            </div>

            <div>
                <div class="visual-frame">
                    @include('partials.visuals.audit-console')
                </div>
            </div>
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <h2 class="mb-2">Choose how deep to go</h2>
        <p class="muted mb-7">Every mode runs the full rule engine. The difference is how many pages are crawled and which model reviews the evidence.</p>

        <div class="grid grid-3" style="gap:1.25rem;align-items:start;">
            @foreach ($modes as $mode)
                <div class="card" style="padding:1.5rem;@if ($mode['featured']) border-color:var(--color-brand); @endif">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h3>{{ $mode['label'] }}</h3>
                        @if ($mode['featured'])
                            <span class="badge badge-info">Recommended</span>
                        @endif
                    </div>
                    <p class="muted text-sm mb-4">{{ $mode['blurb'] }}</p>
                    <ul class="space-y-1.5" style="font-size:.9rem;">
                        <li class="flex gap-2">
                            @include('partials.icons', ['name' => 'layers', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                            <span>Up to {{ $mode['pages'] }} pages crawled</span>
                        </li>
                        <li class="flex gap-2">
                            @include('partials.icons', ['name' => 'wallet', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                            <span>{{ $mode['credits'] }} credit{{ $mode['credits'] === 1 ? '' : 's' }} per audit</span>
                        </li>
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="band">
    <div class="wrap">
        <div class="text-center" style="max-width:640px;margin-inline:auto;">
            <p class="eyebrow mb-3">Five networks, one crawl</p>
            <h2 class="mb-3">Compare sampled public signals by network</h2>
            <p class="muted mb-8">Network-specific views group a few visible site signals. Current program terms, traffic and account eligibility are not verified by this crawl.</p>
        </div>

        <div class="visual-frame mx-auto">
            @include('partials.visuals.network-map', ['class' => 'mx-auto'])
        </div>

        <div class="grid grid-3 mt-8" style="gap:1.25rem;">
            @foreach ($networks as $key => $network)
                <div class="card" style="padding:1.4rem;">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <img src="/brand/{{ $key === 'adx' ? 'adx' : $key }}.svg" alt="{{ $network['label'] }}" height="22">
                    </div>
                    <ul class="space-y-1.5" style="font-size:.88rem;">
                        @foreach (array_slice($network['requirements'], 0, 3) as $requirement)
                            <li class="flex gap-2">
                                <span class="faint">&#183;</span>
                                <span class="muted">{{ $requirement }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection

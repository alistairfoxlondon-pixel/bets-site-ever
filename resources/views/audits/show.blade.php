
@extends('layouts.app')

@section('title', $audit->domain.' audit')
@section('heading', $audit->domain ?? 'Audit')
@section('subheading', $audit->url)

@section('actions')
    <a href="{{ route('audits.report', $audit) }}" class="btn btn-ghost btn-sm">
        @include('partials.icons', ['name' => 'download', 'class' => 'w-4 h-4']) PDF report
    </a>
    <form method="POST" action="{{ route('audits.rerun', $audit) }}">
        @csrf
        <button class="btn btn-primary btn-sm" type="submit">Run again</button>
    </form>
@endsection

@section('content')

@if ($audit->status === 'failed')
    <div class="panel">
        <h2 class="text-lg mb-2">The audit could not finish</h2>
        <p class="muted mb-4">{{ $audit->error_message ?: 'The site did not answer in time.' }}</p>
        <form method="POST" action="{{ route('audits.rerun', $audit) }}">
            @csrf
            <button class="btn btn-primary" type="submit">Try again</button>
        </form>
    </div>
@elseif ($audit->status !== 'completed')
    <div class="panel text-center py-10">
        <p class="muted mb-4">The audit is running. This page updates in a moment.</p>
        <a href="{{ route('audits.show', $audit) }}" class="btn btn-ghost btn-sm">Refresh</a>
    </div>
@else
    @if (data_get($audit->meta, 'sample_data'))
        <div class="panel mb-4" role="note">
            <strong>Demonstration report.</strong> This seeded example uses illustrative data and does not represent a real site audit.
        </div>
    @endif

    <div class="grid grid-2" style="gap:1.25rem;align-items:start;">
        <div class="panel">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <p class="label">Public-site readiness index</p>
                    <p class="display" style="font-size:3.2rem;line-height:1;color:var(--color-brand);">
                        {{ $audit->score }}<span class="muted" style="font-size:1.4rem;">/100</span>
                    </p>
                    <p class="muted text-sm mt-1">Heuristic based on sampled public pages, not an approval prediction.</p>
                </div>
                <div class="text-right">
                    <span class="badge {{ $audit->score >= 78 ? 'badge-pass' : ($audit->score >= 55 ? 'badge-warn' : 'badge-fail') }}">{{ $audit->verdictLabel() }}</span>
                    <p class="faint text-xs mt-2">
                        {{ ucfirst($audit->mode) }} audit<br>
                        {{ $audit->pages_crawled }} pages &middot; {{ $audit->findings_count }} findings
                    </p>
                </div>
            </div>

            <div class="meter mt-4" style="height:9px;">
                <span style="width:{{ max(2, min(100, (int) $audit->score)) }}%;"></span>
            </div>

            @if ($summary)
                <p class="muted mt-4" style="font-size:.95rem;">{{ $summary }}</p>
            @endif

            <div class="flex flex-wrap gap-2 mt-5">
                <a href="{{ route('audits.report', $audit) }}" class="btn btn-primary btn-sm">
                    @include('partials.icons', ['name' => 'download', 'class' => 'w-4 h-4']) Download the report
                </a>
                <a href="{{ route('audits.preview', $audit) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Preview</a>
                @if ($previous)
                    <a href="{{ route('audits.compare', ['left' => $previous->id, 'right' => $audit->id]) }}" class="btn btn-ghost btn-sm">Compare with the last run</a>
                @endif
            </div>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Readiness by area</h2>
            <div class="space-y-3">
                @foreach ($categories as $category)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span>{{ $category['label'] }}</span>
                            <span class="mono muted">{{ $category['score'] === null ? 'Not assessed' : (int) round($category['score']) }}</span>
                        </div>
                        @if ($category['score'] !== null)
                            <div class="meter">
                                <span style="width:{{ max(2, min(100, (int) round($category['score']))) }}%;"></span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-head">
            <h2 class="text-lg">Network-specific public signals</h2>
            <p class="muted text-sm mt-1">These product heuristics inspect only sampled pages. They do not confirm traffic, account status, eligibility, or current partner requirements.</p>
        </div>

        <div class="grid grid-2" style="gap:.9rem;">
            @foreach (config('adready.networks') as $key => $network)
                @php $entry = $networks[$key] ?? null; @endphp
                <div class="card-flat" style="padding:.9rem 1rem;">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <img src="/brand/{{ $key }}.svg" alt="{{ $network['label'] }}" height="20" style="height:20px;width:auto;max-width:140px;">
                        <span class="badge {{ ($entry['ready'] ?? false) ? 'badge-pass' : 'badge-warn' }}">
                            {{ ($entry['ready'] ?? false) ? 'No sampled gaps' : 'Review suggested' }}
                        </span>
                    </div>
                    <div class="meter mt-3">
                        <span style="width:{{ max(2, (int) ($entry['score'] ?? 0)) }}%;"></span>
                    </div>
                    @if (! empty($entry['notes']))
                        <ul class="mt-3 space-y-1" style="font-size:.85rem;">
                            @foreach (array_slice($entry['notes'], 0, 3) as $note)
                                <li class="muted">{{ is_array($note) ? ($note['text'] ?? '') : $note }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if ($critical->isNotEmpty())
        <div class="panel mt-4">
            <div class="panel-head">
                <h2 class="text-lg">Highest-priority findings</h2>
                <span class="badge badge-fail">{{ $critical->count() }} flagged</span>
            </div>
            <div class="space-y-3">
                @foreach ($critical as $finding)
                    <div class="finding finding-critical">
                        <h4>{{ $finding->title }}</h4>
                        @if ($finding->detail)
                            <p>{{ $finding->detail }}</p>
                        @endif
                        @if ($finding->recommendation)
                            <div class="fix">{{ $finding->recommendation }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($warnings->isNotEmpty())
        <div class="panel mt-4">
            <div class="panel-head">
                <h2 class="text-lg">What needs work</h2>
                <span class="badge badge-warn">{{ $warnings->count() }} warnings</span>
            </div>
            <div class="space-y-3">
                @foreach ($warnings as $finding)
                    <div class="finding finding-warning">
                        <h4>{{ $finding->title }}</h4>
                        @if ($finding->detail)
                            <p>{{ $finding->detail }}</p>
                        @endif
                        @if ($finding->recommendation)
                            <div class="fix">{{ $finding->recommendation }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($passes->isNotEmpty())
        <div class="panel mt-4">
            <div class="panel-head">
                <h2 class="text-lg">What is already working</h2>
                <span class="badge badge-pass">{{ $passes->count() }} confirmed</span>
            </div>
            <div class="space-y-2">
                @foreach ($passes as $finding)
                    <div class="finding finding-pass">
                        <h4>{{ $finding->title }}</h4>
                        @if ($finding->detail)
                            <p>{{ $finding->detail }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (! empty($analysis))
        <div class="panel mt-4">
            <div class="panel-head"><h2 class="text-lg">Detailed analysis</h2></div>
            <div class="prose-app" style="font-size:.97rem;">
                @foreach ([
                    'website_quality' => 'Website quality',
                    'content_quality' => 'Content quality',
                    'traffic_quality' => 'Traffic quality',
                    'policy_risks' => 'Policy risks',
                    'trust_eeat' => 'Trust and E-E-A-T',
                    'seo_indexing' => 'SEO and indexing',
                ] as $key => $label)
                    @if (filled($analysis[$key] ?? null))
                        <h3>{{ $label }}</h3>
                        <p>{{ $analysis[$key] }}</p>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if (! empty($roadmap))
        <div class="panel mt-4">
            <div class="panel-head"><h2 class="text-lg">First attempt roadmap</h2></div>
            <div class="space-y-3">
                @foreach ($roadmap as $index => $step)
                    @php
                        $title = is_array($step) ? ($step['title'] ?? $step['step'] ?? 'Step '.($index + 1)) : 'Step '.($index + 1);
                        $detail = is_array($step) ? ($step['description'] ?? $step['detail'] ?? $step['action'] ?? '') : $step;
                    @endphp
                    <div class="flex gap-3">
                        <span class="step-number shrink-0">{{ $index + 1 }}</span>
                        <div>
                            <strong class="block">{{ $title }}</strong>
                            <span class="muted text-sm">{{ $detail }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($strengths || $risks || $criticalFixes)
        <div class="grid grid-3 mt-4" style="gap:1.25rem;align-items:start;">
            @if ($strengths)
                <div class="panel">
                    <h3 class="text-lg mb-2">Strengths</h3>
                    <ul class="space-y-2" style="font-size:.92rem;">
                        @foreach ($strengths as $strength)
                            <li class="flex gap-2">
                                @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                                <span>{{ is_array($strength) ? ($strength['text'] ?? $strength['title'] ?? '') : $strength }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($risks)
                <div class="panel">
                    <h3 class="text-lg mb-2">Risks</h3>
                    <ul class="space-y-2" style="font-size:.92rem;">
                        @foreach ($risks as $risk)
                            <li class="flex gap-2">
                                @include('partials.icons', ['name' => 'cross', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                                <span>{{ is_array($risk) ? ($risk['text'] ?? $risk['title'] ?? '') : $risk }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($criticalFixes)
                <div class="panel">
                    <h3 class="text-lg mb-2">Priority fixes from model review</h3>
                    <ul class="space-y-2" style="font-size:.92rem;">
                        @foreach ($criticalFixes as $fix)
                            <li class="flex gap-2">
                                <span class="faint mt-1">&#183;</span>
                                <span>{{ is_array($fix) ? ($fix['title'] ?? $fix['description'] ?? '') : $fix }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
@endif

@endsection

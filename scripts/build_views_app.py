#!/usr/bin/env python3
"""Write the signed in product views."""
import os

ROOT = "/home/user/bets-site-ever/resources/views"
FILES = {}

FILES['dashboard.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Overview')

@section('content')

<div class="grid grid-4" style="gap:1rem;">
    <div class="stat">
        <div class="k">Audits</div>
        <div class="v">{{ $stats['audits'] }}</div>
        <div class="s">{{ $stats['completed'] }} completed</div>
    </div>
    <div class="stat">
        <div class="k">Articles</div>
        <div class="v">{{ $stats['documents'] }}</div>
        <div class="s">{{ number_format($stats['words']) }} words written</div>
    </div>
    <div class="stat">
        <div class="k">Credits</div>
        <div class="v">{{ number_format((float) auth()->user()->credits_balance, 0) }}</div>
        <div class="s">ready to spend</div>
    </div>
    <div class="stat">
        <div class="k">Best score</div>
        <div class="v">{{ $ready?->probability ?? 0 }}<span class="muted" style="font-size:1rem;">%</span></div>
        <div class="s">{{ $ready?->domain ?? 'no audit yet' }}</div>
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Run a new audit</h2>
            <a href="{{ route('audits.create') }}" class="btn btn-ghost btn-sm">More options</a>
        </div>

        <form method="POST" action="{{ route('audits.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="label" for="url">Site address</label>
                <input class="field" id="url" name="url" placeholder="example.com" required>
            </div>

            <div>
                <label class="label">Audit depth</label>
                <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));gap:.6rem;">
                    @foreach ($modes as $mode)
                        <label class="card-flat" style="padding:.7rem;cursor:pointer;display:block;">
                            <span class="flex items-center gap-2">
                                <input type="radio" name="mode" value="{{ $mode['key'] }}" @checked($mode['featured'])>
                                <strong class="text-sm">{{ $mode['label'] }}</strong>
                            </span>
                            <span class="faint text-xs mono block mt-1">{{ $mode['credits'] }} credit{{ $mode['credits'] === 1 ? '' : 's' }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Start the audit</button>
        </form>
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Recent audits</h2>
            <a href="{{ route('audits.index') }}" class="btn btn-quiet btn-sm">See all</a>
        </div>

        @if ($audits->isEmpty())
            <p class="muted text-sm">No audits yet. Run your first one on the left.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Score</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($audits as $audit)
                            <tr>
                                <td>
                                    <a href="{{ route('audits.show', $audit) }}" class="font-semibold">{{ $audit->domain }}</a>
                                    <div class="faint text-xs">{{ $audit->completed_at?->diffForHumans() ?? 'running' }}</div>
                                </td>
                                <td class="num">
                                    @if ($audit->status === 'completed')
                                        {{ $audit->probability }}%
                                    @else
                                        <span class="faint">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($audit->status === 'completed')
                                        <span class="badge {{ $audit->probability >= 78 ? 'badge-pass' : ($audit->probability >= 55 ? 'badge-warn' : 'badge-fail') }}">{{ $audit->verdictLabel() }}</span>
                                    @elseif ($audit->status === 'failed')
                                        <span class="badge badge-fail">Failed</span>
                                    @else
                                        <span class="badge badge-info">Running</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Latest articles</h2>
            <a href="{{ route('documents.create') }}" class="btn btn-ghost btn-sm">Write one</a>
        </div>

        @if ($documents->isEmpty())
            <p class="muted text-sm">Nothing written yet.</p>
        @else
            <ul class="space-y-3">
                @foreach ($documents as $document)
                    <li class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('documents.show', $document) }}" class="font-semibold">{{ $document->title }}</a>
                            <div class="faint text-xs">
                                {{ $document->isAffiliate() ? 'Affiliate' : 'AdSense' }} &middot; {{ number_format($document->word_count) }} words
                            </div>
                        </div>
                        <span class="chip shrink-0">{{ $document->readingMinutes }} min</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Your plan</h2>
            <a href="{{ route('billing.index') }}" class="btn btn-quiet btn-sm">Manage</a>
        </div>

        @if ($subscription)
            <div class="flex items-baseline justify-between mb-2">
                <strong>{{ $subscription->plan?->name ?? 'Subscription' }}</strong>
                <span class="badge {{ $subscription->isOnTrial() ? 'badge-warn' : 'badge-pass' }}">{{ ucfirst($subscription->status) }}</span>
            </div>
            <p class="muted text-sm mb-3">
                @if ($subscription->renews_at)
                    Renews {{ $subscription->renews_at->format('j M Y') }}
                @else
                    No renewal date set
                @endif
            </p>
        @else
            <p class="muted text-sm mb-3">You are on the free tier. Credits are added when you sign up and can be topped up at any time.</p>
        @endif

        <a href="{{ route('billing.index') }}" class="btn btn-ghost btn-sm btn-block">View plans and credits</a>
    </div>
</div>

@endsection
'''

FILES['audits/index.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Audits')
@section('heading', 'Audits')
@section('subheading', 'Every site you have checked, newest first')

@section('actions')
    <a href="{{ route('audits.compare') }}" class="btn btn-ghost btn-sm">Compare runs</a>
@endsection

@section('content')

<div class="panel">
    @if ($audits->isEmpty())
        <div class="text-center py-10">
            <span class="icon-box" style="margin:0 auto 1rem;">
                @include('partials.icons', ['name' => 'shield', 'class' => 'w-5 h-5'])
            </span>
            <h2 class="text-lg mb-2">No audits yet</h2>
            <p class="muted text-sm mb-5">Run your first audit and the full report lands here.</p>
            <a href="{{ route('audits.create') }}" class="btn btn-primary">Start an audit</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>Mode</th>
                        <th>Probability</th>
                        <th>Verdict</th>
                        <th>Pages</th>
                        <th>Finished</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($audits as $audit)
                        <tr>
                            <td>
                                <a href="{{ route('audits.show', $audit) }}" class="font-semibold">{{ $audit->domain }}</a>
                                <div class="faint text-xs truncate" style="max-width:240px;">{{ $audit->url }}</div>
                            </td>
                            <td class="text-sm muted">{{ ucfirst($audit->mode) }}</td>
                            <td class="num">
                                @if ($audit->status === 'completed')
                                    <strong>{{ $audit->probability }}%</strong>
                                @else
                                    <span class="faint">--</span>
                                @endif
                            </td>
                            <td>
                                @if ($audit->status === 'completed')
                                    <span class="badge {{ $audit->probability >= 78 ? 'badge-pass' : ($audit->probability >= 55 ? 'badge-warn' : 'badge-fail') }}">{{ $audit->verdictLabel() }}</span>
                                @elseif ($audit->status === 'failed')
                                    <span class="badge badge-fail">Failed</span>
                                @else
                                    <span class="badge badge-info">Running</span>
                                @endif
                            </td>
                            <td class="num muted">{{ $audit->pages_crawled ?: 0 }}</td>
                            <td class="text-sm muted">{{ $audit->completed_at?->diffForHumans() ?? 'in progress' }}</td>
                            <td class="text-right">
                                <a href="{{ route('audits.show', $audit) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $audits->links() }}
        </div>
    @endif
</div>

@endsection
'''

FILES['audits/create.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'New audit')
@section('heading', 'New audit')
@section('subheading', 'We read public pages only, and never change anything on the site')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <form method="POST" action="{{ route('audits.store') }}" class="space-y-5">
            @csrf

            <div>
                <label class="label" for="url">Site address</label>
                <input class="field" id="url" name="url" value="{{ old('url', $url) }}" placeholder="example.com" required autofocus>
                <p class="hint mt-1">Include https:// if you want a specific scheme. We follow one redirect at most.</p>
            </div>

            <div>
                <label class="label">Audit depth</label>
                <div class="space-y-2">
                    @foreach ($modes as $mode)
                        <label class="card-flat" style="padding:.9rem 1rem;cursor:pointer;display:block;{{ $mode['featured'] ? 'border-color:var(--color-brand);' : '' }}">
                            <span class="flex items-start gap-3">
                                <input type="radio" name="mode" value="{{ $mode['key'] }}" class="mt-1" @checked(old('mode', $mode['featured'] ? 'standard' : 'normal') === $mode['key'])>
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2 flex-wrap">
                                        <strong>{{ $mode['label'] }}</strong>
                                        @if ($mode['featured'])
                                            <span class="badge badge-info">Recommended</span>
                                        @endif
                                        <span class="chip">{{ $mode['credits'] }} credit{{ $mode['credits'] === 1 ? '' : 's' }}</span>
                                    </span>
                                    <span class="muted text-sm block mt-1">{{ $mode['blurb'] }}</span>
                                    <span class="faint text-xs block mt-1">Up to {{ $mode['pages'] }} pages crawled</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button class="btn btn-primary btn-lg btn-block" type="submit">
                Run the audit
            </button>

            <p class="hint text-center">
                A Standard audit usually takes under a minute. The page will keep you posted.
            </p>
        </form>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">What we check</h2>
        <ul class="space-y-3" style="font-size:.93rem;">
            @foreach ([
                ['Content quality', 'Depth, originality, structure and whether each post earns its place.'],
                ['Policy and compliance', 'Thin pages, copied material, prohibited topics and unsafe links.'],
                ['Trust and E-E-A-T', 'Author identity, contact routes, about page, privacy terms.'],
                ['SEO and indexing', 'Crawlability, robots rules, sitemap health, speed and structured data.'],
                ['Technical and UX', 'Mobile layout, navigation depth, broken links and ad readiness.'],
                ['Traffic quality', 'Engagement signals visible to the crawler and the health of the site.'],
            ] as $item)
                <li class="flex gap-3">
                    @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                    <span>
                        <strong class="block">{{ $item[0] }}</strong>
                        <span class="muted text-sm">{{ $item[1] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>

        <div class="rule my-4"></div>

        <p class="text-sm muted">
            Balance after this audit: <strong class="mono">{{ number_format((float) auth()->user()->credits_balance, 0) }}</strong> credits.
            <a href="{{ route('billing.index') }}" class="underline">Add credits</a>
        </p>
    </div>
</div>

@endsection
'''

FILES['audits/show.blade.php'] = r'''
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
    <div class="grid grid-2" style="gap:1.25rem;align-items:start;">
        <div class="panel">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <p class="label">Approval probability</p>
                    <p class="display" style="font-size:3.2rem;line-height:1;color:var(--color-brand);">
                        {{ $audit->probability }}<span class="muted" style="font-size:1.4rem;">%</span>
                    </p>
                    <p class="muted text-sm mt-1">Readiness score {{ $audit->score }} out of 100</p>
                </div>
                <div class="text-right">
                    <span class="badge {{ $audit->probability >= 78 ? 'badge-pass' : ($audit->probability >= 55 ? 'badge-warn' : 'badge-fail') }}">{{ $audit->verdictLabel() }}</span>
                    <p class="faint text-xs mt-2">
                        {{ ucfirst($audit->mode) }} audit<br>
                        {{ $audit->pages_crawled }} pages &middot; {{ $audit->findings_count }} findings
                    </p>
                </div>
            </div>

            <div class="meter mt-4" style="height:9px;">
                <span style="width:{{ max(2, min(100, (int) $audit->probability)) }}%;"></span>
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
                            <span class="mono muted">{{ (int) round($category['score']) }}</span>
                        </div>
                        <div class="meter">
                            <span style="width:{{ max(2, min(100, (int) round($category['score]))) }}%;"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-head">
            <h2 class="text-lg">Network readiness</h2>
        </div>

        <div class="grid grid-2" style="gap:.9rem;">
            @foreach (config('adready.networks') as $key => $network)
                @php $entry = $networks[$key] ?? null; @endphp
                <div class="card-flat" style="padding:.9rem 1rem;">
                    <div class="flex items-center justify-between gap-3">
                        <img src="{{ asset('brand/'.$key.'.svg') }}" alt="{{ $network['label'] }}" height="20">
                        <span class="badge {{ ($entry['ready'] ?? false) ? 'badge-pass' : 'badge-warn' }}">
                            {{ ($entry['ready'] ?? false) ? 'Ready' : 'Not ready' }}
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
                <h2 class="text-lg">Blockers to fix first</h2>
                <span class="badge badge-fail">{{ $critical->count() }} blocking</span>
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
                    <h3 class="text-lg mb-2">Critical fix list</h3>
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
'''

FILES['audits/compare.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Compare audits')
@section('heading', 'Compare runs')
@section('subheading', 'See what changed between two audits of the same site')

@section('content')

@if (! $left || ! $right)
    <div class="panel text-center py-10">
        <p class="muted mb-4">You need two completed audits to compare.</p>
        <a href="{{ route('audits.index') }}" class="btn btn-ghost btn-sm">Back to audits</a>
    </div>
@else
    <div class="panel mb-4">
        <form method="GET" class="grid grid-2" style="gap:1rem;">
            <div>
                <label class="label" for="left">Earlier run</label>
                <select class="field" id="left" name="left">
                    @foreach ($audits as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $left->id)>
                            {{ $option->domain }} &middot; {{ $option->completed_at?->format('j M Y, H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="right">Later run</label>
                <select class="field" id="right" name="right">
                    @foreach ($audits as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $right->id)>
                            {{ $option->domain }} &middot; {{ $option->completed_at?->format('j M Y, H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <button class="btn btn-primary btn-sm" type="submit">Compare</button>
            </div>
        </form>
    </div>

    <div class="grid grid-2" style="gap:1.25rem;align-items:start;">
        <div class="panel">
            <p class="label">{{ $left->completed_at?->format('j M Y') }}</p>
            <p class="display" style="font-size:2.6rem;color:var(--color-ink-soft);">{{ $left->probability }}%</p>
            <p class="muted text-sm mb-4">{{ $left->verdictLabel() }}</p>
            <div class="space-y-3">
                @foreach ($leftCategories as $key => $category)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span>{{ $category['label'] }}</span>
                            <span class="mono muted">{{ (int) round($category['score']) }}</span>
                        </div>
                        <div class="meter"><span style="width:{{ max(2, min(100, (int) round($category['score']))) }}%;"></span></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <p class="label">{{ $right->completed_at?->format('j M Y') }}</p>
            <p class="display" style="font-size:2.6rem;color:var(--color-brand);">{{ $right->probability }}%</p>
            <p class="muted text-sm mb-4">{{ $right->verdictLabel() }}</p>
            <div class="space-y-3">
                @foreach ($rightCategories as $key => $category)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span>{{ $category['label'] }}</span>
                            <span class="mono muted">{{ (int) round($category['score']) }}</span>
                        </div>
                        <div class="meter"><span style="width:{{ max(2, min(100, (int) round($category['score']))) }}%;"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-head">
            <h2 class="text-lg">Movement</h2>
            <div class="flex gap-2">
                <a href="{{ route('audits.report', $left) }}" class="btn btn-ghost btn-sm">Earlier PDF</a>
                <a href="{{ route('audits.report', $right) }}" class="btn btn-ghost btn-sm">Later PDF</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Earlier</th>
                        <th>Later</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rightCategories as $key => $category)
                        @php $before = (int) round($leftCategories[$key]['score'] ?? 0); @endphp
                        <tr>
                            <td>{{ $category['label'] }}</td>
                            <td class="num muted">{{ $before }}</td>
                            <td class="num"><strong>{{ (int) round($category['score']) }}</strong></td>
                            <td class="num">
                                @php $change = (int) round($category['score']) - $before; @endphp
                                @if ($change > 0)
                                    <span class="badge badge-pass">+{{ $change }}</span>
                                @elseif ($change < 0)
                                    <span class="badge badge-fail">{{ $change }}</span>
                                @else
                                    <span class="badge badge-muted">no change</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
'''

FILES['documents/index.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Content')
@section('heading', 'Content')
@section('subheading', 'Every article the writer has produced for you')

@section('actions')
    <a href="{{ route('documents.create', ['type' => 'affiliate_article']) }}" class="btn btn-ghost btn-sm">Affiliate article</a>
    <a href="{{ route('documents.create', ['type' => 'adsense_article']) }}" class="btn btn-primary btn-sm">New article</a>
@endsection

@section('content')

<div class="panel">
    @if ($documents->isEmpty())
        <div class="text-center py-10">
            <span class="icon-box" style="margin:0 auto 1rem;">
                @include('partials.icons', ['name' => 'pen', 'class' => 'w-5 h-5'])
            </span>
            <h2 class="text-lg mb-2">No articles yet</h2>
            <p class="muted text-sm mb-5">Give the writer a keyword and it will produce a publish ready piece.</p>
            <a href="{{ route('documents.create') }}" class="btn btn-primary">Write an article</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Words</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $document)
                        <tr>
                            <td>
                                <a href="{{ route('documents.show', $document) }}" class="font-semibold">{{ $document->title }}</a>
                                <div class="faint text-xs truncate" style="max-width:320px;">{{ $document->keyword }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $document->isAffiliate() ? 'badge-warn' : 'badge-info' }}">
                                    {{ $document->isAffiliate() ? 'Affiliate' : 'AdSense' }}
                                </span>
                            </td>
                            <td class="num muted">{{ number_format($document->word_count) }}</td>
                            <td class="text-sm muted">{{ $document->created_at->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('documents.show', $document) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $documents->links() }}
        </div>
    @endif
</div>

@endsection
'''

FILES['documents/create.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Write an article')
@section('heading', 'Write an article')
@section('subheading', $type === 'affiliate_article' ? 'Affiliate article with a disclosure in the first paragraph' : 'AdSense ready informational article')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <form method="POST" action="{{ route('documents.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <div>
                <label class="label" for="keyword">Primary keyword</label>
                <input class="field" id="keyword" name="keyword" value="{{ old('keyword', $keyword) }}" placeholder="best hosting for a wordpress blog" required autofocus>
                <p class="hint mt-1">Write it the way a person would search for it.</p>
            </div>

            @if ($type === 'affiliate_article')
                <div>
                    <label class="label" for="product">Product or network to promote</label>
                    <input class="field" id="product" name="product" value="{{ old('product') }}" placeholder="Example: Hosting provider name">
                </div>
            @endif

            <div>
                <label class="label" for="url">Your site address</label>
                <input class="field" id="url" name="url" value="{{ old('url') }}" placeholder="example.com">
                <p class="hint mt-1">Helps the writer match the tone and the topic of your site.</p>
            </div>

            <div>
                <label class="label" for="site_context">What is your site about</label>
                <textarea class="field" id="site_context" name="site_context" rows="3" placeholder="A blog about home coffee brewing for beginners.">{{ old('site_context') }}</textarea>
            </div>

            <div class="form-grid">
                <div>
                    <label class="label" for="mode">Writer</label>
                    <select class="field" id="mode" name="mode">
                        @foreach ($modes as $mode)
                            <option value="{{ $mode['key'] }}" @selected(old('mode', 'standard') === $mode['key'])>
                                {{ $mode['label'] }} ({{ $mode['credits'] }} credits)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="language">Language</label>
                    <input class="field" id="language" name="language" value="{{ old('language', 'English') }}">
                </div>
            </div>

            <div>
                <label class="label" for="tone">Tone</label>
                <input class="field" id="tone" name="tone" value="{{ old('tone') }}" placeholder="plain, practical, slightly opinionated">
            </div>

            <div>
                <label class="label" for="extra_notes">Notes for the writer</label>
                <textarea class="field" id="extra_notes" name="extra_notes" rows="3" placeholder="Mention the migration guide, avoid pricing claims, keep the tone calm.">{{ old('extra_notes') }}</textarea>
            </div>

            <button class="btn btn-primary btn-lg btn-block" type="submit">Write the article</button>

            <p class="hint text-center">
                Balance after this: <strong class="mono">{{ number_format((float) auth()->user()->credits_balance, 0) }}</strong> credits.
            </p>
        </form>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">The house rules</h2>
        <ul class="space-y-3" style="font-size:.93rem;">
            @foreach ([
                'A real person voice, with experience in the details',
                'No dashes as punctuation, no hashtags, no emoji',
                'No invented statistics, studies or prices',
                'The primary keyword used where it belongs, never stuffed',
                'A clear disclosure in the first paragraph for affiliate pieces',
                'Ten question and answer FAQs',
                'Internal links, external references and image prompts',
                'Meta title, meta description and an excerpt',
            ] as $rule)
                <li class="flex gap-3">
                    @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                    <span>{{ $rule }}</span>
                </li>
            @endforeach
        </ul>

        <div class="rule my-4"></div>

        <p class="text-sm muted">
            Articles are generated with the model you choose in the admin panel. The current
            model is <strong class="mono">{{ \App\Models\Setting::get('ai.model_writer_standard', 'standard') }}</strong>.
        </p>
    </div>
</div>

@endsection
'''

FILES['documents/show.blade.php'] = r'''
@extends('layouts.app')

@section('title', $document->title)
@section('heading', $document->title)
@section('subheading', $document->isAffiliate() ? 'Affiliate article' : 'AdSense article')

@section('actions')
    <a href="{{ route('documents.export', [$document, 'html']) }}" class="btn btn-ghost btn-sm">Download HTML</a>
    <a href="{{ route('documents.export', [$document, 'markdown']) }}" class="btn btn-ghost btn-sm">Markdown</a>
@endsection

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Article</h2>
            <span class="chip">{{ number_format($document->word_count) }} words</span>
        </div>

        <div class="prose-app" style="max-height:60vh;overflow:auto;padding-right:.5rem;">{!! $document->body !!}</div>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Export</h2>
            <p class="muted text-sm mb-3">Take the article straight to the platform you publish on.</p>
            <div class="space-y-2">
                @foreach ([
                    'wordpress' => 'WordPress',
                    'blogger' => 'Blogger',
                    'shopify' => 'Shopify',
                    'wix' => 'Wix',
                    'html' => 'HTML',
                    'markdown' => 'Markdown',
                ] as $format => $label)
                    <a href="{{ route('documents.export', [$document, $format]) }}" class="btn btn-ghost btn-block btn-sm">
                        @include('partials.icons', ['name' => 'download', 'class' => 'w-4 h-4']) {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Search details</h2>
            <dl class="space-y-3" style="font-size:.9rem;">
                <div>
                    <dt class="label">Meta title</dt>
                    <dd>{{ $document->meta_title }}</dd>
                </div>
                <div>
                    <dt class="label">Meta description</dt>
                    <dd class="muted">{{ $document->meta_description }}</dd>
                </div>
                <div>
                    <dt class="label">Primary keyword</dt>
                    <dd class="mono">{{ $document->keyword }}</dd>
                </div>
                @if (! empty($document->internal_links))
                    <div>
                        <dt class="label">Internal links</dt>
                        <dd class="muted">{{ count($document->internal_links) }} suggested</dd>
                    </div>
                @endif
                @if (! empty($document->external_links))
                    <div>
                        <dt class="label">External references</dt>
                        <dd class="muted">{{ count($document->external_links) }} suggested</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if ($faq)
            <div class="panel">
                <h2 class="text-lg mb-3">FAQ block</h2>
                <div class="space-y-2">
                    @foreach ($faq as $item)
                        <details class="qa" style="border:0;border-top:1px solid var(--color-line);padding:0;">
                            <summary class="text-sm" style="padding:.7rem 0;">
                                {{ is_array($item) ? $item['question'] : $item }}
                            </summary>
                            <div class="a" style="padding-bottom:.7rem;font-size:.88rem;">
                                {{ is_array($item) ? $item['answer'] : '' }}
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('documents.destroy', $document) }}"
              onsubmit="return confirm('Delete this article permanently?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger btn-sm btn-block" type="submit">Delete this article</button>
        </form>
    </div>
</div>

@endsection
'''

FILES['indexing/index.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Indexing')
@section('heading', 'Quick indexing')
@section('subheading', 'Push a URL to Google and to the IndexNow network')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Submit a URL</h2></div>

        <form method="POST" action="{{ route('indexing.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="url">Page address</label>
                <input class="field" id="url" name="url" type="url" placeholder="https://example.com/blog/my-article" required>
            </div>

            <div>
                <label class="label">Engines</label>
                <div class="space-y-2">
                    @foreach ($engines as $slug => $label)
                        <label class="card-flat" style="padding:.7rem .9rem;cursor:pointer;display:block;">
                            <span class="flex items-center gap-2.5">
                                <input type="checkbox" name="engines[]" value="{{ $slug }}" @checked($slug === 'indexnow')>
                                <span class="text-sm">{{ $label }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Send for indexing</button>

            <p class="hint">
                The Google Indexing API expects a service account. The administrator configures it in
                the control panel under Settings.
            </p>
        </form>
    </div>

    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Submission history</h2></div>

        @if ($submissions->isEmpty())
            <p class="muted text-sm">Nothing submitted yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Engine</th>
                            <th>Result</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr>
                                <td class="truncate" style="max-width:200px;">{{ $submission->url }}</td>
                                <td class="text-sm muted">{{ $engines[$submission->engine] ?? $submission->engine }}</td>
                                <td>
                                    <span class="badge {{ $submission->status === 'success' ? 'badge-pass' : 'badge-fail' }}">
                                        {{ $submission->status === 'success' ? 'Sent' : 'Failed' }}
                                    </span>
                                </td>
                                <td class="text-sm muted">{{ $submission->submitted_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $submissions->links() }}</div>
        @endif
    </div>
</div>

@endsection
'''

FILES['billing/index.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Billing')
@section('heading', 'Billing and plans')
@section('subheading', 'Choose a plan, top up your credits or manage what you already pay for')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Your plan</h2>
            @if ($subscription)
                <span class="badge {{ $subscription->isOnTrial() ? 'badge-warn' : 'badge-pass' }}">{{ ucfirst($subscription->status) }}</span>
            @endif
        </div>

        @if ($subscription)
            <div class="flex items-baseline justify-between mb-3">
                <div>
                    <strong class="text-lg">{{ $subscription->plan?->name ?? 'Subscription' }}</strong>
                    <p class="muted text-sm">
                        {{ ucfirst($subscription->billing_cycle) }} billing &middot;
                        {{ $subscription->currency === 'BDT' ? '৳'.number_format((float) $subscription->amount_bdt, 2) : '$'.number_format((float) $subscription->amount, 2) }}
                    </p>
                </div>
            </div>

            @if ($subscription->renews_at)
                <p class="muted text-sm mb-4">Next renewal {{ $subscription->renews_at->format('j F Y') }}.</p>
            @endif

            <div class="flex flex-wrap gap-2">
                @if ($subscription->status === 'cancelled')
                    <form method="POST" action="{{ route('billing.resume', $subscription) }}">
                        @csrf
                        <button class="btn btn-primary btn-sm" type="submit">Resume subscription</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('billing.cancel', $subscription) }}"
                          onsubmit="return confirm('Cancel the subscription at the end of the paid period?');">
                        @csrf
                        <button class="btn btn-ghost btn-sm" type="submit">Cancel subscription</button>
                    </form>
                @endif
                <a href="{{ route('billing.invoices') }}" class="btn btn-ghost btn-sm">Invoices</a>
            </div>
        @else
            <p class="muted mb-4">You are not on a paid plan. Pick one below, or keep using the free credits.</p>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Credits</h2></div>
        <p class="display" style="font-size:2.6rem;color:var(--color-brand);">{{ number_format($credits, 0) }}</p>
        <p class="muted text-sm mb-4">Available now. A Standard audit uses three credits.</p>
        <p class="text-sm muted mb-4">
            Need more? Pick a plan above, or write to us and we will add the credits to your account.
        </p>
        <a href="{{ route('contact.create', ['reason' => 'billing']) }}" class="btn btn-ghost btn-sm">Ask for more credits</a>
    </div>
</div>

<h2 class="text-xl mt-6 mb-4">Plans</h2>

<div class="grid grid-3" style="gap:1.25rem;align-items:start;">
    @foreach ($plans as $plan)
        <div class="card" style="padding:1.5rem;@if ($plan->is_featured) border-color:var(--color-brand); @endif">
            <div class="flex items-center justify-between gap-2 mb-2">
                <h3 class="text-lg">{{ $plan->name }}</h3>
                @if ($plan->is_featured)
                    <span class="badge badge-info">Popular</span>
                @endif
            </div>
            <p class="muted text-sm mb-4">{{ $plan->tagline }}</p>

            <p class="display" style="font-size:1.9rem;line-height:1;">
                ${{ number_format((float) $plan->price_monthly, 0) }}<span class="muted" style="font-size:.95rem;">/month</span>
            </p>
            <p class="faint text-xs mono mt-1">
                ৳{{ number_format((float) $plan->price_monthly_bdt, 0) }} per month &middot;
                ${{ number_format((float) $plan->price_yearly, 0) }} per year
            </p>

            <ul class="mt-4 space-y-1.5" style="font-size:.9rem;">
                @foreach (array_slice((array) $plan->features, 0, 4) as $feature)
                    <li class="flex gap-2">
                        @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 space-y-2">
                <form method="POST" action="{{ route('billing.subscribe', [$plan, 'monthly']) }}">
                    @csrf
                    <button class="btn {{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} btn-block btn-sm" type="submit">Pay monthly</button>
                </form>
                <form method="POST" action="{{ route('billing.subscribe', [$plan, 'yearly']) }}">
                    @csrf
                    <button class="btn btn-ghost btn-block btn-sm" type="submit">Pay yearly</button>
                </form>
            </div>
        </div>
    @endforeach
</div>

@if ($gateways->isNotEmpty())
    <div class="panel mt-4">
        <h2 class="text-lg mb-3">Payment methods</h2>
        <div class="logo-strip">
            @foreach ($gateways as $gateway)
                <img src="{{ asset('brand/'.($gateway->logo ?: 'bank').'.svg') }}" alt="{{ $gateway->name }}" height="24"
                     onerror="this.style.display='none'">
            @endforeach
        </div>
    </div>
@endif

@endsection
'''

FILES['billing/invoices.blade.php'] = r'''
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
'''

FILES['billing/invoice.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Invoice '.$invoice->number)
@section('heading', 'Invoice '.$invoice->number)
@section('subheading', $invoice->description)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Details</h2>
            <span class="badge {{ $invoice->status === 'paid' ? 'badge-pass' : 'badge-warn' }}">{{ ucfirst($invoice->status) }}</span>
        </div>

        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between">
                <dt class="muted">Number</dt>
                <dd class="mono">{{ $invoice->number }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Amount</dt>
                <dd class="mono">
                    {{ $invoice->currency === 'BDT' ? '৳'.number_format((float) $invoice->amount_bdt, 2) : '$'.number_format((float) $invoice->amount, 2) }}
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Period</dt>
                <dd>{{ $invoice->period_start?->format('j M Y') }} to {{ $invoice->period_end?->format('j M Y') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Created</dt>
                <dd>{{ $invoice->created_at->format('j M Y, H:i') }}</dd>
            </div>
            @if ($invoice->paid_at)
                <div class="flex justify-between">
                    <dt class="muted">Paid</dt>
                    <dd>{{ $invoice->paid_at->format('j M Y, H:i') }}</dd>
                </div>
            @endif
        </dl>

        @if (! $invoice->isPaid() && $gateways->isNotEmpty())
            <div class="rule my-4"></div>
            <h3 class="text-lg mb-3">Pay this invoice</h3>
            <form method="POST" action="{{ route('billing.process', $invoice) }}" class="space-y-3">
                @csrf
                <div class="space-y-2">
                    @foreach ($gateways as $gateway)
                        <label class="card-flat" style="padding:.7rem .9rem;cursor:pointer;display:block;">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="gateway" value="{{ $gateway->slug }}" @checked($loop->first)>
                                <img src="{{ asset('brand/'.($gateway->logo ?: 'bank').'.svg') }}" alt="{{ $gateway->name }}" height="20"
                                     onerror="this.style.display='none'">
                                <span class="text-sm">{{ $gateway->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-block" type="submit">Continue to payment</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">What happens next</h2>
        <ol class="space-y-3" style="font-size:.93rem;">
            <li class="flex gap-3">
                <span class="step-number shrink-0">1</span>
                <span>Choose a payment method and complete the payment.</span>
            </li>
            <li class="flex gap-3">
                <span class="step-number shrink-0">2</span>
                <span>The gateway confirms the payment and the invoice is marked as paid automatically.</span>
            </li>
            <li class="flex gap-3">
                <span class="step-number shrink-0">3</span>
                <span>Your plan and credits are applied to the account straight away.</span>
            </li>
        </ol>

        @if (! $invoice->isPaid())
            <div class="rule my-4"></div>
            <form method="POST" action="{{ route('billing.manual', $invoice) }}">
                @csrf
                <p class="muted text-sm mb-3">Paid by bank transfer? Tell us and we will confirm it by hand.</p>
                <button class="btn btn-ghost btn-sm btn-block" type="submit">I have paid by transfer</button>
            </form>
        @endif
    </div>
</div>

@endsection
'''

FILES['billing/checkout.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Checkout')
@section('heading', 'Checkout')
@section('subheading', 'Invoice '.$invoice->number)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Payment method</h2>

        @if ($gateways->isEmpty())
            <p class="muted mb-4">
                No payment method is switched on right now. Contact support and we will take the
                payment by hand.
            </p>
        @else
            <form method="POST" action="{{ route('billing.process', $invoice) }}" class="space-y-3">
                @csrf
                <div class="space-y-2">
                    @foreach ($gateways as $gateway)
                        <label class="card-flat" style="padding:.85rem 1rem;cursor:pointer;display:block;">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="gateway" value="{{ $gateway->slug }}" @checked($loop->first)>
                                <img src="{{ asset('brand/'.($gateway->logo ?: 'bank').'.svg') }}" alt="{{ $gateway->name }}" height="22"
                                     onerror="this.style.display='none'">
                                <span>
                                    <span class="block font-semibold text-sm">{{ $gateway->name }}</span>
                                    <span class="faint text-xs">{{ $gateway->description }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-block btn-lg" type="submit">Pay now</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Order summary</h2>
        <dl class="space-y-3" style="font-size:.93rem;">
            <div class="flex justify-between">
                <dt class="muted">Description</dt>
                <dd class="text-right">{{ $invoice->description }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Period</dt>
                <dd>{{ $invoice->period_start?->format('j M Y') }} to {{ $invoice->period_end?->format('j M Y') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="muted">Currency</dt>
                <dd class="mono">{{ $invoice->currency }}</dd>
            </div>
            <div class="rule"></div>
            <div class="flex justify-between items-baseline">
                <dt><strong>Total</strong></dt>
                <dd class="mono" style="font-size:1.3rem;">
                    {{ $invoice->currency === 'BDT' ? '৳'.number_format((float) $invoice->amount_bdt, 2) : '$'.number_format((float) $invoice->amount, 2) }}
                </dd>
            </div>
        </dl>
    </div>
</div>

@endsection
'''

FILES['billing/manual.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Payment instructions')
@section('heading', 'Payment instructions')
@section('subheading', 'Invoice '.$invoice->number)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">How to pay</h2>
        <ul class="space-y-2.5" style="font-size:.95rem;">
            @foreach ($instructions as $instruction)
                <li class="flex gap-2.5">
                    @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                    <span>{{ $instruction }}</span>
                </li>
            @endforeach
        </ul>

        <div class="rule my-4"></div>

        <p class="muted text-sm mb-3">Include the invoice number so we can match the payment.</p>
        <p class="mono" style="font-size:1.2rem;">{{ $invoice->number }}</p>
        <p class="mono" style="font-size:1.2rem;">
            {{ $invoice->currency === 'BDT' ? '৳'.number_format((float) $invoice->amount_bdt, 2) : '$'.number_format((float) $invoice->amount, 2) }}
        </p>

        <form method="POST" action="{{ route('billing.manual', $invoice) }}" class="mt-5">
            @csrf
            <button class="btn btn-primary btn-block" type="submit">I have made the payment</button>
        </form>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">After you pay</h2>
        <p class="muted mb-4">
            We check incoming payments by hand, so the account is usually live within a few
            hours. If nothing happens after a day, write to support with the invoice number.
        </p>
        <a href="{{ route('support.index') }}" class="btn btn-ghost btn-sm">Message support</a>
    </div>
</div>

@endsection
'''

FILES['billing/redirect.blade.php'] = r'''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting to payment</title>
    <link rel="icon" href="{{ asset('brand/adready.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css'])
</head>
<body class="grid-paper">
<div class="min-h-screen flex items-center justify-center px-5">
    <div class="card" style="padding:2rem;max-width:420px;text-align:center;">
        <h1 class="text-xl mb-2">Taking you to the payment page</h1>
        <p class="muted text-sm mb-5">If nothing happens, use the button below.</p>
        <a href="{{ $url }}" class="btn btn-primary btn-block" id="continue">Continue to payment</a>
    </div>
</div>

{!! $script !!}

<script>
    document.getElementById('continue').addEventListener('click', function () {
        setTimeout(function () { window.location.href = @json($url); }, 50);
    });
    setTimeout(function () { window.location.href = @json($url); }, 1200);
</script>
</body>
</html>
'''

FILES['affiliate/index.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Referrals')
@section('heading', 'Referral programme')
@section('subheading', 'Share your link, track every signup, withdraw what you earn')

@section('content')

<div class="grid grid-4" style="gap:1rem;">
    <div class="stat">
        <div class="k">Clicks</div>
        <div class="v">{{ number_format($stats['clicks']) }}</div>
        <div class="s">last 30 days and before</div>
    </div>
    <div class="stat">
        <div class="k">Signups</div>
        <div class="v">{{ number_format($stats['referrals']) }}</div>
        <div class="s">{{ $stats['conversions'] }} converted</div>
    </div>
    <div class="stat">
        <div class="k">Earned</div>
        <div class="v">${{ number_format($stats['earnings_total'], 2) }}</div>
        <div class="s">${{ number_format($stats['earnings_approved'], 2) }} cleared</div>
    </div>
    <div class="stat">
        <div class="k">Balance</div>
        <div class="v">${{ number_format($stats['balance'], 2) }}</div>
        <div class="s">ready to withdraw</div>
    </div>
</div>

<div class="grid grid-2 mt-4" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Your link</h2>
        <div class="flex gap-2">
            <input class="field mono" id="ref-link" value="{{ $link }}" readonly style="font-size:.9rem;">
            <button class="btn btn-primary btn-sm shrink-0" type="button" data-copy="#ref-link">Copy</button>
        </div>
        <p class="hint mt-2">
            Every signup through this link is attached to your account for 30 days. The commission
            is {{ (int) $stats['rate'] }}% of their first payment.
        </p>

        <div class="rule my-4"></div>

        <h3 class="text-lg mb-3">Request a withdrawal</h3>
        <form method="POST" action="{{ route('affiliate.payout') }}" class="space-y-3">
            @csrf

            <div>
                <label class="label" for="amount">Amount in US dollars</label>
                <input class="field" id="amount" name="amount" type="number" step="0.01" min="1"
                       max="{{ $stats['balance'] }}" value="{{ old('amount', number_format($stats['balance'], 2)) }}" required>
                <p class="hint mt-1">Minimum withdrawal is ${{ number_format($stats['payout_minimum'], 2) }}.</p>
            </div>

            <div>
                <label class="label" for="method">Pay me through</label>
                <select class="field" id="method" name="method" required>
                    <option value="paypal">PayPal</option>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="bank">Bank transfer</option>
                </select>
            </div>

            <div>
                <label class="label" for="paypal_email">PayPal email</label>
                <input class="field" id="paypal_email" name="paypal_email" type="email" placeholder="you@example.com">
            </div>

            <div>
                <label class="label" for="mobile">Mobile number for bKash or Nagad</label>
                <input class="field" id="mobile" name="mobile" placeholder="01XXXXXXXXX">
            </div>

            <div class="form-grid">
                <div>
                    <label class="label" for="bank_name">Bank name</label>
                    <input class="field" id="bank_name" name="bank_name">
                </div>
                <div>
                    <label class="label" for="account_number">Account number</label>
                    <input class="field" id="account_number" name="account_number">
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Request the withdrawal</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Earnings</h2>
            @if ($earnings->isEmpty())
                <p class="muted text-sm">No commissions yet. Share your link to get started.</p>
            @else
                <div class="table-wrap">
                    <table class="table-app">
                        <thead>
                            <tr><th>Source</th><th>Rate</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($earnings as $earning)
                                <tr>
                                    <td class="text-sm">{{ $earning->description }}</td>
                                    <td class="num muted">{{ (int) $earning->rate }}%</td>
                                    <td class="num">${{ number_format((float) $earning->amount, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $earning->status === 'paid' ? 'badge-pass' : ($earning->status === 'approved' ? 'badge-info' : 'badge-warn') }}">
                                            {{ ucfirst($earning->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $earnings->links() }}</div>
            @endif
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Withdrawals</h2>
            @if ($payouts->isEmpty())
                <p class="muted text-sm">No withdrawals requested yet.</p>
            @else
                <div class="table-wrap">
                    <table class="table-app">
                        <thead>
                            <tr><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($payouts as $payout)
                                <tr>
                                    <td class="num">${{ number_format((float) $payout->amount, 2) }}</td>
                                    <td class="text-sm muted">{{ ucfirst($payout->method) }}</td>
                                    <td>
                                        <span class="badge {{ $payout->status === 'paid' ? 'badge-pass' : ($payout->status === 'rejected' ? 'badge-fail' : 'badge-warn') }}">
                                            {{ ucfirst($payout->status) }}
                                        </span>
                                    </td>
                                    <td class="text-sm muted">{{ $payout->created_at->format('j M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $payouts->links() }}</div>
            @endif
        </div>
    </div>
</div>

@endsection
'''

FILES['settings/profile.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Profile')
@section('heading', 'Settings')
@section('subheading', 'Your profile, security and notification preferences')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Profile</h2></div>

        <form method="POST" action="{{ route('settings.profile.update') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="name">Name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div>
                <label class="label" for="phone">Phone</label>
                <input class="field" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>

            <div>
                <label class="label" for="country">Country</label>
                <input class="field" id="country" name="country" value="{{ old('country', $user->country) }}" placeholder="Bangladesh">
            </div>

            <div>
                <label class="label" for="currency">Preferred currency</label>
                <select class="field" id="currency" name="currency">
                    <option value="USD" @selected(old('currency', $user->currency ?: 'USD') === 'USD')>US dollars</option>
                    <option value="BDT" @selected(old('currency', $user->currency) === 'BDT')>Bangladeshi taka</option>
                </select>
            </div>

            <div>
                <label class="label" for="timezone">Timezone</label>
                <input class="field" id="timezone" name="timezone" value="{{ old('timezone', $user->timezone ?: 'Asia/Dhaka') }}">
            </div>

            <label class="flex items-center gap-2.5" style="font-size:.93rem;">
                <input type="checkbox" name="email_notifications" value="1" @checked(old('email_notifications', $user->email_notifications))>
                <span>Send me an email when an audit finishes or a payment is confirmed</span>
            </label>

            <button class="btn btn-primary" type="submit">Save the profile</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Account</h2>
            <dl class="space-y-3" style="font-size:.93rem;">
                <div class="flex justify-between">
                    <dt class="muted">Email</dt>
                    <dd>
                        {{ $user->email }}
                        @if ($user->hasVerifiedEmail())
                            <span class="badge badge-pass">Verified</span>
                        @else
                            <span class="badge badge-warn">Not verified</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Referral code</dt>
                    <dd class="mono">{{ $user->referral_code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Credits</dt>
                    <dd class="mono">{{ number_format((float) $user->credits_balance, 0) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Member since</dt>
                    <dd>{{ $user->created_at->format('j F Y') }}</dd>
                </div>
            </dl>

            <div class="rule my-4"></div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('settings.security') }}" class="btn btn-ghost btn-sm">Security</a>
                <a href="{{ route('settings.two-factor') }}" class="btn btn-ghost btn-sm">Two factor</a>
                <a href="{{ route('billing.index') }}" class="btn btn-ghost btn-sm">Billing</a>
            </div>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Close the account</h2>
            <p class="muted text-sm mb-4">
                Your audits and invoices stay on record for accounting. The account itself is
                removed and you can always create a new one.
            </p>
            <form method="POST" action="{{ route('settings.destroy') }}"
                  onsubmit="return confirm('Close your account permanently? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <div class="mb-3">
                    <label class="label" for="destroy-password">Confirm your password</label>
                    <input class="field" id="destroy-password" name="password" type="password" required>
                </div>
                <button class="btn btn-danger btn-sm" type="submit">Close the account</button>
            </form>
        </div>
    </div>
</div>

@endsection
'''

FILES['settings/security.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Security')
@section('heading', 'Security')
@section('subheading', 'Password, email address and sign in protection')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Change the password</h2>
        <form method="POST" action="{{ route('settings.password') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="current_password">Current password</label>
                <input class="field" id="current_password" name="current_password" type="password" required>
            </div>
            <div>
                <label class="label" for="password">New password</label>
                <input class="field" id="password" name="password" type="password" required>
                <p class="hint mt-1">At least eight characters with a number.</p>
            </div>
            <div>
                <label class="label" for="password_confirmation">Confirm the new password</label>
                <input class="field" id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Update the password</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Change the email address</h2>
            <p class="muted text-sm mb-4">We will ask you to confirm the new address before it becomes active.</p>
            <form method="POST" action="{{ route('settings.email') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="label" for="email">New email</label>
                    <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="label" for="email_password">Current password</label>
                    <input class="field" id="email_password" name="password" type="password" required>
                </div>
                <button class="btn btn-ghost" type="submit">Update the email</button>
            </form>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Two factor authentication</h2>
            @if ($user->hasTwoFactorEnabled())
                <p class="muted text-sm mb-4">Two factor authentication is on. A code is required at every sign in.</p>
                <a href="{{ route('settings.two-factor') }}" class="btn btn-ghost btn-sm">Manage two factor</a>
            @else
                <p class="muted text-sm mb-4">Add a second step to your sign in with an authenticator app.</p>
                <a href="{{ route('settings.two-factor') }}" class="btn btn-primary btn-sm">Turn on two factor</a>
            @endif
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Recent sign ins</h2>
            <dl class="space-y-2" style="font-size:.9rem;">
                <div class="flex justify-between">
                    <dt class="muted">Last sign in</dt>
                    <dd>{{ $user->last_login_at?->diffForHumans() ?? 'never' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Sign in count</dt>
                    <dd class="mono">{{ $user->login_count }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="muted">Last IP</dt>
                    <dd class="mono">{{ $user->last_login_ip ?? 'unknown' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>

@endsection
'''

FILES['settings/two-factor.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Two factor authentication')
@section('heading', 'Two factor authentication')
@section('subheading', 'A second step at sign in, using any authenticator app')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        @if ($user->hasTwoFactorEnabled())
            <h2 class="text-lg mb-3">Two factor is on</h2>
            <p class="muted text-sm mb-5">
                Every sign in asks for a six digit code from your authenticator app. If you lose
                the device, use one of the recovery codes below.
            </p>

            <div class="rule my-4"></div>

            <h3 class="text-lg mb-3">Recovery codes</h3>
            <p class="muted text-sm mb-3">Each code works once. Store them somewhere safe, away from your phone.</p>

            <div class="card-flat" style="padding:1rem;background:var(--color-canvas);">
                <div class="grid grid-2" style="gap:.4rem;">
                    @foreach ($user->two_factor_recovery_codes ?? [] as $code)
                        <span class="mono text-sm">{{ $code }}</span>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('settings.two-factor.recovery') }}" class="mt-4">
                @csrf
                <button class="btn btn-ghost btn-sm" type="submit">Generate a fresh set of codes</button>
            </form>

            <div class="rule my-5"></div>

            <h3 class="text-lg mb-3">Turn it off</h3>
            <form method="POST" action="{{ route('settings.two-factor.disable') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="label" for="disable-password">Confirm your password</label>
                    <input class="field" id="disable-password" name="password" type="password" required>
                </div>
                <button class="btn btn-danger btn-sm" type="submit">Turn off two factor</button>
            </form>
        @elseif (filled($user->two_factor_secret))
            <h2 class="text-lg mb-3">Finish the setup</h2>
            <p class="muted text-sm mb-5">Add this key to your authenticator app, then enter the code it shows.</p>

            <div class="card-flat" style="padding:1rem;background:var(--color-canvas);">
                <p class="label">Setup key</p>
                <p class="mono" style="word-break:break-all;font-size:.95rem;">{{ $user->two_factor_secret }}</p>
            </div>

            <form method="POST" action="{{ route('settings.two-factor.confirm') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="label" for="code">Code from the app</label>
                    <input class="field mono" id="code" name="code" inputmode="numeric" maxlength="6"
                           style="letter-spacing:.3em;text-align:center;font-size:1.2rem;" required>
                </div>
                <button class="btn btn-primary" type="submit">Confirm and turn on</button>
            </form>
        @else
            <h2 class="text-lg mb-3">Turn on two factor</h2>
            <p class="muted text-sm mb-5">
                Two factor authentication asks for a code from an authenticator app every time
                you sign in. It is the single best protection for an account that runs paid audits.
            </p>

            <ol class="space-y-3" style="font-size:.93rem;">
                <li class="flex gap-3"><span class="step-number shrink-0">1</span><span>Install an authenticator app such as 1Password, Authy or Google Authenticator.</span></li>
                <li class="flex gap-3"><span class="step-number shrink-0">2</span><span>Press the button below. A setup key appears on this page.</span></li>
                <li class="flex gap-3"><span class="step-number shrink-0">3</span><span>Add the key to the app, then type the six digit code it shows.</span></li>
            </ol>

            <form method="POST" action="{{ route('settings.two-factor.enable') }}" class="mt-5">
                @csrf
                <button class="btn btn-primary" type="submit">Start the setup</button>
            </form>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Why bother</h2>
        <p class="muted mb-4">
            An audit account holds paid reports and a payment history. Passwords get reused and
            leaked. A second factor removes that risk almost completely.
        </p>

        <div class="rule my-4"></div>

        <h3 class="text-lg mb-3">If you lose your phone</h3>
        <p class="muted text-sm mb-4">
            Use a recovery code. We give you a set of single use codes when you turn two factor
            on, and you can regenerate them at any time from this page.
        </p>

        @if (session('recovery_codes'))
            <div class="flash flash-success">
                <strong>Save these recovery codes now.</strong>
                <div class="grid grid-2 mt-2" style="gap:.3rem;">
                    @foreach (session('recovery_codes') as $code)
                        <span class="mono text-sm">{{ $code }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@endsection
'''

FILES['support/index.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Support')
@section('heading', 'Support')
@section('subheading', 'Ask a question and keep the whole thread in one place')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Conversation</h2></div>

        @if ($messages->isEmpty())
            <p class="muted text-sm mb-4">No messages yet. Start the conversation on the right.</p>
        @else
            <div class="space-y-3" style="max-height:52vh;overflow:auto;">
                @foreach ($messages as $message)
                    <div style="display:flex;justify-content:{{ $message->sender_type === 'user' ? 'flex-end' : 'flex-start' }};">
                        <div class="card-flat" style="padding:.7rem .9rem;max-width:80%;background:{{ $message->sender_type === 'user' ? 'var(--color-brand-soft)' : 'var(--color-paper)' }};">
                            <p class="text-sm" style="white-space:pre-line;">{{ $message->body }}</p>
                            <p class="faint text-xs mt-1">
                                {{ $message->sender_type === 'user' ? 'You' : 'Support' }} &middot; {{ $message->created_at->format('j M, H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Send a message</h2>
        <form method="POST" action="{{ route('support.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="subject">Subject</label>
                <input class="field" id="subject" name="subject" value="{{ old('subject', $conversation?->subject) }}" placeholder="A short summary">
            </div>
            <div>
                <label class="label" for="body">Message</label>
                <textarea class="field" id="body" name="body" rows="6" required placeholder="Tell us what you need. Include the domain and the page if it is about a specific audit.">{{ old('body') }}</textarea>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Send the message</button>
        </form>

        <div class="rule my-5"></div>

        <h3 class="text-lg mb-2">Faster routes</h3>
        <ul class="space-y-2" style="font-size:.92rem;">
            <li class="flex gap-2">
                @include('partials.icons', ['name' => 'mail', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                <span><a href="{{ route('contact.create') }}" class="underline">Use the contact form</a> for anything that is not about your account.</span>
            </li>
            <li class="flex gap-2">
                @include('partials.icons', ['name' => 'document', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                <span><a href="{{ route('blog.index') }}" class="underline">Read the blog</a> for approval guidance and policy explainers.</span>
            </li>
        </ul>
    </div>
</div>

@endsection
'''

FILES['notifications/index.blade.php'] = r'''
@extends('layouts.app')

@section('title', 'Notifications')
@section('heading', 'Notifications')
@section('subheading', 'Audit results, payment updates and support replies')

@section('actions')
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-ghost btn-sm" type="submit">Mark all as read</button>
    </form>
@endsection

@section('content')

<div class="panel">
    @if ($notifications->isEmpty())
        <p class="muted">Nothing here yet.</p>
    @else
        <ul class="space-y-2">
            @foreach ($notifications as $notification)
                <li class="card-flat" style="padding:.85rem 1rem;{{ $notification->isRead() ? 'opacity:.68;' : 'border-color:var(--color-brand-line);' }}">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <strong>{{ $notification->title }}</strong>
                            <p class="muted text-sm mt-1">{{ $notification->body }}</p>
                            <p class="faint text-xs mt-1 mono">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button class="btn btn-quiet btn-sm" type="submit">
                                {{ $notification->isRead() ? 'Open' : 'Mark as read' }}
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>

@endsection
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} product views")

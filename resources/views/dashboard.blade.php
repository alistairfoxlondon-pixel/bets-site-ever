
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

        <form method="POST" action="{{ route('audits.store') }}" data-busy-form data-busy-label="Crawling the site, this takes a moment" class="space-y-3">
            @csrf
            <div>
                <label class="label" for="url">Site address</label>
                <input class="field" id="url" name="url" placeholder="example.com" required>
            </div>

            <div>
                <label class="label">Audit depth</label>
                <div class="grid mode-grid">
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

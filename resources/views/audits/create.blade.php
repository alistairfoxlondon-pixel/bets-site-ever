
@extends('layouts.app')

@section('title', 'New audit')
@section('heading', 'New audit')
@section('subheading', 'We read public pages only, and never change anything on the site')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <form method="POST" action="{{ route('audits.store') }}" data-busy-form data-busy-label="Crawling the site, this takes a moment" class="space-y-5">
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

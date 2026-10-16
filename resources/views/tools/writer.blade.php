
@extends('layouts.marketing')

@section('title', ($type === 'affiliate_article' ? 'Affiliate article writer' : 'AdSense content writer').' | '.config('app.name'))
@section('description', $type === 'affiliate_article'
    ? 'Write affiliate articles with a clear disclosure in the first paragraph, ten FAQs and export for WordPress, Blogger, Shopify and Wix.'
    : 'Write long form AdSense ready articles with a title, meta description, FAQ block, internal links, external references and image prompts.')

@section('content')

@php
    $isAffiliate = $type === 'affiliate_article';
@endphp

<section class="band band-tight">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">{{ $isAffiliate ? 'Affiliate writer' : 'AdSense content writer' }}</p>
                <h1 class="mb-4">
                    {{ $isAffiliate ? 'Articles that sell without reading like a sales page' : 'The articles your audit keeps asking for' }}
                </h1>
                <p class="lede">
                    Give it a keyword. You get a finished article with the title, the meta
                    description, the headings, the FAQ block, the links and the image prompts,
                    ready to publish on WordPress, Blogger, Shopify or Wix.
                </p>

                <ul class="mt-6 space-y-2.5" style="font-size:.95rem;">
                    @foreach ([
                        $isAffiliate ? 'A clear affiliate disclosure in the first paragraph' : 'A natural voice that reads like a person, not a template',
                        $isAffiliate ? 'Honest comparisons, including who should not buy' : 'Original structure and no keyword stuffing',
                        'Meta title, meta description and excerpt included',
                        'Ten question and answer FAQs',
                        'Internal links, external references and image prompts',
                        'One click export for WordPress, Blogger, Shopify, Wix, HTML and Markdown',
                    ] as $point)
                        <li class="flex gap-2.5">
                            @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="flex flex-wrap gap-3 mt-7">
                    <a href="{{ auth()->check() ? route('documents.create', ['type' => $type]) : route('register') }}" class="btn btn-primary btn-lg">
                        {{ auth()->check() ? 'Open the writer' : 'Start writing free' }}
                    </a>
                    <a href="{{ route('audit.landing') }}" class="btn btn-ghost btn-lg">Audit a site first</a>
                </div>
            </div>

            <div class="visual-frame">
                @include('partials.visuals.writer-card')
            </div>
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <h2 class="mb-2">Two levels of output</h2>
        <p class="muted mb-7">Both are written to the same house rules: current information, no filler, no invented statistics and a clear point of view.</p>

        <div class="grid grid-2" style="gap:1.25rem;">
            @foreach ($modes as $mode)
                <div class="card" style="padding:1.5rem;">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h3>{{ $mode['label'] }}</h3>
                        <span class="chip">{{ $mode['credits'] }} credits</span>
                    </div>
                    <p class="muted text-sm mb-4">{{ $mode['words'] }}, a full FAQ block, ten question and answer pairs.</p>
                    <ul class="space-y-1.5" style="font-size:.9rem;">
                        <li class="flex gap-2"><span class="faint">&#183;</span><span class="muted">Title, slug and meta description</span></li>
                        <li class="flex gap-2"><span class="faint">&#183;</span><span class="muted">Outline before the draft is written</span></li>
                        <li class="flex gap-2"><span class="faint">&#183;</span><span class="muted">Internal and external link suggestions</span></li>
                        <li class="flex gap-2"><span class="faint">&#183;</span><span class="muted">Image prompts matched to each section</span></li>
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="band">
    <div class="wrap-narrow">
        <h2 class="mb-5">How the writing rules work</h2>
        <div class="card" style="padding:1.5rem 1.7rem;">
            <ol class="space-y-4" style="font-size:.96rem;">
                @foreach ([
                    ['Write like a person who used the thing', 'The article explains what something feels like in practice, not what a brochure says about it.'],
                    ['Never invent a number', 'If a figure is needed and we do not have a verified one, the article explains the mechanism instead of making a statistic up.'],
                    ['No dashes, no hashtags, no filler', 'The house style bans the punctuation and the empty openings that make content read as machine written.'],
                    ['Say what is current', 'Anything that changes over time is described in its current state, and the date is known to the writer.'],
                    ['E-E-A-T is visible', 'The structure, the specific experience and the honest tradeoffs are what a reviewer is looking for.'],
                ] as $index => $rule)
                    <li class="flex gap-4">
                        <span class="step-number shrink-0">{{ $index + 1 }}</span>
                        <span>
                            <strong class="block">{{ $rule[0] }}</strong>
                            <span class="muted text-sm">{{ $rule[1] }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

@endsection

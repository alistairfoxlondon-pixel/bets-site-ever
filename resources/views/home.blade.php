
@extends('layouts.marketing')

@section('title', config('app.name').' | Understand your publisher-site readiness')
@section('description', 'Review public site signals for Google AdSense, Google Ad Manager, Ezoic, Mediavine and Raptive with evidence-linked findings, prioritised improvements and a branded PDF report.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3.5rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">Publisher-site readiness audit</p>
                <h1>See the signals behind a stronger publishing site.</h1>
                <p class="lede mt-4">
                    Crawl a sample of public pages, review visible content and technical signals,
                    then get evidence-linked findings and a practical plan. The readiness index is
                    a product heuristic, not an approval prediction.
                </p>

                <div class="flex flex-wrap items-center gap-3 mt-7">
                    <a href="{{ route('audit.landing') }}" class="btn btn-primary btn-lg">
                        Audit a site free
                        @include('partials.icons', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])
                    </a>
                    <a href="{{ route('pricing') }}" class="btn btn-ghost btn-lg">See the plans</a>
                </div>

                <p class="hint mt-4">No card needed. Two free audits are included with every new account.</p>
            </div>

            <div class="visual-frame">
                @include('partials.visuals.audit-console')
            </div>
        </div>
    </div>
</section>

<section class="band band-tight band-line">
    <div class="wrap">
        <p class="text-center muted text-sm mb-6">One crawl, five network-specific public-signal views</p>
        <div class="logo-strip justify-center">
            <img src="/brand/adsense.svg" alt="Google AdSense" height="30">
            <img src="/brand/adx.svg" alt="Google Ad Manager" height="30">
            <img src="/brand/ezoic.svg" alt="Ezoic" height="30">
            <img src="/brand/mediavine.svg" alt="Mediavine" height="30">
            <img src="/brand/raptive.svg" alt="Raptive" height="30">
        </div>
    </div>
</section>

<section class="band">
    <div class="wrap">
        <div class="grid grid-3" style="gap:2.5rem;">
            <div>
                <p class="eyebrow mb-3">Under the hood</p>
                <h2 class="mb-3">What happens when you run an audit</h2>
                <p class="muted">
                    Review the deterministic checks, inspect the crawl evidence, and decide which
                    recommendations fit your site. Model observations are advisory, not verified facts.
                </p>
            </div>

            <div class="visual-frame col-span-2">
                @include('partials.visuals.pipeline', ['class' => 'col-span-2'])
            </div>
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div class="visual-frame">
                @include('partials.visuals.report-sheet')
            </div>
            <div>
                <p class="eyebrow mb-3">The report</p>
                <h2 class="mb-3">A document you can act on, not a score to brag about</h2>
                <p class="muted mb-5">
                    Every audit includes a branded PDF with the internal readiness index, category
                    signals, findings, crawl limitations, and prioritised improvements. Traffic,
                    account status and partner eligibility are not assessed from public pages.
                </p>

                <ul class="space-y-3">
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">
                            @include('partials.icons', ['name' => 'target', 'class' => 'w-5 h-5'])
                        </span>
                        <span>
                            <strong class="block">Public-site readiness index</strong>
                            <span class="muted text-sm">A weighted product heuristic from sampled page findings, not a calibrated probability or network decision.</span>
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">
                            @include('partials.icons', ['name' => 'layers', 'class' => 'w-5 h-5'])
                        </span>
                        <span>
                            <strong class="block">Findings in green, amber and red</strong>
                            <span class="muted text-sm">Deterministic findings show sampled evidence; model notes are separated and should be checked in context.</span>
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">
                            @include('partials.icons', ['name' => 'clock', 'class' => 'w-5 h-5'])
                        </span>
                        <span>
                            <strong class="block">Prioritised improvement plan</strong>
                            <span class="muted text-sm">A practical sequence based on potential impact and product rules; verify each item before making changes.</span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="band">
    <div class="wrap">
        <div class="text-center" style="max-width:640px;margin-inline:auto;">
            <p class="eyebrow mb-3">What gets checked</p>
            <h2 class="mb-3">Six areas of public-site signals</h2>
            <p class="muted">
                These product-defined checks help you review a public site. They are not an official
                network checklist and cannot predict an application outcome.
            </p>
        </div>

        <div class="grid grid-3 mt-8" style="gap:1.25rem;">
            @foreach ([
                ['icon' => 'document', 'title' => 'Content signals', 'text' => 'Sampled page depth, repeated phrasing, headings, readability and visible usefulness cues.'],
                ['icon' => 'shield', 'title' => 'Policy context', 'text' => 'Public-page signals such as disclosure links, download references, navigation and content context; no policy verdict is guaranteed.'],
                ['icon' => 'users', 'title' => 'Traffic quality', 'text' => 'Not assessed by a public crawl. Review your own analytics and traffic-source evidence separately.'],
                ['icon' => 'key', 'title' => 'Trust and publisher identity', 'text' => 'Visible author signals, contact routes, about and privacy links, and publisher details.'],
                ['icon' => 'search', 'title' => 'SEO and discovery', 'text' => 'Crawlable page signals, robots rules, sitemap discovery, metadata, structured data and response time.'],
                ['icon' => 'layers', 'title' => 'Technical experience', 'text' => 'HTTPS, viewport declarations, response size and other technical details detectable in the sampled markup.'],
            ] as $feature)
                <div class="feature">
                    <span class="icon-box">
                        @include('partials.icons', ['name' => $feature['icon'], 'class' => 'w-5 h-5'])
                    </span>
                    <h3>{{ $feature['title'] }}</h3>
                    <p>{{ $feature['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="band band-ink">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3" style="color:#8fc0e6;">Also inside the account</p>
                <h2 class="mb-3" style="color:#fff;">Draft useful publisher content</h2>
                <p class="mb-6" style="color:#bcd3e6;">
                    Create reader-first informational or affiliate drafts with SEO metadata, FAQs,
                    publisher-supplied links and disclosure prompts. Review every draft for accuracy
                    before publishing; no article can guarantee search visibility or network approval.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('writer.landing') }}" class="btn btn-lg" style="background:#fff;color:#12395a;border-color:#fff;">Open the writer</a>
                    <a href="{{ route('indexing.landing') }}" class="btn btn-lg" style="background:transparent;color:#fff;border-color:#5d87a8;">Push URLs to Google and Bing</a>
                </div>
            </div>
            <div class="visual-frame">
                @include('partials.visuals.writer-card')
            </div>
        </div>
    </div>
</section>

<section class="band band-tight">
    <div class="wrap">
        <div class="grid grid-4" style="gap:1.25rem;">
            <div class="stat">
                <div class="k">Audits completed</div>
                <div class="v">{{ number_format($stats['audits']) }}</div>
                <div class="s">site reviews generated</div>
            </div>
            <div class="stat">
                <div class="k">Distinct sites</div>
                <div class="v">{{ number_format($stats['sites']) }}</div>
                <div class="s">audited at least once</div>
            </div>
            <div class="stat">
                <div class="k">Review areas</div>
                <div class="v">6</div>
                <div class="s">including traffic marked not assessed</div>
            </div>
            <div class="stat">
                <div class="k">Network views</div>
                <div class="v">5</div>
                <div class="s">public signals, not eligibility checks</div>
            </div>
        </div>
    </div>
</section>

@if ($plans->isNotEmpty())
<section class="band band-line">
    <div class="wrap">
        <div class="text-center" style="max-width:620px;margin-inline:auto;">
            <p class="eyebrow mb-3">Plans</p>
            <h2 class="mb-3">Start free, pay when the reports start paying off</h2>
            <p class="muted mb-8">Every plan includes the full auditor, the PDF report and the writer.</p>
        </div>

        <div class="grid grid-3" style="gap:1.25rem;align-items:start;">
            @foreach ($plans as $plan)
                <div class="card" style="padding:1.6rem;">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h3>{{ $plan->name }}</h3>
                        @if ($plan->is_featured)
                            <span class="badge badge-info">Recommended</span>
                        @endif
                    </div>
                    <p class="muted text-sm mb-4">{{ $plan->tagline }}</p>
                    <p class="display" style="font-size:2.1rem;line-height:1;">
                        ${{ number_format((float) $plan->price_monthly, 0) }}<span class="muted" style="font-size:1rem;">/month</span>
                    </p>
                    <ul class="mt-5 space-y-2" style="font-size:.92rem;">
                        @foreach (array_slice((array) $plan->features, 0, 5) as $feature)
                            <li class="flex gap-2">
                                @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('billing.index') }}" class="btn {{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} btn-block mt-5">Choose {{ $plan->name }}</a>
                </div>
            @endforeach
        </div>

        <p class="text-center mt-6">
            <a href="{{ route('pricing') }}" class="btn btn-quiet">Compare every plan</a>
        </p>
    </div>
</section>
@endif

<section class="band">
    <div class="wrap-narrow">
        <p class="eyebrow mb-3 text-center">Questions</p>
        <h2 class="mb-6 text-center">Straight answers</h2>

        <div class="card" style="padding:0 1.4rem;">
            @foreach ([
                ['Does a high readiness index guarantee approval?', 'No. The index is a product-defined heuristic from sampled public pages. It does not predict a network decision, and it cannot assess private analytics, account history or current eligibility terms.'],
                ['What does the crawler actually read?', 'It requests the submitted site and a limited set of discoverable public pages and files, subject to crawl limits and site responses. It never logs in or submits forms. Some pages may be missed.']
                ['Can I audit a competitor site?', 'Yes. Auditing a public site is allowed. You cannot log in, and nothing is changed on the target site.'],
                ['How many credits does an audit use?', 'Normal uses one credit, Standard uses three and Premium uses six. The Standard plan includes 40 credits each month.'],
                ['Do you keep my audits?', 'Yes. Every audit is stored against your account so you can re-run it after a fix and compare the two runs side by side.'],
                ['Which languages can the writer produce?', 'The writer supports languages available in your configured model. Review translations and fact-sensitive details with a fluent reader before publishing.'],
            ] as $index => $faq)
                <details class="qa" @if ($index === 0) open @endif>
                    <summary>{{ $faq[0] }}</summary>
                    <div class="a">{{ $faq[1] }}</div>
                </details>
            @endforeach
        </div>

        <p class="text-center mt-8 muted">
            Still unsure? <a href="{{ route('contact.create') }}" class="underline">Ask us directly</a>.
        </p>
    </div>
</section>

@if ($posts->isNotEmpty())
<section class="band band-line">
    <div class="wrap">
        <div class="flex items-end justify-between gap-4 mb-7">
            <div>
                <p class="eyebrow mb-2">From the blog</p>
                <h2>Approval notes</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="btn btn-ghost btn-sm">All articles</a>
        </div>

        <div class="grid grid-3" style="gap:1.25rem;">
            @foreach ($posts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="card" style="padding:1.4rem;text-decoration:none;">
                    <h3 class="text-lg mb-2">{{ $post->title }}</h3>
                    <p class="muted text-sm">{{ Str::limit($post->excerpt, 120) }}</p>
                    <p class="faint text-xs mt-3 mono">{{ $post->published_at?->format('j M Y') }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

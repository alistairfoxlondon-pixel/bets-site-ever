
@extends('layouts.marketing')

@section('title', config('app.name').' | Know your approval odds before you apply')
@section('description', 'AdReady audits your site against Google AdSense, AdX, Ezoic, Mediavine and Raptive, then gives you a realistic approval probability, a ranked fix list and a professional PDF report.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3.5rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">Ad network approval auditor</p>
                <h1>Know your approval odds before you apply.</h1>
                <p class="lede mt-4">
                    Point AdReady at any site. It crawls the pages a reviewer would look at,
                    runs the policy and quality checks that decide approval, and tells you the
                    real number with a step by step plan to fix the rest.
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
        <p class="text-center muted text-sm mb-6">One audit, five ad networks, one honest number</p>
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
                    No black box. Every point in the score comes from a check you can read,
                    and every check points at a page on your site.
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
                    Every audit ends with a professional PDF report: the approval probability,
                    the score behind every category, the findings ranked by severity, how ready
                    you are for each ad network, and the exact order to fix things in.
                </p>

                <ul class="space-y-3">
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">
                            @include('partials.icons', ['name' => 'target', 'class' => 'w-5 h-5'])
                        </span>
                        <span>
                            <strong class="block">Realistic probability</strong>
                            <span class="muted text-sm">Blended from a deterministic rule audit and a deep model review, so the number moves for the right reasons.</span>
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">
                            @include('partials.icons', ['name' => 'layers', 'class' => 'w-5 h-5'])
                        </span>
                        <span>
                            <strong class="block">Findings in green, amber and red</strong>
                            <span class="muted text-sm">Every finding carries the evidence that triggered it and the specific fix that moves the score.</span>
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">
                            @include('partials.icons', ['name' => 'clock', 'class' => 'w-5 h-5'])
                        </span>
                        <span>
                            <strong class="block">First attempt roadmap</strong>
                            <span class="muted text-sm">A numbered sequence of changes to make before you apply, ordered by the weight reviewers give them.</span>
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
            <h2 class="mb-3">The same six areas reviewers look at</h2>
            <p class="muted">
                Google does not publish a checklist, so we built one from what actually gets
                sites approved or rejected, then tested it against real sites.
            </p>
        </div>

        <div class="grid grid-3 mt-8" style="gap:1.25rem;">
            @foreach ([
                ['icon' => 'document', 'title' => 'Content quality', 'text' => 'Depth, originality, readability and whether each post is worth a reader\'s time.'],
                ['icon' => 'shield', 'title' => 'Policy risk', 'text' => 'Thin content, copied material, prohibited topics, missing disclosures and unsafe outbound links.'],
                ['icon' => 'users', 'title' => 'Traffic quality', 'text' => 'Engagement signals, bounce patterns, and whether the audience looks like real readers.'],
                ['icon' => 'key', 'title' => 'Trust and E-E-A-T', 'text' => 'Author identity, contact routes, about pages, privacy terms and a visible editorial voice.'],
                ['icon' => 'search', 'title' => 'SEO and indexing', 'text' => 'Crawlability, canonical tags, robots rules, sitemap health, structured data and page speed.'],
                ['icon' => 'layers', 'title' => 'Design and monetisation', 'text' => 'Mobile layout, navigation depth, ad placement readiness and the technical state of the site.'],
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
                <h2 class="mb-3" style="color:#fff;">Write the articles the auditor asks for</h2>
                <p class="mb-6" style="color:#bcd3e6;">
                    When the report says the site needs 25 substantial posts, the writer produces
                    them. AdSense ready articles and affiliate articles with a disclosure in the
                    first paragraph, exported for WordPress, Blogger, Shopify and Wix.
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
                <div class="k">Checks per audit</div>
                <div class="v">120<span class="muted" style="font-size:1rem;">+</span></div>
                <div class="s">across six review areas</div>
            </div>
            <div class="stat">
                <div class="k">Networks scored</div>
                <div class="v">5</div>
                <div class="s">from AdSense to Raptive</div>
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
                ['Does a high score guarantee approval?', 'No. It tells you how a reviewer would most likely see your site today. Approval also depends on how the site is configured when you apply and on traffic that the crawler cannot see.'],
                ['What does the crawler actually read?', 'The home page, robots.txt, the sitemap, ads.txt and the article pages a visitor would land on. It never logs in and it never submits forms.'],
                ['Can I audit a competitor site?', 'Yes. Auditing a public site is allowed. You cannot log in, and nothing is changed on the target site.'],
                ['How many credits does an audit use?', 'Normal uses one credit, Standard uses three and Premium uses six. The Standard plan includes 40 credits each month.'],
                ['Do you keep my audits?', 'Yes. Every audit is stored against your account so you can re-run it after a fix and compare the two runs side by side.'],
                ['Which languages can the writer produce?', 'The writer works in the language you ask for. English is the strongest because that is what the ad networks review most closely.'],
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

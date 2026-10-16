#!/usr/bin/env python3
"""Write the public marketing views."""
import os

ROOT = "/home/user/bets-site-ever/resources/views"
FILES = {}

FILES['home.blade.php'] = r'''
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

            <div>
                @include('partials.visuals.audit-console')
            </div>
        </div>
    </div>
</section>

<section class="band band-tight band-line">
    <div class="wrap">
        <p class="text-center muted text-sm mb-6">One audit, five ad networks, one honest number</p>
        <div class="logo-strip justify-center">
            <img src="{{ asset('brand/adsense.svg') }}" alt="Google AdSense" height="30">
            <img src="{{ asset('brand/adx.svg') }}" alt="Google Ad Manager" height="30">
            <img src="{{ asset('brand/ezoic.svg') }}" alt="Ezoic" height="30">
            <img src="{{ asset('brand/mediavine.svg') }}" alt="Mediavine" height="30">
            <img src="{{ asset('brand/raptive.svg') }}" alt="Raptive" height="30">
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

            @include('partials.visuals.pipeline', ['class' => 'col-span-2'])
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
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
                    <a href="{{ route('writer.landing') }}" class="btn btn-primary">Open the writer</a>
                    <a href="{{ route('indexing.landing') }}" class="btn btn-ghost" style="border-color:#3a5f80;color:#dce8f2;">Push URLs to Google and Bing</a>
                </div>
            </div>
            <div>
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
                ['How many credits does an audit use?', 'Normal uses one credit, Standard uses three and Premium uses six. The Standard plan includes 30 credits each month.'],
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
'''

FILES['pricing.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'Pricing | '.config('app.name'))
@section('description', 'Transparent plans for the AdReady auditor, writer and indexing tools. Pay in US dollars or Bangladeshi taka.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="text-center" style="max-width:640px;margin-inline:auto;">
            <p class="eyebrow mb-3">Pricing</p>
            <h1 class="mb-4">Simple plans, priced in dollars and taka</h1>
            <p class="lede">
                Every paid plan includes the full auditor, the PDF report, the writer and the
                indexing tool. Credits are the only thing that changes.
            </p>
        </div>

        <div class="grid grid-3 mt-9" style="gap:1.25rem;align-items:start;">
            @foreach ($plans as $plan)
                <div class="card" style="padding:1.7rem;@if ($plan->is_featured) border-color:var(--color-brand); @endif">
                    @if ($plan->is_featured)
                        <span class="badge badge-info mb-3">Most popular</span>
                    @endif

                    <h3 class="mb-1">{{ $plan->name }}</h3>
                    <p class="muted text-sm mb-5">{{ $plan->tagline }}</p>

                    <div class="flex items-baseline gap-2">
                        <span class="display" style="font-size:2.4rem;line-height:1;">
                            {{ $currency === 'BDT' ? '৳' : '$' }}{{ number_format($currency === 'BDT' ? (float) $plan->price_monthly_bdt : (float) $plan->price_monthly, 0) }}
                        </span>
                        <span class="muted text-sm">per month</span>
                    </div>

                    <p class="faint text-xs mt-2 mono">
                        or {{ $currency === 'BDT' ? '৳' : '$' }}{{ number_format($currency === 'BDT' ? (float) $plan->price_yearly_bdt : (float) $plan->price_yearly, 0) }} per year
                        @if ($plan->savingsPercent($currency) > 0)
                            (save {{ $plan->savingsPercent($currency) }}%)
                        @endif
                    </p>

                    <ul class="mt-5 space-y-2" style="font-size:.93rem;">
                        @foreach ((array) $plan->features as $feature)
                            <li class="flex gap-2">
                                @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                        <li class="flex gap-2">
                            @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                            <span>{{ number_format((int) $plan->credits) }} credits every month</span>
                        </li>
                        <li class="flex gap-2">
                            @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                            <span>Up to {{ number_format((int) $plan->max_sites) }} tracked sites</span>
                        </li>
                    </ul>

                    <a href="{{ route('billing.index') }}?cycle=monthly&plan={{ $plan->slug }}"
                       class="btn {{ $plan->is_featured ? 'btn-primary' : 'btn-ghost' }} btn-block mt-6">
                        Choose {{ $plan->name }}
                    </a>
                </div>
            @endforeach
        </div>

        <div class="card mt-8" style="padding:1.4rem 1.6rem;">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg">Pay in taka if that is easier</h3>
                    <p class="muted text-sm">Every price is stored in US dollars and converted at the live rate. Switch the currency on any page.</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('pricing', ['currency' => 'USD']) }}" class="btn btn-sm {{ $currency === 'USD' ? 'btn-primary' : 'btn-ghost' }}">USD</a>
                    <a href="{{ route('pricing', ['currency' => 'BDT']) }}" class="btn btn-sm {{ $currency === 'BDT' ? 'btn-primary' : 'btn-ghost' }}">BDT</a>
                </div>
            </div>
            <p class="faint text-xs mono mt-3">1 USD = ৳{{ number_format($rates['bdt_rate'] ?? 0, 2) }}</p>
        </div>
    </div>
</section>

<section class="band band-line band-tight">
    <div class="wrap">
        <h2 class="text-center mb-6">Pay with the method you already use</h2>
        <div class="logo-strip justify-center" style="gap:1.6rem 2rem;">
            @foreach ($gateways as $gateway)
                <img src="{{ asset('brand/'.$gateway->logo) }}" alt="{{ $gateway->name }}" height="26"
                     onerror="this.style.display='none'">
            @endforeach
        </div>
        <p class="text-center muted text-sm mt-5">
            Local mobile wallets, card processors and bank transfer are all supported. The admin
            switches each one on and drops in the credentials.
        </p>
    </div>
</section>

<section class="band">
    <div class="wrap-narrow">
        <h2 class="mb-5">Questions about billing</h2>
        <div class="card" style="padding:0 1.4rem;">
            @foreach ([
                ['What counts as a credit?', 'A Normal audit uses one credit, a Standard audit three, and a Premium audit six. A written article uses two credits, or five for the high quality writer.'],
                ['What happens when I run out?', 'Audits and writing pause, but your account, your saved reports and your history stay exactly where they are. Add credits or upgrade and you carry on.'],
                ['Can I cancel any time?', 'Yes. You keep every plan feature until the end of the period you already paid for, and your saved audits stay in the account.'],
                ['Do you refund credits?', 'Unused credits are refunded if you cancel within the first seven days of a purchase. After that they are still yours to use at any time.'],
                ['How do referrals pay out?', 'You earn a share of the first payment from every account you refer. Withdraw from the affiliate page once you clear the minimum balance.'],
            ] as $index => $faq)
                <details class="qa" @if ($index === 0) open @endif>
                    <summary>{{ $faq[0] }}</summary>
                    <div class="a">{{ $faq[1] }}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>

@endsection
'''

FILES['features.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'What is inside | '.config('app.name'))
@section('description', 'A full look at the AdReady auditor, the writer, the indexing tool and the report.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="text-center" style="max-width:640px;margin-inline:auto;">
            <p class="eyebrow mb-3">Inside the product</p>
            <h1 class="mb-4">Four tools that share one engine</h1>
            <p class="lede">
                The auditor tells you what is missing, the writer fills the gap, the indexing tool
                publishes the result and the report proves the progress.
            </p>
        </div>

        <div class="grid grid-2 mt-9" style="gap:1.25rem;">
            @foreach ([
                ['icon' => 'shield', 'title' => 'Approval auditor', 'text' => 'Crawls up to 25 pages, checks 120 signals, scores six review areas and produces a realistic probability with a ranked fix list.'],
                ['icon' => 'pen', 'title' => 'AdSense content writer', 'text' => 'Long form articles with the title, meta description, FAQ block, internal links, external references and image prompts a publisher needs.'],
                ['icon' => 'link', 'title' => 'Affiliate writer', 'text' => 'The same depth with a clear affiliate disclosure in the first paragraph and tagged monetised links.'],
                ['icon' => 'search', 'title' => 'Quick indexing', 'text' => 'Push a URL to the Google Indexing API and to Bing through IndexNow, with a full submission history.'],
            ] as $tool)
                <div class="card" style="padding:1.6rem;">
                    <span class="icon-box">
                        @include('partials.icons', ['name' => $tool['icon'], 'class' => 'w-5 h-5'])
                    </span>
                    <h3 class="mb-2">{{ $tool['title'] }}</h3>
                    <p class="muted">{{ $tool['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                @include('partials.visuals.comparison-sheet')
            </div>
            <div>
                <p class="eyebrow mb-3">Progress you can see</p>
                <h2 class="mb-3">Re-audit after every fix</h2>
                <p class="muted">
                    The point of a second audit is the difference. AdReady keeps every run, and the
                    comparison view lines up the score, the probability and each category so you
                    can see exactly what your work changed.
                </p>
                <ul class="mt-5 space-y-3" style="font-size:.95rem;">
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">@include('partials.icons', ['name' => 'chart', 'class' => 'w-4 h-4'])</span>
                        <span>Category by category movement between two runs</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">@include('partials.icons', ['name' => 'layers', 'class' => 'w-4 h-4'])</span>
                        <span>Findings that were closed and findings that appeared</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">@include('partials.icons', ['name' => 'download', 'class' => 'w-4 h-4'])</span>
                        <span>Both reports as PDFs, side by side</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

@endsection
'''

FILES['contact.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'Contact | '.config('app.name'))
@section('description', 'Talk to the AdReady team about billing, audits, partnerships or anything else.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:start;">
            <div>
                <p class="eyebrow mb-3">Contact</p>
                <h1 class="mb-4">Tell us what you need</h1>
                <p class="lede mb-6">
                    Pick a reason so the right person picks it up. We answer every message within
                    one business day.
                </p>

                @include('partials.visuals.contact-rail')
            </div>

            <div class="card" style="padding:1.6rem;">
                <form method="POST" action="{{ route('contact.store') }}" class="form-grid">
                    @csrf

                    <div>
                        <label class="label" for="name">Your name</label>
                        <input class="field" id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required>
                    </div>

                    <div>
                        <label class="label" for="email">Email</label>
                        <input class="field" id="email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required>
                    </div>

                    <div>
                        <label class="label" for="category">Reason</label>
                        <select class="field" id="category" name="category" required>
                            <option value="">Choose a reason</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $preset) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label" for="subject">Subject</label>
                        <input class="field" id="subject" name="subject" value="{{ old('subject') }}" required>
                    </div>

                    <div class="col-span-2">
                        <label class="label" for="message">Message</label>
                        <textarea class="field" id="message" name="message" rows="6" required placeholder="Include the domain and the page URL if it is about a specific site.">{{ old('message') }}</textarea>
                        <p class="hint mt-1">The more detail you give, the faster we can help.</p>
                    </div>

                    <div class="col-span-2">
                        <button class="btn btn-primary" type="submit">Send message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
'''

FILES['blog/index.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'Blog | '.config('app.name'))
@section('description', 'Practical notes on AdSense approval, ad network policy and publishing sites that pass review.')

@section('content')

<section class="band">
    <div class="wrap">
        <p class="eyebrow mb-3">Blog</p>
        <h1 class="mb-4">Approval notes</h1>
        <p class="lede" style="max-width:620px;">
            What reviewers look for, what gets a site rejected, and the small changes that
            move an approval from unlikely to likely.
        </p>
    </div>
</section>

<section class="band band-tight" style="padding-top:0;">
    <div class="wrap">
        @if ($posts->isEmpty())
            <div class="card" style="padding:2.5rem;text-align:center;">
                <h3 class="mb-2">No articles published yet</h3>
                <p class="muted">The first piece is being written. Check back soon.</p>
            </div>
        @else
            <div class="grid grid-3" style="gap:1.25rem;">
                @foreach ($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="card" style="padding:1.5rem;text-decoration:none;display:block;">
                        <div class="flex items-center gap-2 mb-3">
                            @foreach ((array) $post->tags as $tag)
                                <span class="chip">{{ $tag }}</span>
                            @endforeach
                        </div>
                        <h3 class="mb-2">{{ $post->title }}</h3>
                        <p class="muted text-sm">{{ Str::limit($post->excerpt, 150) }}</p>
                        <p class="faint text-xs mono mt-4">{{ $post->published_at?->format('j M Y') }} &middot; {{ $post->readingTime ?? 4 }} min read</p>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</section>

@endsection
'''

FILES['blog/show.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', $post->title.' | '.config('app.name'))
@section('description', $post->meta_description ?: Str::limit($post->excerpt, 155))

@section('content')

<section class="band band-tight">
    <div class="wrap-narrow">
        <a href="{{ route('blog.index') }}" class="muted text-sm no-underline">&larr; All articles</a>

        <div class="flex flex-wrap items-center gap-2 mt-5 mb-4">
            @foreach ((array) $post->tags as $tag)
                <span class="chip">{{ $tag }}</span>
            @endforeach
        </div>

        <h1 class="mb-4">{{ $post->title }}</h1>
        <p class="faint text-sm mono mb-7">
            {{ $post->published_at?->format('j F Y') }}
            @if ($post->author) &middot; {{ $post->author->name }} @endif
        </p>

        @if ($post->cover_image)
            <img src="{{ $post->cover_image }}" alt="" class="w-full mb-8" style="border-radius:var(--radius-md);">
        @endif

        <div class="prose-app">{!! $post->body !!}</div>

        <div class="rule" style="margin:3rem 0 1.5rem;"></div>

        <div class="flex flex-wrap gap-3 items-center justify-between">
            <p class="muted text-sm">Written for people who are about to apply.</p>
            <a href="{{ route('audit.landing') }}" class="btn btn-primary btn-sm">Audit a site</a>
        </div>
    </div>
</section>

@if ($related->isNotEmpty())
<section class="band band-line band-tight">
    <div class="wrap">
        <h2 class="mb-5">Read next</h2>
        <div class="grid grid-3" style="gap:1.25rem;">
            @foreach ($related as $item)
                <a href="{{ route('blog.show', $item->slug) }}" class="card" style="padding:1.4rem;text-decoration:none;">
                    <h3 class="text-lg mb-2">{{ $item->title }}</h3>
                    <p class="muted text-sm">{{ Str::limit($item->excerpt, 110) }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
'''

FILES['page/show.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', $page->title.' | '.config('app.name'))
@section('description', $page->meta_description ?: Str::limit(strip_tags($page->body), 155))

@section('content')

<section class="band band-tight">
    <div class="wrap-narrow">
        <h1 class="mb-6">{{ $page->title }}</h1>
        <div class="prose-app">{!! $page->body !!}</div>
        <p class="faint text-xs mono mt-10">Last updated {{ $page->updated_at?->format('j F Y') }}</p>
    </div>
</section>

@endsection
'''

FILES['page/generic.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'Page | '.config('app.name'))

@section('content')

<section class="band">
    <div class="wrap-narrow text-center">
        <h1 class="mb-3">This page is being written</h1>
        <p class="lede mb-6">
            The legal pages are managed by the administrator. If you need a copy right now,
            ask us and we will send it.
        </p>
        <a href="{{ route('contact.create') }}" class="btn btn-primary">Contact the team</a>
    </div>
</section>

@endsection
'''

FILES['affiliate/landing.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'Affiliate program | '.config('app.name'))
@section('description', 'Earn a share of every subscription you send to AdReady. Track every click, every signup and every payout.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">Affiliate program</p>
                <h1 class="mb-4">Earn {{ (int) $rate }}% of every subscription you send</h1>
                <p class="lede mb-6">
                    One link. Every signup and every payment is tracked. When somebody you
                    referred upgrades, the commission lands in your wallet and you can withdraw it.
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ auth()->check() ? route('affiliate.index') : route('register') }}" class="btn btn-primary btn-lg">
                        {{ auth()->check() ? 'Open your dashboard' : 'Join and get your link' }}
                    </a>
                    <a href="{{ route('pricing') }}" class="btn btn-ghost btn-lg">See the plans</a>
                </div>

                <div class="grid grid-3 mt-8" style="gap:1rem;">
                    <div class="stat">
                        <div class="k">Commission</div>
                        <div class="v">{{ (int) $rate }}%</div>
                        <div class="s">of the first payment</div>
                    </div>
                    <div class="stat">
                        <div class="k">Minimum payout</div>
                        <div class="v">${{ number_format($minimum, 0) }}</div>
                        <div class="s">paid to your account</div>
                    </div>
                    <div class="stat">
                        <div class="k">Cookie</div>
                        <div class="v">30d</div>
                        <div class="s">attribution window</div>
                    </div>
                </div>
            </div>

            <div>
                @include('partials.visuals.referral-card')
            </div>
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <h2 class="mb-6">How it works</h2>
        <div class="grid grid-3" style="gap:1.25rem;">
            @foreach ([
                ['title' => 'Share your link', 'text' => 'Your personal link sits in the affiliate page. Share it where your audience already trusts you.'],
                ['title' => 'We track the click', 'text' => 'The referral is attached to the browser for 30 days, so the signup is credited to you.'],
                ['title' => 'Get paid on their payment', 'text' => 'When they pay for a plan, the commission is credited to your wallet and you request a withdrawal.'],
            ] as $index => $step)
                <div class="feature">
                    <span class="step-number">{{ $index + 1 }}</span>
                    <h3 class="mt-3">{{ $step['title'] }}</h3>
                    <p>{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} marketing views")

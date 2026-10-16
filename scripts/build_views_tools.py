#!/usr/bin/env python3
"""Write the tool landing pages and the authentication views."""
import os

ROOT = "/home/user/bets-site-ever/resources/views"
FILES = {}

FILES['tools/audit.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'AdSense approval checker | '.config('app.name'))
@section('description', 'Audit any site for Google AdSense, AdX, Ezoic, Mediavine and Raptive approval. Get a realistic probability, a ranked fix list and a PDF report.')

@section('content')

<section class="band band-tight">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">Approval auditor</p>
                <h1 class="mb-4">Check a site before you apply</h1>
                <p class="lede">
                    Enter a domain. We crawl the pages a reviewer would open, run the checks that
                    decide approval, and hand you a probability with a plan to improve it.
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
                @include('partials.visuals.audit-console')
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
            <h2 class="mb-3">The same audit tells you where you stand with every network</h2>
            <p class="muted mb-8">Each network adds its own requirements on top of the AdSense bar. The report shows all five side by side.</p>
        </div>

        @include('partials.visuals.network-map', ['class' => 'mx-auto'])

        <div class="grid grid-3 mt-8" style="gap:1.25rem;">
            @foreach ($networks as $key => $network)
                <div class="card" style="padding:1.4rem;">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <img src="{{ asset('brand/'.($key === 'adx' ? 'adx' : $key).'.svg') }}" alt="{{ $network['label'] }}" height="22">
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
'''

FILES['tools/writer.blade.php'] = r'''
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

            <div>
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
'''

FILES['tools/indexing.blade.php'] = r'''
@extends('layouts.marketing')

@section('title', 'Quick indexing | '.config('app.name'))
@section('description', 'Push a URL to the Google Indexing API and to Bing through IndexNow, and keep a record of every submission.')

@section('content')

<section class="band band-tight">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div>
                <p class="eyebrow mb-3">Quick indexing</p>
                <h1 class="mb-4">Tell the search engines the page exists</h1>
                <p class="lede">
                    Publish a page and hand the URL straight to Google and to the IndexNow
                    network, which covers Bing, Yandex and the other engines that accept it.
                </p>

                <div class="flex flex-wrap gap-3 mt-7">
                    <a href="{{ auth()->check() ? route('indexing.index') : route('register') }}" class="btn btn-primary btn-lg">
                        {{ auth()->check() ? 'Open the tool' : 'Create a free account' }}
                    </a>
                    <a href="{{ route('writer.landing') }}" class="btn btn-ghost btn-lg">Write the article first</a>
                </div>

                <p class="hint mt-4">The Google Indexing API is built for job boards and event listings. For a normal article, IndexNow plus a clean sitemap is the right path.</p>
            </div>

            <div class="card" style="padding:1.6rem;">
                <p class="label">Submit a URL</p>
                <input class="field mb-3" type="text" placeholder="https://example.com/blog/my-article" disabled>
                <div class="space-y-2 mb-4">
                    @foreach ($engines as $slug => $label)
                        <label class="flex items-center gap-2.5" style="font-size:.92rem;">
                            <input type="checkbox" disabled checked>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-block" disabled>Send for indexing</button>
                <p class="hint mt-3">Sign in to use the tool. Every submission is recorded with its response.</p>
            </div>
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <h2 class="mb-6">What each engine does</h2>
        <div class="grid grid-3" style="gap:1.25rem;">
            @foreach ([
                ['icon' => 'search', 'title' => 'Google Indexing API', 'text' => 'A service account pushes the URL directly to Google. Built for pages that change often, such as job posts and listings.'],
                ['icon' => 'globe', 'title' => 'Bing through IndexNow', 'text' => 'One call notifies Bing and every engine that accepts the IndexNow protocol, including Yandex and Seznam.'],
                ['icon' => 'database', 'title' => 'Submission history', 'text' => 'Every request, engine and response is stored against your account so you can prove what was sent and when.'],
            ] as $item)
                <div class="feature">
                    <span class="icon-box">
                        @include('partials.icons', ['name' => $item['icon'], 'class' => 'w-5 h-5'])
                    </span>
                    <h3>{{ $item['title'] }}</h3>
                    <p>{{ $item['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
'''

FILES['auth/login.blade.php'] = r'''
@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Welcome back</h1>
    <p class="muted text-sm mb-6">Sign in to continue to your audits.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label class="label" for="password">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm muted" style="text-decoration:none;">Forgot it?</a>
            </div>
            <input class="field" id="password" type="password" name="password" required autocomplete="current-password">
        </div>

        <label class="flex items-center gap-2.5" style="font-size:.92rem;">
            <input type="checkbox" name="remember" value="1">
            <span>Keep me signed in on this device</span>
        </label>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
    </form>

    @if (config('services.google.client_id'))
        <div class="flex items-center gap-3 my-5">
            <span class="rule flex-1"></span>
            <span class="faint text-xs uppercase tracking-widest">or</span>
            <span class="rule flex-1"></span>
        </div>

        <a href="{{ route('google.redirect') }}" class="btn btn-ghost btn-block">
            <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.4a5.5 5.5 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.6-5.2 3.6-8.8z"/>
                <path fill="#34A853" d="M12 24c3.2 0 6-1.1 8-2.9l-3.9-3a7.2 7.2 0 0 1-10.7-3.8H1.3v3.1A12 12 0 0 0 12 24z"/>
                <path fill="#FBBC05" d="M5.4 14.3a7.2 7.2 0 0 1 0-4.6V6.6H1.3a12 12 0 0 0 0 10.8z"/>
                <path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A11.6 11.6 0 0 0 12 0 12 12 0 0 0 1.3 6.6l4.1 3.1A7.2 7.2 0 0 1 12 4.8z"/>
            </svg>
            Continue with Google
        </a>
    @endif

    <p class="text-center muted text-sm mt-6">
        No account yet? <a href="{{ route('register') }}" class="underline">Create one free</a>
    </p>
</div>

@endsection
'''

FILES['auth/register.blade.php'] = r'''
@extends('layouts.auth')

@section('title', 'Create your account')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Create your account</h1>
    <p class="muted text-sm mb-6">Two free audits are waiting for you.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        @if (request('ref') || app(\App\Services\Affiliate\AffiliateService::class)->pendingReferralCode())
            <input type="hidden" name="referral_code" value="{{ request('ref') ?? app(\App\Services\Affiliate\AffiliateService::class)->pendingReferralCode() }}">
            <div class="flash flash-success">
                You were referred by a member. The referral is attached to your account.
            </div>
        @endif

        <div>
            <label class="label" for="name">Name</label>
            <input class="field" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </div>

        <div>
            <label class="label" for="password">Password</label>
            <input class="field" id="password" type="password" name="password" required autocomplete="new-password">
            <p class="hint mt-1">At least eight characters with a number.</p>
        </div>

        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input class="field" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <label class="flex items-start gap-2.5" style="font-size:.9rem;">
            <input type="checkbox" name="terms" value="1" class="mt-1" required>
            <span class="muted">I agree to the <a href="{{ route('page.show', ['slug' => 'terms']) }}" class="underline">terms of service</a> and the <a href="{{ route('page.show', ['slug' => 'privacy']) }}" class="underline">privacy policy</a>.</span>
        </label>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Create account</button>
    </form>

    @if (config('services.google.client_id'))
        <div class="flex items-center gap-3 my-5">
            <span class="rule flex-1"></span>
            <span class="faint text-xs uppercase tracking-widest">or</span>
            <span class="rule flex-1"></span>
        </div>

        <a href="{{ route('google.redirect') }}" class="btn btn-ghost btn-block">
            <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.4a5.5 5.5 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.6-5.2 3.6-8.8z"/>
                <path fill="#34A853" d="M12 24c3.2 0 6-1.1 8-2.9l-3.9-3a7.2 7.2 0 0 1-10.7-3.8H1.3v3.1A12 12 0 0 0 12 24z"/>
                <path fill="#FBBC05" d="M5.4 14.3a7.2 7.2 0 0 1 0-4.6V6.6H1.3a12 12 0 0 0 0 10.8z"/>
                <path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A11.6 11.6 0 0 0 12 0 12 12 0 0 0 1.3 6.6l4.1 3.1A7.2 7.2 0 0 1 12 4.8z"/>
            </svg>
            Continue with Google
        </a>
    @endif

    <p class="text-center muted text-sm mt-6">
        Already registered? <a href="{{ route('login') }}" class="underline">Sign in</a>
    </p>
</div>

@endsection
'''

FILES['auth/forgot-password.blade.php'] = r'''
@extends('layouts.auth')

@section('title', 'Reset your password')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Reset your password</h1>
    <p class="muted text-sm mb-6">We will email you a link to set a new one.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Send the reset link</button>
    </form>

    <p class="text-center muted text-sm mt-6">
        <a href="{{ route('login') }}" class="underline">Back to sign in</a>
    </p>
</div>

@endsection
'''

FILES['auth/reset-password.blade.php'] = r'''
@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('content')

<div class="card" style="padding:1.8rem;">
    <h1 class="text-2xl mb-1">Choose a new password</h1>
    <p class="muted text-sm mb-6">Make it different from the one you just replaced.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="label" for="email">Email</label>
            <input class="field" id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
        </div>

        <div>
            <label class="label" for="password">New password</label>
            <input class="field" id="password" type="password" name="password" required autocomplete="new-password">
        </div>

        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input class="field" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Save the new password</button>
    </form>
</div>

@endsection
'''

FILES['auth/verify-email.blade.php'] = r'''
@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')

<div class="card" style="padding:1.8rem;text-align:center;">
    <span class="icon-box" style="margin:0 auto 1rem;">
        @include('partials.icons', ['name' => 'mail', 'class' => 'w-5 h-5'])
    </span>

    <h1 class="text-2xl mb-2">Confirm your email</h1>
    <p class="muted text-sm mb-6">
        We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
        Confirming it keeps your account secure and lets us send audit results.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
        @csrf
        <button class="btn btn-primary btn-block" type="submit">Send the link again</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-quiet btn-block" type="submit">Sign out</button>
    </form>
</div>

@endsection
'''

FILES['auth/two-factor-challenge.blade.php'] = r'''
@extends('layouts.auth')

@section('title', 'Two factor check')

@section('content')

<div class="card" style="padding:1.8rem;">
    <span class="icon-box" style="margin:0 0 1rem;">
        @include('partials.icons', ['name' => 'lock', 'class' => 'w-5 h-5'])
    </span>

    <h1 class="text-2xl mb-1">Confirm it is you</h1>
    <p class="muted text-sm mb-6">Enter the six digit code from your authenticator app.</p>

    @include('partials.flash')

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label class="label" for="code">Authentication code</label>
            <input class="field mono" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="12"
                   style="font-size:1.3rem;letter-spacing:.35em;text-align:center;" required autofocus>
            <p class="hint mt-1">A recovery code works here too.</p>
        </div>

        <button class="btn btn-primary btn-block btn-lg" type="submit">Verify</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button class="btn btn-quiet btn-block" type="submit">Sign out instead</button>
    </form>
</div>

@endsection
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} tool and auth views")

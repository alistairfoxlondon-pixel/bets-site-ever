<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', 'Review public publisher-site signals for major ad networks with evidence-linked findings, prioritised improvements and a professional PDF report.')">
    <link rel="icon" href="/brand/adready.svg" type="image/svg+xml">
    @include('partials.critical')
    @include('partials.assets')
    @stack('head')
</head>
<body>
<a href="#main" class="sr-only focus:not-sr-only">Skip to content</a>

<header class="site-header no-print">
    <div class="wrap nav-shell">
        <a href="{{ route('home') }}" class="flex items-center" aria-label="{{ config('app.name') }} home">
            <img src="/brand/adready-word.svg" alt="{{ config('app.name') }}" width="168" height="36">
        </a>

        <nav class="nav-links" aria-label="Primary">
            <a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Overview</a>
            <a class="nav-link {{ request()->routeIs('audit.*') ? 'is-active' : '' }}" href="{{ route('audit.landing') }}">Auditor</a>
            <a class="nav-link {{ request()->routeIs('writer.*') ? 'is-active' : '' }}" href="{{ route('writer.landing') }}">Writer</a>
            <a class="nav-link {{ request()->routeIs('indexing.landing') ? 'is-active' : '' }}" href="{{ route('indexing.landing') }}">Indexing</a>
            <a class="nav-link {{ request()->routeIs('pricing') ? 'is-active' : '' }}" href="{{ route('pricing') }}">Pricing</a>
            <a class="nav-link {{ request()->routeIs('blog.*') ? 'is-active' : '' }}" href="{{ route('blog.index') }}">Blog</a>
        </nav>

        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">Open app</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-quiet btn-sm">Log in</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Start free</a>
            @endauth
            <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" aria-label="Open menu">
                @include('partials.icons', ['name' => 'menu', 'class' => 'w-5 h-5'])
            </button>
        </div>
    </div>

    <div class="nav-drawer" id="mobile-nav" hidden>
        <a href="{{ route('home') }}">Overview</a>
        <a href="{{ route('audit.landing') }}">Auditor</a>
        <a href="{{ route('writer.landing') }}">Writer</a>
        <a href="{{ route('indexing.landing') }}">Indexing</a>
        <a href="{{ route('pricing') }}">Pricing</a>
        <a href="{{ route('blog.index') }}">Blog</a>
        <a href="{{ route('contact.create') }}">Contact</a>
        @auth
            <a href="{{ route('dashboard') }}">Open app</a>
        @else
            <a href="{{ route('login') }}">Log in</a>
            <a href="{{ route('register') }}">Start free</a>
        @endauth
    </div>
</header>

<main id="main">
    @yield('content')
</main>

<footer class="footer no-print">
    <div class="wrap">
        <div class="grid grid-4" style="gap:2rem;">
            <div>
                <img src="/brand/adready-word.svg" alt="{{ config('app.name') }}" width="150" height="32" class="mb-3">
                <p class="muted" style="font-size:.9rem;">Public evidence. Practical next steps.</p>
                <p class="faint mono" style="font-size:.78rem;">Audits for AdSense, AdX, Ezoic, Mediavine and Raptive.</p>
            </div>
            <div>
                <h4>Tools</h4>
                <ul class="space-y-1.5">
                    <li><a href="{{ route('audit.landing') }}">Publisher-site auditor</a></li>
                    <li><a href="{{ route('writer.landing') }}">AdSense writer</a></li>
                    <li><a href="{{ route('writer.affiliate') }}">Affiliate writer</a></li>
                    <li><a href="{{ route('indexing.landing') }}">Quick indexing</a></li>
                </ul>
            </div>
            <div>
                <h4>Company</h4>
                <ul class="space-y-1.5">
                    <li><a href="{{ route('page.show', ['slug' => 'about']) }}">About</a></li>
                    <li><a href="{{ route('blog.index') }}">Blog</a></li>
                    <li><a href="{{ route('contact.create') }}">Contact</a></li>
                    <li><a href="{{ route('affiliate.landing') }}">Affiliate program</a></li>
                </ul>
            </div>
            <div>
                <h4>Legal</h4>
                <ul class="space-y-1.5">
                    <li><a href="{{ route('page.show', ['slug' => 'terms']) }}">Terms of service</a></li>
                    <li><a href="{{ route('page.show', ['slug' => 'privacy']) }}">Privacy policy</a></li>
                    <li><a href="{{ route('page.show', ['slug' => 'refund-policy']) }}">Refund policy</a></li>
                    <li><a href="{{ route('page.show', ['slug' => 'acceptable-use']) }}">Acceptable use</a></li>
                </ul>
            </div>
        </div>

        <div class="rule" style="margin:2rem 0 1.25rem;"></div>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="faint" style="font-size:.84rem;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
            <div class="flex items-center gap-2">
                <a href="{{ request()->fullUrlWithQuery(['currency' => 'USD']) }}" class="btn btn-sm {{ ($displayCurrency ?? 'USD') === 'USD' ? 'btn-primary' : 'btn-ghost' }}">USD</a>
                <a href="{{ request()->fullUrlWithQuery(['currency' => 'BDT']) }}" class="btn btn-sm {{ ($displayCurrency ?? 'USD') === 'BDT' ? 'btn-primary' : 'btn-ghost' }}">BDT</a>
            </div>
            <div class="logo-strip" style="gap:1.1rem;">
                <img src="/brand/adsense.svg" alt="Google AdSense" height="20">
                <img src="/brand/adx.svg" alt="Google Ad Manager" height="20">
                <img src="/brand/ezoic.svg" alt="Ezoic" height="20">
                <img src="/brand/mediavine.svg" alt="Mediavine" height="20">
                <img src="/brand/raptive.svg" alt="Raptive" height="20">
            </div>
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>

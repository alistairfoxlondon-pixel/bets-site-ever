
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="/brand/adready.svg" type="image/svg+xml">
    @include('partials.critical')
    @include('partials.assets')
    @stack('head')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar no-print" id="app-sidebar">
        <div class="px-3 pb-4">
            <a href="{{ route('dashboard') }}" class="flex items-center">
                <img src="/brand/adready-word.svg" alt="{{ config('app.name') }}" width="150" height="32">
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Workspace</div>
            <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
                @include('partials.icons', ['name' => 'home', 'class' => 'w-4 h-4']) Overview
            </a>
            <a class="sidebar-link {{ request()->routeIs('audits.*') ? 'is-active' : '' }}" href="{{ route('audits.index') }}">
                @include('partials.icons', ['name' => 'shield', 'class' => 'w-4 h-4']) Audits
                <span class="count">{{ $sidebarCounts['audits'] ?? '' }}</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}" href="{{ route('documents.index') }}">
                @include('partials.icons', ['name' => 'document', 'class' => 'w-4 h-4']) Content
                <span class="count">{{ $sidebarCounts['documents'] ?? '' }}</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('indexing.*') ? 'is-active' : '' }}" href="{{ route('indexing.index') }}">
                @include('partials.icons', ['name' => 'search', 'class' => 'w-4 h-4']) Indexing
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Account</div>
            <a class="sidebar-link {{ request()->routeIs('billing.*') ? 'is-active' : '' }}" href="{{ route('billing.index') }}">
                @include('partials.icons', ['name' => 'wallet', 'class' => 'w-4 h-4']) Billing
            </a>
            <a class="sidebar-link {{ request()->routeIs('affiliate.*') ? 'is-active' : '' }}" href="{{ route('affiliate.index') }}">
                @include('partials.icons', ['name' => 'users', 'class' => 'w-4 h-4']) Referrals
            </a>
            <a class="sidebar-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}" href="{{ route('settings.profile') }}">
                @include('partials.icons', ['name' => 'gear', 'class' => 'w-4 h-4']) Settings
            </a>
            <a class="sidebar-link {{ request()->routeIs('support.*') ? 'is-active' : '' }}" href="{{ route('support.index') }}">
                @include('partials.icons', ['name' => 'chat', 'class' => 'w-4 h-4']) Support
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Credits</div>
            <div class="px-3 py-2">
                <div class="flex items-baseline justify-between">
                    <span class="mono" style="font-size:1.25rem;">{{ number_format((float) auth()->user()->credits_balance, 0) }}</span>
                    <span class="faint" style="font-size:.74rem;">available</span>
                </div>
                <a href="{{ route('billing.index') }}" class="btn btn-ghost btn-sm btn-block mt-2">Add credits</a>
            </div>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Signed in</div>
            <div class="px-3">
                <div class="text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
                <div class="faint text-xs truncate">{{ auth()->user()->email }}</div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="btn btn-quiet btn-sm" type="submit">Log out</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="app-main">
        <div class="app-topbar">
            <div class="flex items-center gap-3 min-w-0">
                <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="app-sidebar" aria-label="Open navigation">
                    @include('partials.icons', ['name' => 'menu', 'class' => 'w-5 h-5'])
                </button>
                <div class="min-w-0">
                    <h1 class="text-xl md:text-2xl truncate">@yield('heading', 'Dashboard')</h1>
                    @hasSection('subheading')
                        <p class="muted text-sm truncate">@yield('subheading')</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                @yield('actions')
                <a href="{{ route('notifications.index') }}" class="btn btn-ghost btn-sm relative" aria-label="Notifications">
                    @include('partials.icons', ['name' => 'bell', 'class' => 'w-4 h-4'])
                    @if (($unreadNotifications ?? 0) > 0)
                        <span class="absolute -top-1 -right-1 min-w-4 h-4 px-1 rounded-full text-[10px] font-bold text-white bg-fail flex items-center justify-center">{{ $unreadNotifications }}</span>
                    @endif
                </a>
                <a href="{{ route('audits.create') }}" class="btn btn-primary btn-sm">
                    @include('partials.icons', ['name' => 'plus', 'class' => 'w-4 h-4']) New audit
                </a>
            </div>
        </div>

        @include('partials.flash')

        @yield('content')
    </div>
</div>

@stack('scripts')
</body>
</html>

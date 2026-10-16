#!/usr/bin/env python3
"""Write the shared Blade layouts."""
import os

ROOT = "/home/user/bets-site-ever/resources/views"
FILES = {}

FILES['layouts/app.blade.php'] = r'''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="{{ asset('brand/adready.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar no-print" id="app-sidebar">
        <div class="px-3 pb-4">
            <a href="{{ route('dashboard') }}" class="flex items-center">
                <img src="{{ asset('brand/adready-word.svg') }}" alt="{{ config('app.name') }}" width="150" height="32">
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
            <div class="sidebar-title">Account</div>
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
                <button class="nav-toggle lg:hidden" type="button" data-nav-toggle aria-expanded="false" aria-controls="app-sidebar" aria-label="Open navigation">
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
'''

FILES['layouts/admin.blade.php'] = r'''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') &middot; {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('brand/adready.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar no-print" id="app-sidebar">
        <div class="px-3 pb-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <img src="{{ asset('brand/adready.svg') }}" alt="" width="28" height="28">
                <span class="display text-lg">Control</span>
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Overview</div>
            <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
                @include('partials.icons', ['name' => 'chart', 'class' => 'w-4 h-4']) Dashboard
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
                @include('partials.icons', ['name' => 'users', 'class' => 'w-4 h-4']) Users
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.chat.*') ? 'is-active' : '' }}" href="{{ route('admin.chat.index') }}">
                @include('partials.icons', ['name' => 'chat', 'class' => 'w-4 h-4']) Live chat
                @if (($openChats ?? 0) > 0)
                    <span class="count" style="color:var(--color-fail);">{{ $openChats }}</span>
                @endif
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}" href="{{ route('admin.messages.index') }}">
                @include('partials.icons', ['name' => 'mail', 'class' => 'w-4 h-4']) Messages
                @if (($newMessages ?? 0) > 0)
                    <span class="count" style="color:var(--color-fail);">{{ $newMessages }}</span>
                @endif
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Revenue</div>
            <a class="sidebar-link {{ request()->routeIs('admin.plans.*') ? 'is-active' : '' }}" href="{{ route('admin.plans.index') }}">
                @include('partials.icons', ['name' => 'layers', 'class' => 'w-4 h-4']) Plans
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.gateways.*') ? 'is-active' : '' }}" href="{{ route('admin.gateways.index') }}">
                @include('partials.icons', ['name' => 'wallet', 'class' => 'w-4 h-4']) Gateways
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.invoices.*') ? 'is-active' : '' }}" href="{{ route('admin.invoices.index') }}">
                @include('partials.icons', ['name' => 'document', 'class' => 'w-4 h-4']) Invoices
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.coupons.*') ? 'is-active' : '' }}" href="{{ route('admin.coupons.index') }}">
                @include('partials.icons', ['name' => 'key', 'class' => 'w-4 h-4']) Coupons
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.affiliate.*') ? 'is-active' : '' }}" href="{{ route('admin.affiliate.index') }}">
                @include('partials.icons', ['name' => 'users', 'class' => 'w-4 h-4']) Affiliate
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Content</div>
            <a class="sidebar-link {{ request()->routeIs('admin.blog.*') ? 'is-active' : '' }}" href="{{ route('admin.blog.index') }}">
                @include('partials.icons', ['name' => 'pen', 'class' => 'w-4 h-4']) Blog
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.pages.*') ? 'is-active' : '' }}" href="{{ route('admin.pages.index') }}">
                @include('partials.icons', ['name' => 'layers', 'class' => 'w-4 h-4']) Pages
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">System</div>
            <a class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.index') }}">
                @include('partials.icons', ['name' => 'gear', 'class' => 'w-4 h-4']) Settings
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.prompts.*') ? 'is-active' : '' }}" href="{{ route('admin.prompts.index') }}">
                @include('partials.icons', ['name' => 'code', 'class' => 'w-4 h-4']) Prompts
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.audits.*') ? 'is-active' : '' }}" href="{{ route('admin.audits.index') }}">
                @include('partials.icons', ['name' => 'shield', 'class' => 'w-4 h-4']) Audits
            </a>
            <a class="sidebar-link {{ request()->routeIs('admin.logs.*') ? 'is-active' : '' }}" href="{{ route('admin.logs.index') }}">
                @include('partials.icons', ['name' => 'database', 'class' => 'w-4 h-4']) Audit log
            </a>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-title">Shortcuts</div>
            <a class="sidebar-link" href="{{ route('dashboard') }}">
                @include('partials.icons', ['name' => 'external', 'class' => 'w-4 h-4']) Open product
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sidebar-link w-full" type="submit">Log out</button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <div class="app-topbar">
            <div class="flex items-center gap-3 min-w-0">
                <button class="nav-toggle lg:hidden" type="button" data-nav-toggle aria-expanded="false" aria-controls="app-sidebar" aria-label="Open navigation">
                    @include('partials.icons', ['name' => 'menu', 'class' => 'w-5 h-5'])
                </button>
                <div class="min-w-0">
                    <h1 class="text-xl md:text-2xl truncate">@yield('heading', 'Control panel')</h1>
                    @hasSection('subheading')
                        <p class="muted text-sm truncate">@yield('subheading')</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                @yield('actions')
                <span class="chip">
                    @include('partials.icons', ['name' => 'shield', 'class' => 'w-3.5 h-3.5']) Super admin
                </span>
            </div>
        </div>

        @include('partials.flash')

        @yield('content')
    </div>
</div>

@stack('scripts')
</body>
</html>
'''

FILES['layouts/auth.blade.php'] = r'''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') &middot; {{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="{{ asset('brand/adready.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="grid-paper">
<div class="min-h-screen flex flex-col">
    <header class="no-print">
        <div class="wrap flex items-center justify-between h-16">
            <a href="{{ route('home') }}">
                <img src="{{ asset('brand/adready-word.svg') }}" alt="{{ config('app.name') }}" width="150" height="32">
            </a>
            <a href="{{ route('home') }}" class="btn btn-quiet btn-sm">Back to site</a>
        </div>
    </header>

    <main class="flex-1 flex items-start justify-center px-5 py-8 md:py-14">
        <div class="w-full" style="max-width: 420px;">
            @yield('content')
        </div>
    </main>

    <footer class="no-print pb-8">
        <p class="text-center faint text-xs">
            &copy; {{ date('Y') }} {{ config('app.name') }} &middot;
            <a href="{{ route('page.show', ['slug' => 'terms']) }}" class="underline">Terms</a> &middot;
            <a href="{{ route('page.show', ['slug' => 'privacy']) }}" class="underline">Privacy</a>
        </p>
    </footer>
</div>

@stack('scripts')
</body>
</html>
'''

FILES['partials/flash.blade.php'] = r'''
@if (session('status'))
    <div class="flash flash-success">{{ session('status') }}</div>
@endif

@if (session('error'))
    <div class="flash flash-error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="flash flash-error">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} layout files")

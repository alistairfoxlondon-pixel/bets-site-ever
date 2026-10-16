
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') &middot; {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/brand/adready.svg" type="image/svg+xml">
    @include('partials.critical')
    @include('partials.assets')
    @stack('head')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar no-print" id="app-sidebar">
        <div class="px-3 pb-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                <img src="/brand/adready.svg" alt="" width="28" height="28">
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
                <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="app-sidebar" aria-label="Open navigation">
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

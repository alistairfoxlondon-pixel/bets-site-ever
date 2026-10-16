
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') &middot; {{ config('app.name') }}</title>
    <meta name="robots" content="noindex">
    <link rel="icon" href="/brand/adready.svg" type="image/svg+xml">
    @include('partials.critical')
    @include('partials.assets')
    @stack('head')
</head>
<body class="grid-paper">
<div class="min-h-screen flex flex-col">
    <header class="no-print">
        <div class="wrap flex items-center justify-between h-16">
            <a href="{{ route('home') }}">
                <img src="/brand/adready-word.svg" alt="{{ config('app.name') }}" width="150" height="32">
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

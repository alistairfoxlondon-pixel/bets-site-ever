<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Not allowed · {{ config('app.name') }}</title>
    <link rel="icon" href="/brand/adready.svg" type="image/svg+xml">
    @include('partials.critical')
    @include('partials.assets')
</head>
<body>
<header class="site-header">
    <div class="wrap nav-shell">
        <a href="{{ url('/') }}"><img src="/brand/adready-word.svg" alt="{{ config('app.name') }}" width="168" height="36"></a>
    </div>
</header>
<main class="band">
    <div class="wrap-narrow">
        <p class="eyebrow mb-3">403</p>
        <h1 class="mb-3">You do not have access to this area</h1>
        <p class="lede mb-6">This section is limited to administrators. If you need access, ask the account owner.</p>
        <div class="flex flex-wrap gap-3">
            <a class="btn btn-primary" href="{{ url('/dashboard') }}">Open workspace</a>
            <a class="btn btn-ghost" href="{{ url('/') }}">Overview</a>
        </div>
    </div>
</main>
</body>
</html>

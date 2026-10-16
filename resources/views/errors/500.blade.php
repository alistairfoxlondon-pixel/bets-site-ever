<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Something went wrong · {{ config('app.name') }}</title>
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
        <p class="eyebrow mb-3">500</p>
        <h1 class="mb-3">The page failed to load</h1>
        <p class="lede mb-6">Try again in a moment. If it keeps happening, write to us and we will look at the logs.</p>
        <div class="flex flex-wrap gap-3">
            <a class="btn btn-primary" href="{{ url('/') }}">Back to overview</a>
            <a class="btn btn-ghost" href="{{ url('/contact') }}">Contact</a>
        </div>
    </div>
</main>
</body>
</html>

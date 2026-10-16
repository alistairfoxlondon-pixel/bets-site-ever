
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

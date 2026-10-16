
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

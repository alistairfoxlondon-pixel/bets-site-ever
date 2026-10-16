
@extends('layouts.marketing')

@section('title', $post->title.' | '.config('app.name'))
@section('description', $post->meta_description ?: Str::limit($post->excerpt, 155))

@section('content')

<section class="band band-tight">
    <div class="wrap-narrow">
        <a href="{{ route('blog.index') }}" class="muted text-sm no-underline">&larr; All articles</a>

        <div class="flex flex-wrap items-center gap-2 mt-5 mb-4">
            @foreach ((array) $post->tags as $tag)
                <span class="chip">{{ $tag }}</span>
            @endforeach
        </div>

        <h1 class="mb-4">{{ $post->title }}</h1>
        <p class="faint text-sm mono mb-7">
            {{ $post->published_at?->format('j F Y') }}
            @if ($post->author) &middot; {{ $post->author->name }} @endif
        </p>

        @if ($post->cover_image)
            <img src="{{ $post->cover_image }}" alt="" class="w-full mb-8" style="border-radius:var(--radius-md);">
        @endif

        <div class="prose-app">{!! $post->body !!}</div>

        <div class="rule" style="margin:3rem 0 1.5rem;"></div>

        <div class="flex flex-wrap gap-3 items-center justify-between">
            <p class="muted text-sm">Written for people who are about to apply.</p>
            <a href="{{ route('audit.landing') }}" class="btn btn-primary btn-sm">Audit a site</a>
        </div>
    </div>
</section>

@if ($related->isNotEmpty())
<section class="band band-line band-tight">
    <div class="wrap">
        <h2 class="mb-5">Read next</h2>
        <div class="grid grid-3" style="gap:1.25rem;">
            @foreach ($related as $item)
                <a href="{{ route('blog.show', $item->slug) }}" class="card" style="padding:1.4rem;text-decoration:none;">
                    <h3 class="text-lg mb-2">{{ $item->title }}</h3>
                    <p class="muted text-sm">{{ Str::limit($item->excerpt, 110) }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

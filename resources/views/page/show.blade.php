
@extends('layouts.marketing')

@section('title', $page->title.' | '.config('app.name'))
@section('description', $page->meta_description ?: Str::limit(strip_tags($page->body), 155))

@section('content')

<section class="band band-tight">
    <div class="wrap-narrow">
        <h1 class="mb-6">{{ $page->title }}</h1>
        <div class="prose-app">{!! $page->body !!}</div>
        <p class="faint text-xs mono mt-10">Last updated {{ $page->updated_at?->format('j F Y') }}</p>
    </div>
</section>

@endsection

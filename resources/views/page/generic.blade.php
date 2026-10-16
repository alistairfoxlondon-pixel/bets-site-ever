
@extends('layouts.marketing')

@section('title', 'Page | '.config('app.name'))

@section('content')

<section class="band">
    <div class="wrap-narrow text-center">
        <h1 class="mb-3">This page is being written</h1>
        <p class="lede mb-6">
            The legal pages are managed by the administrator. If you need a copy right now,
            ask us and we will send it.
        </p>
        <a href="{{ route('contact.create') }}" class="btn btn-primary">Contact the team</a>
    </div>
</section>

@endsection

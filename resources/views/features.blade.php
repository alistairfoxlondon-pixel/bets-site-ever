
@extends('layouts.marketing')

@section('title', 'What is inside | '.config('app.name'))
@section('description', 'A full look at the AdReady auditor, the writer, the indexing tool and the report.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="text-center" style="max-width:640px;margin-inline:auto;">
            <p class="eyebrow mb-3">Inside the product</p>
            <h1 class="mb-4">Four tools that share one engine</h1>
            <p class="lede">
                The auditor tells you what is missing, the writer fills the gap, the indexing tool
                publishes the result and the report proves the progress.
            </p>
        </div>

        <div class="grid grid-2 mt-9" style="gap:1.25rem;">
            @foreach ([
                ['icon' => 'shield', 'title' => 'Approval auditor', 'text' => 'Crawls up to 25 pages, checks 120 signals, scores six review areas and produces a realistic probability with a ranked fix list.'],
                ['icon' => 'pen', 'title' => 'AdSense content writer', 'text' => 'Long form articles with the title, meta description, FAQ block, internal links, external references and image prompts a publisher needs.'],
                ['icon' => 'link', 'title' => 'Affiliate writer', 'text' => 'The same depth with a clear affiliate disclosure in the first paragraph and tagged monetised links.'],
                ['icon' => 'search', 'title' => 'Quick indexing', 'text' => 'Push a URL to the Google Indexing API and to Bing through IndexNow, with a full submission history.'],
            ] as $tool)
                <div class="card" style="padding:1.6rem;">
                    <span class="icon-box">
                        @include('partials.icons', ['name' => $tool['icon'], 'class' => 'w-5 h-5'])
                    </span>
                    <h3 class="mb-2">{{ $tool['title'] }}</h3>
                    <p class="muted">{{ $tool['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="band band-soft">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:center;">
            <div class="visual-frame">
                @include('partials.visuals.comparison-sheet')
            </div>
            <div>
                <p class="eyebrow mb-3">Progress you can see</p>
                <h2 class="mb-3">Re-audit after every fix</h2>
                <p class="muted">
                    The point of a second audit is the difference. AdReady keeps every run, and the
                    comparison view lines up the score, the probability and each category so you
                    can see exactly what your work changed.
                </p>
                <ul class="mt-5 space-y-3" style="font-size:.95rem;">
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">@include('partials.icons', ['name' => 'chart', 'class' => 'w-4 h-4'])</span>
                        <span>Category by category movement between two runs</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">@include('partials.icons', ['name' => 'layers', 'class' => 'w-4 h-4'])</span>
                        <span>Findings that were closed and findings that appeared</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="icon-box" style="margin:0;flex:0 0 auto;">@include('partials.icons', ['name' => 'download', 'class' => 'w-4 h-4'])</span>
                        <span>Both reports as PDFs, side by side</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

@endsection

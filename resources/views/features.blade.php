
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
                The auditor highlights public-site signals, the writer helps draft new content,
                indexing tools record supported submissions, and reports track changes over time.
            </p>
        </div>

        <div class="grid grid-2 mt-9" style="gap:1.25rem;">
            @foreach ([
                ['icon' => 'shield', 'title' => 'Publisher-site auditor', 'text' => 'Crawls a limited number of public pages, reports evidence-linked findings and calculates a product-defined readiness index. It does not predict approval.'],
                ['icon' => 'pen', 'title' => 'Informational content writer', 'text' => 'Drafts articles with SEO metadata, FAQs, publisher-supplied internal links, references and image prompts for review.'],
                ['icon' => 'link', 'title' => 'Affiliate writer', 'text' => 'Creates affiliate-focused drafts with disclosure prompts and publisher-provided monetised links for editorial review.'],
                ['icon' => 'search', 'title' => 'Indexing requests', 'text' => 'Submit supported URLs to configured Google or Bing services and track the response; submission does not guarantee crawling or indexing.'],
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
                    comparison view lines up the readiness index and each assessed category so you
                    can see how the sampled findings changed between runs.
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

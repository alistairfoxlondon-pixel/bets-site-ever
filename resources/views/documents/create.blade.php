
@extends('layouts.app')

@section('title', 'Write an article')
@section('heading', 'Write an article')
@section('subheading', $type === 'affiliate_article' ? 'Affiliate article with a disclosure in the first paragraph' : 'AdSense ready informational article')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <form method="POST" action="{{ route('documents.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <div>
                <label class="label" for="keyword">Primary keyword</label>
                <input class="field" id="keyword" name="keyword" value="{{ old('keyword', $keyword) }}" placeholder="best hosting for a wordpress blog" required autofocus>
                <p class="hint mt-1">Write it the way a person would search for it.</p>
            </div>

            @if ($type === 'affiliate_article')
                <div>
                    <label class="label" for="product">Product or network to promote</label>
                    <input class="field" id="product" name="product" value="{{ old('product') }}" placeholder="Example: Hosting provider name">
                </div>
            @endif

            <div>
                <label class="label" for="url">Your site address</label>
                <input class="field" id="url" name="url" value="{{ old('url') }}" placeholder="example.com">
                <p class="hint mt-1">Helps the writer match the tone and the topic of your site.</p>
            </div>

            <div>
                <label class="label" for="site_context">What is your site about</label>
                <textarea class="field" id="site_context" name="site_context" rows="3" placeholder="A blog about home coffee brewing for beginners.">{{ old('site_context') }}</textarea>
            </div>

            <div class="form-grid">
                <div>
                    <label class="label" for="mode">Writer</label>
                    <select class="field" id="mode" name="mode">
                        @foreach ($modes as $mode)
                            <option value="{{ $mode['key'] }}" @selected(old('mode', 'standard') === $mode['key'])>
                                {{ $mode['label'] }} ({{ $mode['credits'] }} credits)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="language">Language</label>
                    <input class="field" id="language" name="language" value="{{ old('language', 'English') }}">
                </div>
            </div>

            <div>
                <label class="label" for="tone">Tone</label>
                <input class="field" id="tone" name="tone" value="{{ old('tone') }}" placeholder="plain, practical, slightly opinionated">
            </div>

            <div>
                <label class="label" for="extra_notes">Notes for the writer</label>
                <textarea class="field" id="extra_notes" name="extra_notes" rows="3" placeholder="Mention the migration guide, avoid pricing claims, keep the tone calm.">{{ old('extra_notes') }}</textarea>
            </div>

            <button class="btn btn-primary btn-lg btn-block" type="submit">Write the article</button>

            <p class="hint text-center">
                Balance after this: <strong class="mono">{{ number_format((float) auth()->user()->credits_balance, 0) }}</strong> credits.
            </p>
        </form>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">The house rules</h2>
        <ul class="space-y-3" style="font-size:.93rem;">
            @foreach ([
                'A real person voice, with experience in the details',
                'No dashes as punctuation, no hashtags, no emoji',
                'No invented statistics, studies or prices',
                'The primary keyword used where it belongs, never stuffed',
                'A clear disclosure in the first paragraph for affiliate pieces',
                'Ten question and answer FAQs',
                'Internal links, external references and image prompts',
                'Meta title, meta description and an excerpt',
            ] as $rule)
                <li class="flex gap-3">
                    @include('partials.icons', ['name' => 'check', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                    <span>{{ $rule }}</span>
                </li>
            @endforeach
        </ul>

        <div class="rule my-4"></div>

        <p class="text-sm muted">
            Articles are generated with the model you choose in the admin panel. The current
            model is <strong class="mono">{{ \App\Models\Setting::get('ai.model_writer_standard', 'standard') }}</strong>.
        </p>
    </div>
</div>

@endsection

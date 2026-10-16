
@extends('layouts.app')

@section('title', $document->title)
@section('heading', $document->title)
@section('subheading', $document->isAffiliate() ? 'Affiliate article' : 'AdSense article')

@section('actions')
    <a href="{{ route('documents.export', [$document, 'html']) }}" class="btn btn-ghost btn-sm">Download HTML</a>
    <a href="{{ route('documents.export', [$document, 'markdown']) }}" class="btn btn-ghost btn-sm">Markdown</a>
@endsection

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Article</h2>
            <span class="chip">{{ number_format($document->word_count) }} words</span>
        </div>

        <div class="prose-app" style="max-height:60vh;overflow:auto;padding-right:.5rem;">{!! $document->body !!}</div>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Export</h2>
            <p class="muted text-sm mb-3">Take the article straight to the platform you publish on.</p>
            <div class="space-y-2">
                @foreach ([
                    'wordpress' => 'WordPress',
                    'blogger' => 'Blogger',
                    'shopify' => 'Shopify',
                    'wix' => 'Wix',
                    'html' => 'HTML',
                    'markdown' => 'Markdown',
                ] as $format => $label)
                    <a href="{{ route('documents.export', [$document, $format]) }}" class="btn btn-ghost btn-block btn-sm">
                        @include('partials.icons', ['name' => 'download', 'class' => 'w-4 h-4']) {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Search details</h2>
            <dl class="space-y-3" style="font-size:.9rem;">
                <div>
                    <dt class="label">Meta title</dt>
                    <dd>{{ $document->meta_title }}</dd>
                </div>
                <div>
                    <dt class="label">Meta description</dt>
                    <dd class="muted">{{ $document->meta_description }}</dd>
                </div>
                <div>
                    <dt class="label">Primary keyword</dt>
                    <dd class="mono">{{ $document->keyword }}</dd>
                </div>
                @if (! empty($document->internal_links))
                    <div>
                        <dt class="label">Internal links</dt>
                        <dd class="muted">{{ count($document->internal_links) }} suggested</dd>
                    </div>
                @endif
                @if (! empty($document->external_links))
                    <div>
                        <dt class="label">External references</dt>
                        <dd class="muted">{{ count($document->external_links) }} suggested</dd>
                    </div>
                @endif
            </dl>
        </div>

        @if ($faq)
            <div class="panel">
                <h2 class="text-lg mb-3">FAQ block</h2>
                <div class="space-y-2">
                    @foreach ($faq as $item)
                        <details class="qa" style="border:0;border-top:1px solid var(--color-line);padding:0;">
                            <summary class="text-sm" style="padding:.7rem 0;">
                                {{ is_array($item) ? $item['question'] : $item }}
                            </summary>
                            <div class="a" style="padding-bottom:.7rem;font-size:.88rem;">
                                {{ is_array($item) ? $item['answer'] : '' }}
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('documents.destroy', $document) }}"
              onsubmit="return confirm('Delete this article permanently?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger btn-sm btn-block" type="submit">Delete this article</button>
        </form>
    </div>
</div>

@endsection

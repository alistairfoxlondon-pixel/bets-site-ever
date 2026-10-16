
@extends('layouts.app')

@section('title', 'Content')
@section('heading', 'Content')
@section('subheading', 'Every article the writer has produced for you')

@section('actions')
    <a href="{{ route('documents.create', ['type' => 'affiliate_article']) }}" class="btn btn-ghost btn-sm">Affiliate article</a>
    <a href="{{ route('documents.create', ['type' => 'adsense_article']) }}" class="btn btn-primary btn-sm">New article</a>
@endsection

@section('content')

<div class="panel">
    @if ($documents->isEmpty())
        <div class="text-center py-10">
            <span class="icon-box" style="margin:0 auto 1rem;">
                @include('partials.icons', ['name' => 'pen', 'class' => 'w-5 h-5'])
            </span>
            <h2 class="text-lg mb-2">No articles yet</h2>
            <p class="muted text-sm mb-5">Give the writer a keyword and it will produce a publish ready piece.</p>
            <a href="{{ route('documents.create') }}" class="btn btn-primary">Write an article</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Words</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $document)
                        <tr>
                            <td>
                                <a href="{{ route('documents.show', $document) }}" class="font-semibold">{{ $document->title }}</a>
                                <div class="faint text-xs truncate" style="max-width:320px;">{{ $document->keyword }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $document->isAffiliate() ? 'badge-warn' : 'badge-info' }}">
                                    {{ $document->isAffiliate() ? 'Affiliate' : 'AdSense' }}
                                </span>
                            </td>
                            <td class="num muted">{{ number_format($document->word_count) }}</td>
                            <td class="text-sm muted">{{ $document->created_at->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('documents.show', $document) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $documents->links() }}
        </div>
    @endif
</div>

@endsection

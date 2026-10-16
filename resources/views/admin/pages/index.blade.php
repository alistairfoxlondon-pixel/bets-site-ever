
@extends('layouts.admin')

@section('title', 'Pages')
@section('heading', 'Pages')
@section('subheading', 'The legal and static pages of the site')

@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm">New page</a>
@endsection

@section('content')

<div class="panel">
    @if ($pages->isEmpty())
        <p class="muted">No pages yet. The terms and privacy pages live here.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td><a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold">{{ $page->title }}</a></td>
                            <td class="mono text-xs">/{{ $page->slug }}</td>
                            <td>
                                <span class="badge {{ $page->status === 'published' ? 'badge-pass' : 'badge-muted' }}">{{ ucfirst($page->status) }}</span>
                            </td>
                            <td class="text-sm muted">{{ $page->updated_at->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('page.show', $page->slug) }}" class="btn btn-quiet btn-sm" target="_blank" rel="noopener">View</a>
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-quiet btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

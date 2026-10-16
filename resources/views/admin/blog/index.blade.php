
@extends('layouts.admin')

@section('title', 'Blog')
@section('heading', 'Blog')
@section('subheading', 'Articles published on the public site')

@section('actions')
    <a href="{{ route('admin.blog.create') }}" class="btn btn-primary btn-sm">Write an article</a>
@endsection

@section('content')

<div class="panel">
    @if ($posts->isEmpty())
        <p class="muted">No articles yet.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr><th>Title</th><th>Author</th><th>Status</th><th>Published</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($posts as $post)
                        <tr>
                            <td>
                                <a href="{{ route('admin.blog.edit', $post) }}" class="font-semibold">{{ $post->title }}</a>
                                <div class="faint text-xs mono">/{{ $post->slug }}</div>
                            </td>
                            <td class="text-sm muted">{{ $post->author?->name ?? 'system' }}</td>
                            <td>
                                <span class="badge {{ $post->status === 'published' ? 'badge-pass' : 'badge-muted' }}">{{ ucfirst($post->status) }}</span>
                            </td>
                            <td class="text-sm muted">{{ $post->published_at?->format('j M Y') ?? 'not published' }}</td>
                            <td class="text-right">
                                <a href="{{ route('blog.show', $post->slug) }}" class="btn btn-quiet btn-sm" target="_blank" rel="noopener">View</a>
                                <a href="{{ route('admin.blog.edit', $post) }}" class="btn btn-quiet btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

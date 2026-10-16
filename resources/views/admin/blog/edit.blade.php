
@extends('layouts.admin')

@section('title', $post->exists ? 'Edit article' : 'New article')
@section('heading', $post->exists ? 'Edit article' : 'New article')
@section('subheading', $post->exists ? $post->title : 'Write something worth reading')

@section('content')

<form method="POST" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
      class="grid grid-2" style="gap:1.25rem;align-items:start;">
    @csrf
    @if ($post->exists)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div class="panel">
            <div class="mb-3">
                <label class="label" for="title">Title</label>
                <input class="field" id="title" name="title" value="{{ old('title', $post->title) }}" required>
            </div>

            <div class="mb-3">
                <label class="label" for="excerpt">Excerpt</label>
                <textarea class="field" id="excerpt" name="excerpt" rows="2">{{ old('excerpt', $post->excerpt) }}</textarea>
            </div>

            <div>
                <label class="label" for="body">Body</label>
                <div class="editor-shell">
                    <textarea class="field" id="body" name="body" rows="18" style="border:0;border-radius:0;" data-editor="body">{{ old('body', $post->body) }}</textarea>
                </div>
                <p class="hint mt-1">The editor keeps clean HTML so the article reads well on every device.</p>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Publishing</h2>

            <div class="mb-3">
                <label class="label" for="status">Status</label>
                <select class="field" id="status" name="status">
                    <option value="draft" @selected(old('status', $post->status ?: 'draft') === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="label" for="published_at">Publish date</label>
                <input class="field" id="published_at" name="published_at" type="datetime-local"
                       value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
            </div>

            <div class="mb-3">
                <label class="label" for="tags">Tags, comma separated</label>
                <input class="field" id="tags" name="tags" value="{{ old('tags', is_array($post->tags) ? implode(', ', $post->tags) : '') }}">
            </div>

            <div class="mb-3">
                <label class="label" for="cover_image">Cover image address</label>
                <input class="field" id="cover_image" name="cover_image" value="{{ old('cover_image', $post->cover_image) }}">
            </div>

            <button class="btn btn-primary btn-block" type="submit">{{ $post->exists ? 'Save the article' : 'Create the article' }}</button>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Search engine</h2>
            <div class="mb-3">
                <label class="label" for="meta_title">Meta title</label>
                <input class="field" id="meta_title" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}">
            </div>
            <div>
                <label class="label" for="meta_description">Meta description</label>
                <textarea class="field" id="meta_description" name="meta_description" rows="3">{{ old('meta_description', $post->meta_description) }}</textarea>
            </div>
        </div>

    </div>
</form>

@if ($post->exists)
    <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" class="mt-4"
          onsubmit="return confirm('Delete this article?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger btn-sm" type="submit">Delete the article</button>
    </form>
@endif

@endsection

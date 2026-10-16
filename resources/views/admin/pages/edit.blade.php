
@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'New page')
@section('heading', $page->exists ? 'Edit page' : 'New page')

@section('content')

<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
      class="grid grid-2" style="gap:1.25rem;align-items:start;">
    @csrf
    @if ($page->exists)
        @method('PUT')
    @endif

    <div class="panel">
        <div class="mb-3">
            <label class="label" for="title">Title</label>
            <input class="field" id="title" name="title" value="{{ old('title', $page->title) }}" required>
        </div>

        <div>
            <label class="label" for="body">Body</label>
            <div class="editor-shell">
                <textarea class="field" id="body" name="body" rows="20" style="border:0;border-radius:0;" data-editor="body">{{ old('body', $page->body) }}</textarea>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="panel">
            <h2 class="text-lg mb-3">Publishing</h2>
            <div class="mb-3">
                <label class="label" for="status">Status</label>
                <select class="field" id="status" name="status">
                    <option value="draft" @selected(old('status', $page->status ?: 'draft') === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                </select>
            </div>
            <button class="btn btn-primary btn-block" type="submit">{{ $page->exists ? 'Save the page' : 'Create the page' }}</button>
        </div>

        <div class="panel">
            <h2 class="text-lg mb-3">Search engine</h2>
            <div class="mb-3">
                <label class="label" for="meta_title">Meta title</label>
                <input class="field" id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}">
            </div>
            <div>
                <label class="label" for="meta_description">Meta description</label>
                <textarea class="field" id="meta_description" name="meta_description" rows="3">{{ old('meta_description', $page->meta_description) }}</textarea>
            </div>
        </div>

    </div>
</form>

@if ($page->exists)
    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="mt-4"
          onsubmit="return confirm('Delete this page?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger btn-sm" type="submit">Delete the page</button>
    </form>
@endif

@endsection

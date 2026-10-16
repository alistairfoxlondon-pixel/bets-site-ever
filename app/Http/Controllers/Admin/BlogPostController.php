<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function index(): View
    {
        return view('admin.blog.index', [
            'posts' => BlogPost::query()->with('author')->orderByDesc('created_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.edit', [
            'post' => new BlogPost,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = BlogPost::uniqueSlug($data['title']);
        $data['author_id'] = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;

        BlogPost::create($data);

        return redirect()->route('admin.blog.index')->with('status', 'Post created.');
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.edit', [
            'post' => $post,
        ]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = BlogPost::uniqueSlug($data['title'], $post->id);
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? $post->published_at ?? now()) : null;

        $post->update($data);

        return redirect()->route('admin.blog.index')->with('status', 'Post updated.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $post->delete();

        return back()->with('status', 'Post deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:400'],
            'tags' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
        $data['published_at'] = $data['published_at'] ?? null;

        return $data;
    }
}

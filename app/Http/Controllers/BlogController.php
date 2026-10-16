<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $query = BlogPost::query()->published()->latest('published_at');

        if ($request->filled('tag')) {
            $query->where('tags', 'like', '%'.$request->string('tag').'%');
        }

        $posts = $query->paginate(9)->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'tag' => $request->string('tag')->toString(),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()->published()->where('slug', $slug)->firstOrFail();

        $post->increment('views');

        $related = BlogPost::query()
            ->published()
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
        ]);
    }
}

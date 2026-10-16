<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, Request $request): View
    {
        $page = Page::query()->published()->where('slug', $slug)->first();

        if (! $page) {
            abort(404);
        }

        return view('page.show', [
            'page' => $page,
        ]);
    }
}

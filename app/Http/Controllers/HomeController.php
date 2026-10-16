<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $posts = BlogPost::query()
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        $audited = (int) \App\Models\Audit::query()->where('status', 'completed')->count();
        $sites = (int) \App\Models\Audit::query()->where('status', 'completed')->distinct()->count('normalized_url');
        $users = (int) User::query()->count();

        return view('home', [
            'plans' => $plans,
            'posts' => $posts,
            'stats' => [
                'audits' => $audited,
                'sites' => max($sites, 0),
                'users' => $users,
            ],
        ]);
    }
}

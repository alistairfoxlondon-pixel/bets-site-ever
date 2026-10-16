<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\ContentDocument;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $audits = Audit::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $documents = ContentDocument::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(3)
            ->get();

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing', 'pending'])
            ->latest()
            ->first();

        $stats = [
            'audits' => Audit::query()->where('user_id', $user->id)->count(),
            'completed' => Audit::query()->where('user_id', $user->id)->where('status', 'completed')->count(),
            'documents' => ContentDocument::query()->where('user_id', $user->id)->count(),
            'words' => (int) ContentDocument::query()->where('user_id', $user->id)->sum('word_count'),
        ];

        $ready = Audit::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->orderByDesc('completed_at')
            ->first();

        $modes = collect(config('adready.modes'))
            ->map(fn (array $mode, string $key) => [
                'key' => $key,
                'label' => $mode['label'],
                'blurb' => $mode['blurb'],
                'credits' => $mode['credits'],
                'pages' => $mode['max_pages'],
                'featured' => (bool) ($mode['featured'] ?? false),
            ])
            ->values();

        return view('dashboard', [
            'audits' => $audits,
            'documents' => $documents,
            'subscription' => $subscription,
            'stats' => $stats,
            'ready' => $ready,
            'modes' => $modes,
        ]);
    }
}

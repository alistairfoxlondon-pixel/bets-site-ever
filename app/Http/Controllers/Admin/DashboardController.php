<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\ContentDocument;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\GatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, GatewayManager $gateways): View
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $revenue = (float) Invoice::query()->where('status', 'paid')->sum('amount');
        $revenueThisMonth = (float) Invoice::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', $startOfMonth)
            ->sum('amount');

        $users = User::query()->count();
        $newUsers = User::query()->where('created_at', '>=', $startOfMonth)->count();
        $activeSubscriptions = Subscription::query()->whereIn('status', ['active', 'trialing'])->count();
        $audits = Audit::query()->where('status', 'completed')->count();
        $documents = ContentDocument::query()->count();
        $words = (int) ContentDocument::query()->sum('word_count');

        $daily = Invoice::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->get(['paid_at', 'amount'])
            ->groupBy(fn ($invoice) => $invoice->paid_at->format('Y-m-d'))
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->all();

        $chart = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $chart[] = ['date' => $day, 'amount' => $daily[$day] ?? 0.0];
        }

        $topAudits = Audit::query()
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->limit(5)
            ->get();

        $recentPayments = PaymentTransaction::query()
            ->latest()
            ->limit(8)
            ->get();

        $byGateway = PaymentTransaction::query()
            ->where('status', 'paid')
            ->get()
            ->groupBy('gateway')
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->sortDesc();

        return view('admin.dashboard', [
            'revenue' => $revenue,
            'revenueThisMonth' => $revenueThisMonth,
            'users' => $users,
            'newUsers' => $newUsers,
            'activeSubscriptions' => $activeSubscriptions,
            'audits' => $audits,
            'documents' => $documents,
            'words' => $words,
            'chart' => $chart,
            'topAudits' => $topAudits,
            'recentPayments' => $recentPayments,
            'byGateway' => $byGateway,
            'plans' => Plan::query()->where('is_active', true)->count(),
            'gateways' => $gateways->enabled()->count(),
            'readyGateways' => $gateways->ready()->count(),
            'failedAudits' => Audit::query()->where('status', 'failed')->count(),
            'suspendedUsers' => User::query()->where('status', 'suspended')->count(),
        ]);
    }
}

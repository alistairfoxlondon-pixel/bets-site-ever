<?php

namespace App\Http\Controllers;

use App\Models\AffiliateEarning;
use App\Models\AffiliatePayout;
use App\Models\Plan;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function landing(Request $request): View
    {
        return view('affiliate.landing', [
            'rate' => AffiliateService::commissionRate(),
            'minimum' => AffiliateService::payoutMinimum(),
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function index(Request $request, AffiliateService $affiliate): View
    {
        $user = $request->user();

        $earnings = AffiliateEarning::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        $payouts = AffiliatePayout::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('affiliate.index', [
            'stats' => $affiliate->stats($user),
            'earnings' => $earnings,
            'payouts' => $payouts,
            'link' => $user->affiliateUrl(),
        ]);
    }

    public function payout(Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'string', 'in:paypal,bkash,nagad,bank'],
            'paypal_email' => ['nullable', 'email', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:120'],
            'routing_number' => ['nullable', 'string', 'max:120'],
        ]);

        $details = match ($data['method']) {
            'paypal' => ['email' => $data['paypal_email'] ?? ''],
            'bkash', 'nagad' => ['mobile' => $data['mobile'] ?? ''],
            default => [
                'bank_name' => $data['bank_name'] ?? '',
                'account_number' => $data['account_number'] ?? '',
                'routing_number' => $data['routing_number'] ?? '',
            ],
        };

        try {
            $affiliate->requestPayout($request->user(), (float) $data['amount'], $data['method'], $details);
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Withdrawal requested. The team reviews requests daily.');
    }
}

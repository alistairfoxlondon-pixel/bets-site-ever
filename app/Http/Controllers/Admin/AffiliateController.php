<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateEarning;
use App\Models\AffiliatePayout;
use App\Models\Setting;
use App\Models\User;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $payouts = AffiliatePayout::query()
            ->with('user')
            ->latest()
            ->paginate(20);

        $earnings = AffiliateEarning::query()
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        $top = User::query()
            ->withCount(['earnings', 'referrals'])
            ->has('earnings')
            ->orderByDesc('earnings_count')
            ->limit(10)
            ->get();

        return view('admin.affiliate.index', [
            'payouts' => $payouts,
            'earnings' => $earnings,
            'top' => $top,
            'rate' => AffiliateService::commissionRate(),
            'minimum' => AffiliateService::payoutMinimum(),
            'totalOwed' => (float) AffiliateEarning::query()->whereIn('status', ['pending', 'approved'])->sum('amount'),
        ]);
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payout_minimum' => ['required', 'numeric', 'min:0'],
            'cookie_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        Setting::put('affiliate.commission_rate', (float) $data['commission_rate']);
        Setting::put('affiliate.payout_minimum', (float) $data['payout_minimum']);
        Setting::put('affiliate.cookie_days', (int) $data['cookie_days']);

        return back()->with('status', 'Affiliate settings saved.');
    }

    public function payout(AffiliatePayout $payout): View
    {
        $payout->load('user');

        return view('admin.affiliate.payout', [
            'payout' => $payout,
        ]);
    }

    public function updatePayout(Request $request, AffiliatePayout $payout): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,paid,rejected'],
            'admin_note' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payout->update($data);

        if ($data['status'] === 'paid' && ! $payout->processed_at) {
            $payout->update(['processed_at' => now()]);

            AffiliateEarning::query()
                ->where('user_id', $payout->user_id)
                ->whereIn('status', ['approved', 'pending'])
                ->update(['status' => 'paid', 'paid_at' => now()]);
        }

        if ($data['status'] === 'rejected') {
            $payout->user()->increment('wallet_balance', (float) $payout->amount);
        }

        return back()->with('status', 'Withdrawal updated.');
    }
}

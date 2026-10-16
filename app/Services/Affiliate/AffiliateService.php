<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateClick;
use App\Models\AffiliateEarning;
use App\Models\AffiliatePayout;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class AffiliateService
{
    public const COOKIE = 'adready_ref';

    public static function commissionRate(): float
    {
        return (float) Setting::get('affiliate.commission_rate', 20);
    }

    public static function payoutMinimum(): float
    {
        return (float) Setting::get('affiliate.payout_minimum', 50);
    }

    public static function cookieTtlDays(): int
    {
        return (int) Setting::get('affiliate.cookie_days', 30);
    }

    /**
     * Record an inbound referral click and remember the code for 30 days.
     */
    public function trackClick(string $code, ?string $ip = null, ?string $userAgent = null, ?string $landingPath = null, ?string $referrer = null, ?string $country = null): ?AffiliateClick
    {
        $user = User::query()->where('referral_code', $code)->first();

        if (! $user) {
            return null;
        }

        $click = AffiliateClick::create([
            'referral_code' => $code,
            'user_id' => $user->id,
            'ip_hash' => $this->hashIp($ip),
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            'landing_path' => $landingPath ? mb_substr($landingPath, 0, 255) : null,
            'referrer' => $referrer ? mb_substr($referrer, 0, 255) : null,
            'country' => $country,
            'converted' => false,
        ]);

        Cookie::queue(Cookie::make(self::COOKIE, $code, self::cookieTtlDays() * 24 * 60));

        return $click;
    }

    public function pendingReferralCode(): ?string
    {
        $code = Cookie::get(self::COOKIE);

        return is_string($code) && filled($code) ? $code : null;
    }

    public function forgetReferral(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    public function referredUser(?string $code = null): ?User
    {
        $code = $code ?: $this->pendingReferralCode();

        if (blank($code)) {
            return null;
        }

        return User::query()->where('referral_code', $code)->first();
    }

    /**
     * Attach a new account to the referrer that sent them.
     */
    public function attachReferral(User $user, ?string $code = null): bool
    {
        if ($user->referred_by) {
            return false;
        }

        $referrer = $this->referredUser($code);

        if (! $referrer || $referrer->id === $user->id) {
            return false;
        }

        $user->forceFill([
            'referred_by' => $referrer->id,
            'referred_at' => now(),
        ])->save();

        AffiliateClick::query()
            ->where('referral_code', $referrer->referral_code)
            ->whereNull('converted_at')
            ->orderByDesc('id')
            ->limit(1)
            ->update(['converted' => true, 'converted_at' => now()]);

        return true;
    }

    public static function hasInvoiceCommission(int $invoiceId): bool
    {
        return AffiliateEarning::query()
            ->where('source_type', 'invoice')
            ->where('source_id', $invoiceId)
            ->exists();
    }

    /**
     * Pay the referrer their share of a paid invoice.
     */
    public static function recordCommissionForInvoice(Invoice $invoice): ?AffiliateEarning
    {
        $customer = $invoice->user;

        if (! $customer || ! $customer->referred_by) {
            return null;
        }

        $referrer = User::query()->find($customer->referred_by);

        if (! $referrer) {
            return null;
        }

        $rate = self::commissionRate();
        $base = (float) $invoice->amount;
        $amount = round($base * ($rate / 100), 2);

        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($referrer, $customer, $invoice, $amount, $rate, $base) {
            $earning = AffiliateEarning::create([
                'user_id' => $referrer->id,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'amount' => $amount,
                'rate' => $rate,
                'currency' => $invoice->currency ?: 'USD',
                'status' => 'pending',
                'description' => 'Commission from '.$customer->name.' on invoice '.$invoice->number,
            ]);

            $referrer->increment('wallet_balance', $amount);

            return $earning;
        });
    }

    public function stats(User $user): array
    {
        $earnings = AffiliateEarning::query()->where('user_id', $user->id);

        $clicks = AffiliateClick::query()->where('user_id', $user->id);

        return [
            'clicks' => $clicks->count(),
            'conversions' => (clone $clicks)->where('converted', true)->count(),
            'earnings_total' => (float) (clone $earnings)->sum('amount'),
            'earnings_pending' => (float) (clone $earnings)->where('status', 'pending')->sum('amount'),
            'earnings_approved' => (float) (clone $earnings)->where('status', 'approved')->sum('amount'),
            'earnings_paid' => (float) (clone $earnings)->where('status', 'paid')->sum('amount'),
            'referrals' => User::query()->where('referred_by', $user->id)->count(),
            'balance' => (float) $user->wallet_balance,
            'payout_minimum' => self::payoutMinimum(),
            'rate' => self::commissionRate(),
        ];
    }

    public function requestPayout(User $user, float $amount, string $method, array $details): AffiliatePayout
    {
        $minimum = self::payoutMinimum();

        if ($amount < $minimum) {
            throw new \InvalidArgumentException('The minimum withdrawal amount is '.$minimum.' USD.');
        }

        $available = (float) $user->wallet_balance;

        if ($amount > $available) {
            throw new \InvalidArgumentException('The requested amount is higher than your available balance.');
        }

        $payout = AffiliatePayout::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $user->currency ?: 'USD',
            'method' => $method,
            'account_details' => $details,
            'status' => 'pending',
        ]);

        $user->decrement('wallet_balance', $amount);

        return $payout;
    }
}

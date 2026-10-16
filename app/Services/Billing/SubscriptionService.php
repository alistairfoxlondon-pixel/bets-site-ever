<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Create a pending subscription plus its invoice for a plan cycle.
     */
    public function start(User $user, Plan $plan, string $cycle = 'monthly', ?string $couponCode = null): array
    {
        $currency = $user->currency ?: 'USD';
        $amount = $plan->priceFor($cycle, $currency);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('This plan is not available for the selected billing cycle.');
        }

        return DB::transaction(function () use ($user, $plan, $cycle, $amount, $currency, $couponCode) {
            $discount = 0;

            if ($couponCode) {
                $discount = $this->couponDiscount($couponCode, $amount);
            }

            $net = max(0, $amount - $discount);
            $amountBdt = strtoupper($currency) === 'BDT' ? $net : Currency::toBdt($net);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'billing_cycle' => $cycle,
                'amount' => $net,
                'currency' => $currency,
                'amount_bdt' => $amountBdt,
                'starts_at' => now(),
                'meta' => ['coupon' => $couponCode, 'discount' => $discount],
            ]);

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'number' => Invoice::nextNumber(),
                'description' => $plan->name.' plan ('.ucfirst($cycle).' subscription)',
                'amount' => $net,
                'currency' => $currency,
                'amount_bdt' => $amountBdt,
                'status' => 'pending',
                'period_start' => now(),
                'period_end' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                'meta' => ['plan' => $plan->slug, 'cycle' => $cycle, 'discount' => $discount],
            ]);

            return [$subscription, $invoice];
        });
    }

    /**
     * Mark an invoice paid and activate the linked subscription.
     */
    public function completePayment(Invoice $invoice, string $gateway, ?string $transactionId, array $payload = []): bool
    {
        return DB::transaction(function () use ($invoice, $gateway, $transactionId, $payload) {
            $invoice = Invoice::query()->lockForUpdate()->find($invoice->id);

            if (! $invoice) {
                return false;
            }

            if ($invoice->status === 'paid') {
                return true;
            }

            if (! in_array($invoice->status, ['pending', 'awaiting_review'], true)) {
                return false;
            }

            $subscription = $invoice->subscription_id
                ? Subscription::query()->lockForUpdate()->find($invoice->subscription_id)
                : null;

            if ($invoice->subscription_id && (! $subscription || $subscription->status !== 'pending')) {
                return false;
            }

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            PaymentTransaction::create([
                'user_id' => $invoice->user_id,
                'invoice_id' => $invoice->id,
                'gateway' => $gateway,
                'gateway_transaction_id' => $transactionId,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'amount_bdt' => $invoice->amount_bdt,
                'status' => 'paid',
                'payload' => $payload,
                'paid_at' => now(),
            ]);

            if ($subscription) {
                $this->activateSubscription($subscription, $invoice, $gateway);
            }

            $this->recordReferralCommission($invoice);

            return true;
        });
    }

    public function activateSubscription(Subscription $subscription, ?Invoice $invoice = null, ?string $gateway = null): void
    {
        $user = $subscription->user;

        if ($user) {
            $this->closeOtherSubscriptions($user, $subscription->id);
        }

        $plan = $subscription->plan;
        $cycle = $subscription->billing_cycle === 'yearly' ? 'yearly' : 'monthly';

        $trialEndsAt = $plan && $plan->trial_days > 0 ? now()->addDays($plan->trial_days) : null;

        $subscription->update([
            'status' => $trialEndsAt ? 'trialing' : 'active',
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => $subscription->starts_at ?: now(),
            'renews_at' => now()->add($cycle === 'yearly' ? 'year' : 'month'),
            'gateway' => $gateway ?? $subscription->gateway,
        ]);

        if ($plan && $plan->credits > 0) {
            if ($user) {
                $user->grantCredits(
                    $plan->credits,
                    'subscription',
                    $plan->name.' plan credits',
                    'subscription',
                    $subscription->id
                );
            }
        }
    }

    public function cancel(Subscription $subscription, bool $immediately = false): void
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'ends_at' => $immediately ? now() : $subscription->renews_at,
        ]);
    }

    public function closeOtherSubscriptions(User $user, ?int $exceptId = null): void
    {
        Subscription::query()
            ->where('user_id', $user->id)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereIn('status', ['active', 'trialing', 'pending'])
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }

    public function couponDiscount(string $code, float $amount): float
    {
        $coupon = \App\Models\Coupon::whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->where('is_active', true)
            ->first();

        if (! $coupon) {
            return 0;
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return 0;
        }

        if (! $coupon->isValid()) {
            return 0;
        }

        return $coupon->discountFor($amount);
    }

    /**
     * Pay the referrer a share of the first invoice of a referred account.
     */
    protected function recordReferralCommission(Invoice $invoice): void
    {
        try {
            $user = $invoice->user;

            if (! $user || ! $user->referred_by) {
                return;
            }

            $already = AffiliateService::hasInvoiceCommission($invoice->id);

            if ($already) {
                return;
            }

            AffiliateService::recordCommissionForInvoice($invoice);
        } catch (\Throwable $exception) {
            Log::error('Referral commission failed', [
                'invoice_id' => $invoice->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}

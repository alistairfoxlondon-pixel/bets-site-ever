#!/usr/bin/env python3
"""Write the billing, affiliate and settings controllers."""
import os

ROOT = "/home/user/bets-site-ever/app/Http/Controllers"
FILES = {}

FILES['BillingController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\GatewayManager;
use App\Services\Billing\SubscriptionService;
use App\Support\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, GatewayManager $gateways): View
    {
        $user = $request->user();

        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->get();

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing', 'pending'])
            ->latest()
            ->first();

        return view('billing.index', [
            'plans' => $plans,
            'subscription' => $subscription,
            'gateways' => $gateways->ready(),
            'credits' => (float) $user->credits_balance,
            'creditCost' => (int) config('adready.modes.standard.credits'),
            'currency' => $user->currency ?: 'USD',
        ]);
    }

    public function invoices(Request $request): View
    {
        $invoices = Invoice::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return view('billing.invoices', [
            'invoices' => $invoices,
        ]);
    }

    public function invoice(Request $request, Invoice $invoice, GatewayManager $gateways): View
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        return view('billing.invoice', [
            'invoice' => $invoice,
            'gateways' => $gateways->ready(),
        ]);
    }

    public function subscribe(Request $request, Plan $plan, string $cycle, SubscriptionService $service): RedirectResponse
    {
        abort_unless(in_array($cycle, ['monthly', 'yearly'], true), 404);

        if (! $plan->is_active) {
            return back()->with('error', 'That plan is not available.');
        }

        $coupon = $request->string('coupon')->toString();

        [$subscription, $invoice] = $service->start($request->user(), $plan, $cycle, $coupon ?: null);

        return redirect()->route('billing.checkout', $invoice)
            ->with('status', 'Order created. Choose how you want to pay.');
    }

    public function checkout(Request $request, Invoice $invoice, GatewayManager $gateways): View
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        return view('billing.checkout', [
            'invoice' => $invoice,
            'gateways' => $gateways->ready(),
        ]);
    }

    public function process(Request $request, Invoice $invoice, GatewayManager $gateways): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        if ($invoice->isPaid()) {
            return redirect()->route('billing.invoice', $invoice)->with('status', 'This invoice is already paid.');
        }

        $data = $request->validate([
            'gateway' => ['required', 'string', 'max:40'],
            'coupon' => ['nullable', 'string', 'max:40'],
        ]);

        $gateway = Gateway::query()->where('slug', $data['gateway'])->first();

        if (! $gateway || ! $gateway->is_enabled) {
            return back()->with('error', 'That payment method is not available.');
        }

        $driver = $gateways->driver($gateway);

        if (! $driver) {
            return back()->with('error', 'That payment method is not available.');
        }

        $intent = $driver->createPayment($invoice, $request->user());

        if (! $intent->successful) {
            return back()->with('error', $intent->error ?? 'The payment could not be started.');
        }

        if ($intent->redirectUrl) {
            if (filled($intent->payload['script'] ?? null)) {
                return response()->view('billing.redirect', [
                    'url' => $intent->redirectUrl,
                    'script' => $intent->payload['script'],
                ]);
            }

            return redirect()->away($intent->redirectUrl);
        }

        return view('billing.manual', [
            'invoice' => $invoice,
            'instructions' => $intent->instructions,
        ]);
    }

    public function callback(Request $request, string $gateway, Invoice $invoice, GatewayManager $gateways): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $driver = $gateways->driverForSlug($gateway);

        if (! $driver) {
            return redirect()->route('billing.invoice', $invoice)->with('error', 'Unknown payment method.');
        }

        $paid = $driver->handleCallback($request, $invoice);

        if ($paid) {
            return redirect()->route('billing.invoice', $invoice)
                ->with('status', 'Payment received. Your subscription is active.');
        }

        return redirect()->route('billing.invoice', $invoice)
            ->with('error', 'We could not confirm the payment. If you were charged, contact support and we will sort it out.');
    }

    public function webhook(Request $request, string $gateway, GatewayManager $gateways): Response
    {
        $driver = $gateways->driverForSlug($gateway);

        if (! $driver) {
            abort(404);
        }

        $verified = $driver->verifyWebhook($request);

        if ($verified === false) {
            abort(403, 'The signature could not be verified.');
        }

        $invoiceId = (int) ($request->input('invoice_id') ?: data_get($request->json(), 'data.metadata.invoice_id') ?: 0);
        $invoice = $invoiceId > 0 ? Invoice::query()->find($invoiceId) : null;

        if ($invoice) {
            $driver->handleCallback($request, $invoice);
        }

        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'ends_at' => $subscription->renews_at,
        ]);

        return back()->with('status', 'Subscription cancelled. You keep access until the end of the paid period.');
    }

    public function resume(Request $request, Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);

        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
            'ends_at' => null,
        ]);

        return back()->with('status', 'Subscription resumed.');
    }

    public function confirmManual(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $invoice->update([
            'status' => 'awaiting_review',
            'meta' => array_merge($invoice->meta ?? [], ['manual_submitted_at' => now()->toIso8601String()]),
        ]);

        return back()->with('status', 'Thanks. We will confirm the transfer and activate the subscription, usually within a few hours.');
    }
}
'''

FILES['AffiliateController.php'] = r'''<?php

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
            'link' => $user->affiliateLink,
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
'''

FILES['SettingsController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function profile(Request $request): View
    {
        return view('settings.profile', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'in:USD,BDT'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'email_notifications' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $user->fill($data);
        $user->email_notifications = $request->boolean('email_notifications');
        $user->save();

        return back()->with('status', 'Profile updated.');
    }

    public function security(Request $request): View
    {
        return view('settings.security', [
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withInput()->with('error', 'The current password is not correct.');
        }

        $user->password = $data['password'];
        $user->save();

        return back()->with('status', 'Password updated.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withInput()->with('error', 'The password is not correct.');
        }

        $user->email = $data['email'];
        $user->email_verified_at = null;
        $user->save();

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Email address updated. Check your inbox to confirm the new address.');
    }

    public function twoFactor(Request $request): View
    {
        return view('settings.two-factor', [
            'user' => $request->user(),
        ]);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two factor authentication is already on.');
        }

        $secret = TwoFactor::generateSecret();

        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = false;
        $user->save();

        return back()->with('status', 'Scan the code, then confirm with a code from your app.');
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! filled($user->two_factor_secret)) {
            return back()->with('error', 'Start the setup again.');
        }

        if (! TwoFactor::verify($user->two_factor_secret, $data['code'])) {
            return back()->with('error', 'That code was not accepted. Try again.');
        }

        $recoveryCodes = TwoFactor::recoveryCodes();

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return redirect()->route('settings.two-factor')
            ->with('status', 'Two factor authentication is on. Save your recovery codes somewhere safe.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->with('error', 'The password is not correct.');
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back()->with('status', 'Two factor authentication is off.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        $codes = TwoFactor::recoveryCodes();

        $user->two_factor_recovery_codes = $codes;
        $user->save();

        return back()
            ->with('status', 'A fresh set of recovery codes was generated.')
            ->with('recovery_codes', $codes);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->with('error', 'The password is not correct.');
        }

        $user->delete();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account was closed.');
    }
}
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} account controllers")

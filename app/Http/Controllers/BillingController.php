<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\GatewayManager;
use App\Services\Billing\SubscriptionService;
use App\Support\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function process(Request $request, Invoice $invoice, GatewayManager $gateways): RedirectResponse|View|Response
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        if ($invoice->isPaid()) {
            return redirect()->route('billing.invoice', $invoice)->with('status', 'This invoice is already paid.');
        }

        if (! in_array($invoice->status, ['pending', 'awaiting_review'], true)) {
            return redirect()->route('billing.invoice', $invoice)->with('error', 'This invoice is not available for payment.');
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

        if (! $driver || ! $driver->isConfigured() || $gateway->checkoutCurrency() === null) {
            return back()->with('error', 'That payment method is not available or configured for USD/BDT checkout.');
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
                    'checkoutModal' => (bool) ($intent->payload['checkout_modal'] ?? false),
                    'fallbackUrl' => route('billing.invoice', $invoice),
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

        if ($verified !== true) {
            abort(403, 'The signature could not be verified.');
        }

        $payload = $request->json()->all();
        $invoiceId = (int) (
            $request->input('invoice_id')
            ?: data_get($payload, 'data.object.metadata.invoice_id')
            ?: data_get($payload, 'data.object.client_reference_id')
            ?: data_get($payload, 'resource.purchase_units.0.custom_id')
            ?: data_get($payload, 'resource.purchase_units.0.reference_id')
            ?: data_get($payload, 'resource.custom_id')
            ?: data_get($payload, 'data.metadata.invoice_id')
            ?: data_get($payload, 'value1')
            ?: 0
        );
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

        $submitted = DB::transaction(function () use ($invoice): bool {
            $locked = Invoice::query()->lockForUpdate()->find($invoice->id);

            if (! $locked || ! in_array($locked->status, ['pending', 'awaiting_review'], true)) {
                return false;
            }

            $meta = $locked->meta ?? [];
            $meta['manual_submitted_at'] = now()->toIso8601String();
            $locked->update([
                'status' => 'awaiting_review',
                'meta' => $meta,
            ]);

            return true;
        });

        if (! $submitted) {
            return back()->with('error', 'This invoice is no longer available for manual payment review.');
        }

        return back()->with('status', 'Thanks. We will confirm the transfer and activate the subscription, usually within a few hours.');
    }
}

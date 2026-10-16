<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StripeGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'stripe';
    }

    public function name(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('secret_key'));
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Stripe is not configured yet. Add the secret key in the admin panel.');
        }

        $base = rtrim((string) $this->credential('base_url', 'https://api.stripe.com/v1'), '/');

        try {
            $response = $this->client()
                ->withToken((string) $this->credential('secret_key'))
                ->asForm()
                ->post($base.'/checkout/sessions', [
                    'mode' => 'payment',
                    'client_reference_id' => (string) $invoice->id,
                    'customer_email' => $user->email,
                    'success_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]).'&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.invoice', ['invoice' => $invoice->id]),
                    'metadata[invoice_id]' => (string) $invoice->id,
                    'metadata[user_id]' => (string) $user->id,
                    'line_items[0][quantity]' => 1,
                    'line_items[0][price_data][currency]' => strtolower($this->currency()),
                    'line_items[0][price_data][unit_amount]' => (int) round($this->amountFor($invoice) * 100),
                    'line_items[0][price_data][product_data][name]' => $invoice->description ?: 'Subscription payment',
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Stripe could not be reached. Please try again.');
        }

        if (! $response->successful()) {
            return PaymentIntent::failed('Stripe rejected the request: '.Str::limit((string) $response->body(), 200));
        }

        $url = $response->json('url');

        if (blank($url)) {
            return PaymentIntent::failed('Stripe did not return a checkout link.');
        }

        return PaymentIntent::redirect($url, $response->json('id'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $sessionId = $request->query('session_id') ?: $request->input('session_id');

        if (blank($sessionId)) {
            return false;
        }

        $base = rtrim((string) $this->credential('base_url', 'https://api.stripe.com/v1'), '/');

        try {
            $response = $this->client()
                ->withToken((string) $this->credential('secret_key'))
                ->get($base.'/checkout/sessions/'.$sessionId);
        } catch (\Throwable $exception) {
            $this->logError('callback', $exception);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        if ($response->json('payment_status') !== 'paid') {
            return false;
        }

        return $this->markPaid($invoice, (string) $response->json('payment_intent'), $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $secret = (string) $this->credential('webhook_secret');

        if (blank($secret)) {
            return null;
        }

        $signature = (string) $request->header('Stripe-Signature');
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (blank($timestamp) || $signatures === []) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }
}

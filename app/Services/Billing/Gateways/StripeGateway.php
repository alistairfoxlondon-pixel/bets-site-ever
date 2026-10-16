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

        $base = 'https://api.stripe.com/v1';

        try {
            $response = $this->client()
                ->withToken((string) $this->credential('secret_key'))
                ->asForm()
                ->post($base.'/checkout/sessions', [
                    'mode' => 'payment',
                    'client_reference_id' => (string) $invoice->id,
                    'customer_email' => $user->email,
                    'success_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]).'?session_id={CHECKOUT_SESSION_ID}',
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
        $sessionId = $request->query('session_id')
            ?: $request->input('session_id')
            ?: data_get($request->json()->all(), 'data.object.id');

        if (! is_string($sessionId) || ! preg_match('/^cs_(?:test|live)_[A-Za-z0-9]+$/D', $sessionId)) {
            return false;
        }

        $base = 'https://api.stripe.com/v1';

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

        $data = $response->json();
        $expectedAmount = (int) round($this->amountFor($invoice) * 100);
        $invoiceId = (string) $invoice->id;

        if (data_get($data, 'id') !== $sessionId
            || data_get($data, 'status') !== 'complete'
            || data_get($data, 'mode') !== 'payment'
            || data_get($data, 'payment_status') !== 'paid'
            || (string) data_get($data, 'client_reference_id') !== $invoiceId
            || (string) data_get($data, 'metadata.invoice_id') !== $invoiceId
            || strtoupper((string) data_get($data, 'currency')) !== $this->currency()
            || (int) data_get($data, 'amount_total', 0) !== $expectedAmount
            || blank(data_get($data, 'payment_intent'))) {
            return false;
        }

        return $this->markPaid($invoice, (string) data_get($data, 'payment_intent'), $data);
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

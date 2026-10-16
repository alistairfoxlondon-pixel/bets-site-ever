<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class PaymobGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'paymob';
    }

    public function name(): string
    {
        return 'Paymob';
    }

    public function isConfigured(): bool
    {
        // The current adapter does not yet implement Paymob's complete, verified
        // payment-key/iframe flow. Keep it out of checkout until that is finished.
        return false;
    }

    private function baseUrl(): string
    {
        return 'https://accept.paymob.com/api';
    }

    private function authToken(): ?string
    {
        try {
            $response = $this->client()
                ->post($this->baseUrl().'/auth/tokens', [
                    'api_key' => (string) $this->credential('public_key'),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('token', $exception);

            return null;
        }

        return $response->successful() ? $response->json('token') : null;
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Paymob is not configured yet. Add the public key and secret in the admin panel.');
        }

        $token = $this->authToken();

        if (blank($token)) {
            return PaymentIntent::failed('Paymob authentication failed. Check the credentials in the admin panel.');
        }

        try {
            $order = $this->client()
                ->withToken($token)
                ->post($this->baseUrl().'/ecommerce/orders', [
                    'amount_cents' => (int) round($this->amountFor($invoice) * 100),
                    'currency' => $this->currency(),
                    'merchant_order_id' => (string) $invoice->id,
                    'items' => [[
                        'name' => $invoice->description ?: 'Subscription',
                        'amount_cents' => (int) round($this->amountFor($invoice) * 100),
                        'quantity' => 1,
                    ]],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('order', $exception);

            return PaymentIntent::failed('Paymob could not be reached. Please try again.');
        }

        $orderId = $order->json('id');

        if (! $order->successful() || blank($orderId)) {
            return PaymentIntent::failed('Paymob did not return an order.');
        }

        try {
            $payment = $this->client()
                ->withToken($token)
                ->post($this->baseUrl().'/ecommerce/payments', [
                    'amount_cents' => (int) round($this->amountFor($invoice) * 100),
                    'currency' => $this->currency(),
                    'order_id' => $orderId,
                ]);
        } catch (\Throwable $exception) {
            $this->logError('payment', $exception);

            return PaymentIntent::failed('Paymob could not start the payment. Please try again.');
        }

        $token = (string) $payment->json('token');

        if (! $payment->successful() || ! preg_match('/^[A-Za-z0-9._~-]{1,2048}$/D', $token)) {
            return PaymentIntent::failed('Paymob did not return a valid payment token.');
        }

        $iframe = route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]);
        $scriptToken = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return PaymentIntent::redirect(
            $iframe.'?'.http_build_query(['payment_token' => $token]),
            $token,
            ['script' => '<script src="https://accept.paymob.com/api/accept.js" data-token="'.$scriptToken.'"></script>']
        );
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        // The current callback does not yet verify the Paymob transaction, invoice,
        // amount, and currency through the documented transaction endpoint.
        return false;
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}

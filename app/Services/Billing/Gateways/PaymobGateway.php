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
        return filled($this->credential('public_key')) && filled($this->credential('secret_key'));
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

        if (blank($orderId)) {
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
        $iframe = route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]);

        return PaymentIntent::redirect(
            $iframe.'?payment_token='.$token,
            $token,
            ['script' => '<script src="https://accept.paymob.com/api/accept.js" data-token="'.$token.'"></script>']
        );
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $transactionId = $request->input('data.order_id') ?: $request->query('order_id');

        if (blank($transactionId)) {
            return false;
        }

        $token = $this->authToken();

        if (blank($token)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withToken($token)
                ->get($this->baseUrl().'/ecommerce/orders/'.urlencode((string) $transactionId));
        } catch (\Throwable $exception) {
            $this->logError('fetch', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('pending') === true) {
            return false;
        }

        return $this->markPaid($invoice, (string) $transactionId, $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}

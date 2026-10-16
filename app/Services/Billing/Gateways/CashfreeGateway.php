<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class CashfreeGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'cashfree';
    }

    public function name(): string
    {
        return 'Cashfree';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('app_id')) && filled($this->credential('secret_key'));
    }

    private function baseUrl(): string
    {
        return $this->isLive() ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
    }

    private function apiVersion(): string
    {
        return '2023-08-01';
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Cashfree is not configured yet. Add the app id and secret key in the admin panel.');
        }

        try {
            $response = $this->client()
                ->withHeaders(['x-client-id' => (string) $this->credential('app_id'), 'x-client-secret' => (string) $this->credential('secret_key'), 'X-Cashfree-Version' => $this->apiVersion()])
                ->post($this->baseUrl().'/orders', [
                    'order_id' => $invoice->number,
                    'order_amount' => $this->amountFor($invoice),
                    'order_currency' => $this->currency(),
                    'customer_details' => [
                        'customer_id' => (string) $user->id,
                        'customer_name' => $user->name,
                        'customer_email' => $user->email,
                    ],
                    'order_meta' => [
                        'return_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                        'payment_methods' => 'cc,up,netbanking,app',
                    ],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Cashfree could not be reached. Please try again.');
        }

        $url = $response->json('payment_link');

        if (blank($url)) {
            return PaymentIntent::failed('Cashfree did not return a payment link.');
        }

        return PaymentIntent::redirect($url, $response->json('order_id'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $orderId = $request->input('order_id') ?: $request->query('order_id');

        if (blank($orderId)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withHeaders(['x-client-id' => (string) $this->credential('app_id'), 'x-client-secret' => (string) $this->credential('secret_key'), 'X-Cashfree-Version' => $this->apiVersion()])
                ->get($this->baseUrl().'/orders/'.urlencode($orderId));
        } catch (\Throwable $exception) {
            $this->logError('fetch', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('order_status') !== 'PAID') {
            return false;
        }

        return $this->markPaid($invoice, (string) $response->json('payment_id'), $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $signature = (string) $request->header('x-webhook-signature');
        $body = $request->getContent();

        if (blank($signature)) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $body, (string) $this->credential('secret_key'), true));

        return hash_equals($expected, $signature);
    }
}

<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayPalGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'paypal';
    }

    public function name(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('client_id')) && filled($this->credential('client_secret'));
    }

    private function baseUrl(): string
    {
        return $this->isLive()
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function accessToken(): ?string
    {
        try {
            $response = $this->client()
                ->withBasicAuth((string) $this->credential('client_id'), (string) $this->credential('client_secret'))
                ->asForm()
                ->post($this->baseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);
        } catch (\Throwable $exception) {
            $this->logError('token', $exception);

            return null;
        }

        return $response->successful() ? $response->json('access_token') : null;
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('PayPal is not configured yet. Add the API credentials in the admin panel.');
        }

        $token = $this->accessToken();

        if (blank($token)) {
            return PaymentIntent::failed('PayPal authentication failed. Check the credentials in the admin panel.');
        }

        $currency = $this->currency();
        $value = number_format($this->amountFor($invoice), 2, '.', '');

        try {
            $response = $this->client()
                ->withToken($token)
                ->post($this->baseUrl().'/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => (string) $invoice->id,
                        'custom_id' => (string) $invoice->id,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => $value,
                        ],
                    ]],
                    'application_context' => [
                        'return_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                        'cancel_url' => route('billing.invoice', ['invoice' => $invoice->id]),
                    ],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('PayPal could not be reached. Please try again.');
        }

        if (! $response->successful()) {
            $this->logError('create', new \RuntimeException('PayPal returned HTTP '.$response->status()));

            return PaymentIntent::failed('PayPal rejected the checkout request. Please check the gateway configuration.');
        }

        $approve = collect($response->json('links') ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (blank($approve)) {
            return PaymentIntent::failed('PayPal did not return an approval link.');
        }

        return PaymentIntent::redirect($approve, $response->json('id'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $token = $request->query('token') ?: $request->input('token');

        if (blank($token)) {
            return false;
        }

        $access = $this->accessToken();

        if (blank($access)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withToken($access)
                ->post($this->baseUrl().'/v2/checkout/orders/'.$token.'/capture');
        } catch (\Throwable $exception) {
            $this->logError('capture', $exception);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $data = $response->json();
        $status = data_get($data, 'status');
        $purchaseUnit = data_get($data, 'purchase_units.0', []);
        $capture = data_get($purchaseUnit, 'payments.captures.0', []);
        $captureAmount = (float) data_get($capture, 'amount.value', 0);
        $captureCurrency = strtoupper((string) data_get($capture, 'amount.currency_code', ''));
        $referenceId = (string) data_get($purchaseUnit, 'reference_id', '');
        $customId = (string) data_get($purchaseUnit, 'custom_id', '');
        $expectedAmount = $this->amountFor($invoice);

        if ($status !== 'COMPLETED'
            || data_get($capture, 'status') !== 'COMPLETED'
            || ($referenceId !== (string) $invoice->id && $customId !== (string) $invoice->id)
            || $captureCurrency !== $this->currency()
            || abs($captureAmount - $expectedAmount) >= 0.01) {
            return false;
        }

        $captureId = data_get($capture, 'id');

        if (blank($captureId)) {
            return false;
        }

        return $this->markPaid($invoice, (string) $captureId, $data);
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $access = $this->accessToken();

        if (blank($access)) {
            return null;
        }

        try {
            $response = $this->client()
                ->withToken($access)
                ->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
                    'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                    'cert_url' => $request->header('PAYPAL-CERT-URL'),
                    'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                    'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                    'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                    'webhook_id' => (string) $this->credential('webhook_id'),
                    'webhook_event' => $request->json()->all(),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('webhook', $exception);

            return null;
        }

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }
}

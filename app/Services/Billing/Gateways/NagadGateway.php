<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class NagadGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'nagad';
    }

    public function name(): string
    {
        return 'Nagad';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('app_key')) && filled($this->credential('app_secret'));
    }

    private function baseUrl(): string
    {
        return $this->isLive()
            ? 'https://api.sandbox.nagad.com.bd/aggregate'
            : 'https://api.sandbox.nagad.com.bd/aggregate';
    }

    private function token(): ?string
    {
        try {
            $response = $this->client()
                ->withHeaders([
                    'X-APIM-Key' => (string) $this->credential('app_key'),
                    'X-Secret' => (string) $this->credential('app_secret'),
                ])
                ->post($this->baseUrl().'/common/v1.0.0/authentication', [
                    'appKey' => (string) $this->credential('app_key'),
                    'appSecret' => (string) $this->credential('app_secret'),
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
            return PaymentIntent::failed('Nagad is not configured yet. Add the app key and secret in the admin panel.');
        }

        $token = $this->token();

        if (blank($token)) {
            return PaymentIntent::failed('Nagad authentication failed. Check the credentials in the admin panel.');
        }

        try {
            $response = $this->client()
                ->withToken($token)
                ->withHeaders(['X-APIM-Key' => (string) $this->credential('app_key')])
                ->post($this->baseUrl().'/common/v1.0.0/merchant/merchant/charges', [
                    'merchantInvoiceNumber' => $invoice->number,
                    'customerMobileNumber' => (string) $this->credential('merchant_number', '01700000000'),
                    'amount' => number_format($this->amountFor($invoice), 2, '.', ''),
                    'currency' => $this->currency(),
                    'callbackURL' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Nagad could not be reached. Please try again.');
        }

        $redirect = $response->json('chargeUrl');

        if (blank($redirect)) {
            return PaymentIntent::failed('Nagad did not return a payment link.');
        }

        return PaymentIntent::redirect($redirect, $response->json('paymentId'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $status = $request->query('status') ?: $request->input('status');

        if ($status !== 'Success') {
            return false;
        }

        $paymentId = $request->query('paymentId') ?: $request->input('paymentId');

        if (blank($paymentId)) {
            return false;
        }

        $token = $this->token();

        if (blank($token)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withToken($token)
                ->withHeaders(['X-APIM-Key' => (string) $this->credential('app_key')])
                ->get($this->baseUrl().'/common/v1.0.0/merchant/merchant/charges/'.$paymentId);
        } catch (\Throwable $exception) {
            $this->logError('verify', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('status') !== 'Success') {
            return false;
        }

        return $this->markPaid($invoice, (string) $paymentId, $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}

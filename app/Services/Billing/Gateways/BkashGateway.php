<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BkashGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'bkash';
    }

    public function name(): string
    {
        return 'bKash';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('app_key')) && filled($this->credential('app_secret'));
    }

    private function baseUrl(): string
    {
        return $this->isLive()
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
    }

    private function token(): ?string
    {
        try {
            $response = $this->client()
                ->withHeaders([
                    'username' => (string) $this->credential('app_key'),
                    'password' => (string) $this->credential('app_secret'),
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl().'/tokenized/checkout/token/grant', [
                    'app_key' => (string) $this->credential('app_key'),
                    'app_secret' => (string) $this->credential('app_secret'),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('token', $exception);

            return null;
        }

        return $response->successful() ? $response->json('id_token') : null;
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('bKash is not configured yet. Add the app key and secret in the admin panel.');
        }

        $token = $this->token();

        if (blank($token)) {
            return PaymentIntent::failed('bKash authentication failed. Check the credentials in the admin panel.');
        }

        $amount = number_format($this->amountFor($invoice), 2, '.', '');

        try {
            $response = $this->client()
                ->withToken($token)
                ->withHeaders(['X-APP-Key' => (string) $this->credential('app_key')])
                ->post($this->baseUrl().'/tokenized/checkout/create', [
                    'mode' => '0011',
                    'payerType' => '01',
                    'payerNumber' => '01' . (string) $this->credential('merchant_number'),
                    'callbackURL' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                    'amount' => $amount,
                    'currency' => $this->currency(),
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => (string) $invoice->id,
                    'merchantInvoiceNumber' => $invoice->number,
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('bKash could not be reached. Please try again.');
        }

        $bkashUrl = $response->json('bkashURL');

        if (blank($bkashUrl)) {
            return PaymentIntent::failed('bKash did not return a payment link.');
        }

        return PaymentIntent::redirect($bkashUrl, $response->json('paymentID'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $status = $request->query('status') ?: $request->input('status');

        if ($status !== 'success') {
            return false;
        }

        $paymentId = $request->query('paymentID') ?: $request->input('paymentID');

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
                ->withHeaders(['X-APP-Key' => (string) $this->credential('app_key')])
                ->get($this->baseUrl().'/tokenized/checkout/payment/'.$paymentId);
        } catch (\Throwable $exception) {
            $this->logError('execute', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('status') !== 'Completed') {
            return false;
        }

        return $this->markPaid($invoice, (string) $paymentId, $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}

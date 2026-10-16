<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class FlutterwaveGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'flutterwave';
    }

    public function name(): string
    {
        return 'Flutterwave';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('public_key')) && filled($this->credential('secret_key'));
    }

    private function baseUrl(): string
    {
        return 'https://api.flutterwave.com/v3';
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Flutterwave is not configured yet. Add the public key and secret in the admin panel.');
        }

        try {
            $response = $this->client()
                ->withToken((string) $this->credential('secret_key'))
                ->post($this->baseUrl().'/payments', [
                    'tx_ref' => $invoice->number,
                    'amount' => $this->amountFor($invoice),
                    'currency' => $this->currency(),
                    'redirect_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                    'customer' => [
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'customizations' => [
                        'title' => config('app.name'),
                        'description' => $invoice->description ?: 'Subscription',
                    ],
                    'meta' => ['invoice_id' => (string) $invoice->id],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Flutterwave could not be reached. Please try again.');
        }

        $url = $response->json('data.link');

        if (blank($url)) {
            return PaymentIntent::failed('Flutterwave did not return a payment link.');
        }

        return PaymentIntent::redirect($url, $response->json('data.id'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $status = $request->query('status') ?: $request->input('status');
        $transactionId = $request->query('tx_ref') ?: $request->input('tx_ref');

        if ($status !== 'completed' || blank($transactionId)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withToken((string) $this->credential('secret_key'))
                ->get($this->baseUrl().'/transactions/'.urlencode((string) $transactionId).'/verify');
        } catch (\Throwable $exception) {
            $this->logError('verify', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('data.status') !== 'successful') {
            return false;
        }

        return $this->markPaid($invoice, (string) $response->json('data.id'), $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $secretHash = (string) $request->header('verif-hash');

        if (blank($secretHash)) {
            return false;
        }

        return hash_equals((string) $this->credential('secret_hash', $this->credential('secret_key')), $secretHash);
    }
}

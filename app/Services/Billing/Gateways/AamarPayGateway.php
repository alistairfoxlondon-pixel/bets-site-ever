<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class AamarPayGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'aamarpay';
    }

    public function name(): string
    {
        return 'Rocket (AamarPay)';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('store_id')) && filled($this->credential('store_password'));
    }

    private function baseUrl(): string
    {
        return $this->isLive() ? 'https://pay.aamarPay.com' : 'https://sandbox.aamarPay.com';
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Rocket is not configured yet. Add the store id and password in the admin panel.');
        }

        try {
            $response = $this->client()
                ->asForm()
                ->post($this->baseUrl().'/payment/create', [
                    'store_id' => (string) $this->credential('store_id'),
                    'store_passwd' => (string) $this->credential('store_password'),
                    'total_amount' => number_format($this->amountFor($invoice), 2, '.', ''),
                    'currency' => $this->currency(),
                    'tran_id' => $invoice->number,
                    'success_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                    'fail_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                    'cancel_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                    'ipn_url' => route('billing.webhook', ['gateway' => $this->slug()]),
                    'cus_name' => $user->name,
                    'cus_email' => $user->email,
                    'cus_phone' => (string) ($user->phone ?? ''),
                    'cus_add1' => (string) ($user->country ?? 'Bangladesh'),
                    'product_name' => $invoice->description ?: 'Subscription',
                    'product_category' => 'Subscription',
                    'product_profile' => 'general',
                    'value1' => (string) $invoice->id,
                    'value2' => (string) $user->id,
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Rocket could not be reached. Please try again.');
        }

        $url = $response->json('payment_url') ?? $response->json('redirect_url');

        if (blank($url)) {
            return PaymentIntent::failed('Rocket did not return a checkout link.');
        }

        return PaymentIntent::redirect($url, $invoice->number);
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $status = $request->input('status') ?: $request->query('status');

        if ($status !== 'success') {
            return false;
        }

        $tranId = $request->input('tran_id') ?: $request->query('tran_id');
        $hash = $request->input('hash') ?: $request->query('hash');
        $amount = (float) ($request->input('amount') ?: $request->query('amount'));

        $expected = hash_hmac('sha256', $tranId.$amount.$this->credential('store_password'), (string) $this->credential('store_id'));

        if (blank($hash) || ! hash_equals($expected, (string) $hash)) {
            return false;
        }

        return $this->markPaid($invoice, (string) $tranId, $request->all());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}

<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class SslCommerzGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'sslcommerz';
    }

    public function name(): string
    {
        return 'SSLCommerz';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('store_id')) && filled($this->credential('store_password'));
    }

    private function baseUrl(): string
    {
        return $this->isLive() ? 'https://securepay.sslcommerz.com' : 'https://sandbox.sslcommerz.com';
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('SSLCommerz is not configured yet. Add the store id and password in the admin panel.');
        }

        try {
            $response = $this->client()
                ->asForm()
                ->post($this->baseUrl().'/gwprocess/v4/api.php', [
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
                    'shipping_method' => 'NO',
                    'product_name' => $invoice->description ?: 'Subscription',
                    'product_category' => 'Subscription',
                    'product_profile' => 'general',
                    'value1' => (string) $invoice->id,
                    'value2' => (string) $user->id,
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('SSLCommerz could not be reached. Please try again.');
        }

        $url = $response->json('GatewayPageURL');

        if (! $response->successful() || ! $this->isProviderCheckoutUrl($url)) {
            return PaymentIntent::failed('SSLCommerz did not return a checkout link.');
        }

        return PaymentIntent::redirect($url, $invoice->number);
    }

    private function isProviderCheckoutUrl(mixed $url): bool
    {
        if (! is_string($url) || strlen($url) > 2048) {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && in_array(strtolower((string) ($parts['host'] ?? '')), ['sandbox.sslcommerz.com', 'securepay.sslcommerz.com'], true)
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $validation = $request->input('val_id') ?: $request->query('val_id');

        if (blank($validation)) {
            return false;
        }

        try {
            $response = $this->client()
                ->asForm()
                ->post($this->baseUrl().'/validator/api/validationserverAPI.php', [
                    'val_id' => $validation,
                    'store_id' => (string) $this->credential('store_id'),
                    'store_passwd' => (string) $this->credential('store_password'),
                    'format' => 'json',
                ]);
        } catch (\Throwable $exception) {
            $this->logError('validate', $exception);

            return false;
        }

        $data = $response->json();
        $status = strtoupper((string) data_get($data, 'status'));
        $amount = (float) data_get($data, 'amount', 0);
        $currency = strtoupper((string) data_get($data, 'currency', ''));
        $transactionId = (string) data_get($data, 'bank_tran_id', '');

        if (! $response->successful()
            || ! in_array($status, ['VALID', 'VALIDATED'], true)
            || (string) data_get($data, 'tran_id', '') !== $invoice->number
            || (string) data_get($data, 'value1', '') !== (string) $invoice->id
            || $currency !== $this->currency()
            || abs($amount - $this->amountFor($invoice)) >= 0.01
            || blank($transactionId)) {
            return false;
        }

        return $this->markPaid($invoice, $transactionId, $data);
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}

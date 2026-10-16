<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class RazorpayGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'razorpay';
    }

    public function name(): string
    {
        return 'Razorpay';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('key_id')) && filled($this->credential('key_secret'));
    }

    private function baseUrl(): string
    {
        return 'https://api.razorpay.com/v1';
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Razorpay is not configured yet. Add the key id and key secret in the admin panel.');
        }

        try {
            $response = $this->client()
                ->withBasicAuth((string) $this->credential('key_id'), (string) $this->credential('key_secret'))
                ->post($this->baseUrl().'/orders', [
                    'amount' => (int) round($this->amountFor($invoice) * 100),
                    'currency' => $this->currency(),
                    'receipt' => $invoice->number,
                    'notes' => ['invoice_id' => (string) $invoice->id],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Razorpay could not be reached. Please try again.');
        }

        $orderId = $response->json('id');

        if (! $response->successful() || ! is_string($orderId) || ! preg_match('/^order_[A-Za-z0-9]+$/D', $orderId)) {
            return PaymentIntent::failed('Razorpay did not return a valid checkout order.');
        }

        $script = $this->checkoutScript($invoice, $orderId, $user);

        return PaymentIntent::redirect(
            route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
            $orderId,
            ['script' => $script, 'checkout_modal' => true]
        );
    }

    private function checkoutScript(Invoice $invoice, string $orderId, User $user): string
    {
        $options = [
            'key' => (string) $this->credential('key_id'),
            'amount' => (int) round($this->amountFor($invoice) * 100),
            'currency' => $this->currency(),
            'name' => config('app.name'),
            'description' => $invoice->description ?: 'Subscription',
            'order_id' => $orderId,
            'prefill' => ['name' => $user->name, 'email' => $user->email],
            'callback_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
            'redirect' => true,
        ];

        $encodedOptions = json_encode(
            $options,
            JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
        );

        return '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>'
            .'<script>(function(){var options='.$encodedOptions.';var instance=null;window.adreadyOpenCheckout=function(){if(!instance){instance=new Razorpay(options);}instance.open();};window.adreadyOpenCheckout();})();</script>';
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $razorpaySignature = (string) $request->input('razorpay_signature');
        $razorpayOrderId = (string) $request->input('razorpay_order_id');
        $razorpayPaymentId = (string) $request->input('razorpay_payment_id');

        if (! preg_match('/^order_[A-Za-z0-9]+$/D', $razorpayOrderId)
            || ! preg_match('/^pay_[A-Za-z0-9]+$/D', $razorpayPaymentId)
            || ! preg_match('/^[a-f0-9]{64}$/iD', $razorpaySignature)) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $razorpayOrderId.'|'.$razorpayPaymentId,
            (string) $this->credential('key_secret'),
        );

        if (! hash_equals($expected, $razorpaySignature)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withBasicAuth((string) $this->credential('key_id'), (string) $this->credential('key_secret'))
                ->get($this->baseUrl().'/payments/'.$razorpayPaymentId);
        } catch (\Throwable $exception) {
            $this->logError('verify-payment', $exception);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $data = $response->json();
        $expectedAmount = (int) round($this->amountFor($invoice) * 100);

        if (data_get($data, 'id') !== $razorpayPaymentId
            || data_get($data, 'status') !== 'captured'
            || data_get($data, 'order_id') !== $razorpayOrderId
            || (int) data_get($data, 'amount', 0) !== $expectedAmount
            || strtoupper((string) data_get($data, 'currency', '')) !== $this->currency()
            || (string) data_get($data, 'notes.invoice_id', '') !== (string) $invoice->id) {
            return false;
        }

        return $this->markPaid($invoice, $razorpayPaymentId, $data);
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $secret = (string) $this->credential('webhook_secret');
        $signature = (string) $request->header('X-Razorpay-Signature');

        if (blank($secret) || blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}

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

        if (blank($orderId)) {
            return PaymentIntent::failed('Razorpay did not return an order.');
        }

        $script = $this->checkoutScript($invoice, $orderId, $user);

        return PaymentIntent::redirect(
            route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
            $orderId,
            ['script' => $script]
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
            'handler' => [
                'action' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
            ],
        ];

        return '<script src="https://checkout.razorpay.com/v1/checkout.js"></script>'
            ."<script>new Razorpay(".json_encode($options, JSON_UNESCAPED_SLASHES).").open();</script>";
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $razorpaySignature = (string) $request->input('razorpay_signature');
        $razorpayOrderId = (string) $request->input('razorpay_order_id');
        $razorpayPaymentId = (string) $request->input('razorpay_payment_id');

        if (blank($razorpaySignature) || blank($razorpayOrderId)) {
            return false;
        }

        $expected = hash_hmac('sha256', $razorpayOrderId.'|'.(string) $this->credential('key_secret'), (string) $this->credential('key_secret'));

        if (! hash_equals($expected, $razorpaySignature)) {
            return false;
        }

        return $this->markPaid($invoice, $razorpayPaymentId, $request->all());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $signature = (string) $request->header('X-Razorpay-Signature');
        $body = $request->getContent();

        if (blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $body, (string) $this->credential('key_secret'));

        return hash_equals($expected, $signature);
    }
}

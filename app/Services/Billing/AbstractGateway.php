<?php

namespace App\Services\Billing;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Currency;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractGateway implements PaymentGateway
{
    public function __construct(protected readonly Gateway $gateway)
    {
    }

    protected function credential(string $key, mixed $default = null): mixed
    {
        return $this->gateway->credential($key, $default);
    }

    protected function isLive(): bool
    {
        return ! $this->gateway->is_test_mode;
    }

    protected function currency(): string
    {
        return $this->gateway->checkoutCurrency()
            ?? throw new \InvalidArgumentException('This payment method has no supported USD or BDT settlement currency configured.');
    }

    protected function amountFor(Invoice $invoice): float
    {
        $gatewayCurrency = $this->currency();
        $invoiceCurrency = strtoupper((string) $invoice->currency);

        if ($gatewayCurrency === $invoiceCurrency) {
            return (float) $invoice->amount;
        }

        if ($gatewayCurrency === 'BDT' && $invoiceCurrency === 'USD') {
            return (float) $invoice->amount_bdt;
        }

        if ($gatewayCurrency === 'USD' && $invoiceCurrency === 'BDT') {
            return Currency::fromBdt((float) $invoice->amount);
        }

        throw new \InvalidArgumentException('The invoice currency cannot be converted to this gateway currency.');
    }

    protected function client(): PendingRequest
    {
        return Http::asJson()->acceptJson()->timeout(30)->connectTimeout(15);
    }

    /**
     * Mark an invoice as paid and hand the money to the subscription service.
     */
    protected function markPaid(Invoice $invoice, ?string $transactionId = null, array $payload = []): bool
    {
        return app(SubscriptionService::class)->completePayment($invoice, $this->slug(), $transactionId, $payload);
    }

    protected function logError(string $context, \Throwable $exception): void
    {
        Log::error('Payment gateway error', [
            'gateway' => $this->slug(),
            'context' => $context,
            'message' => $exception->getMessage(),
        ]);
    }
}

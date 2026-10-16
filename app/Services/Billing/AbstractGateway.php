<?php

namespace App\Services\Billing;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\User;
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
        return strtoupper((string) ($this->credential('currency') ?: 'USD'));
    }

    protected function amountFor(Invoice $invoice): float
    {
        if ($this->currency() === 'BDT') {
            return (float) $invoice->amount_bdt;
        }

        return (float) $invoice->amount;
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

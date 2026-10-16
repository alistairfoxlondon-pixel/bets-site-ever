<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class ManualGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'manual';
    }

    public function name(): string
    {
        return 'Bank transfer';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('account_name')) || filled($this->credential('instructions'));
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        $raw = $this->credential('instructions');

        if (is_string($raw) && trim($raw) !== '') {
            $instructions = [trim($raw)];
        } elseif (is_array($raw) && $raw !== []) {
            $instructions = array_values(array_filter(array_map('strval', $raw)));
        } else {
            $instructions = [
                'Transfer the invoice total and include invoice number '.$invoice->number.' as the reference.',
            ];
        }

        if ($name = $this->credential('account_name')) {
            $instructions[] = 'Account name: '.$name;
        }
        if ($bank = $this->credential('bank_name')) {
            $instructions[] = 'Bank: '.$bank;
        }
        if ($number = $this->credential('account_number')) {
            $instructions[] = 'Account number: '.$number;
        }

        return PaymentIntent::manual($instructions, $invoice->number);
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        return false;
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}

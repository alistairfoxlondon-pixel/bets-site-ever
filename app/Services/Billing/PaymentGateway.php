<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\User;

interface PaymentGateway
{
    public function slug(): string;

    public function name(): string;

    /**
     * Whether the gateway has everything it needs to take money.
     */
    public function isConfigured(): bool;

    /**
     * Start a payment and return the details the browser needs.
     */
    public function createPayment(Invoice $invoice, User $user): PaymentIntent;

    /**
     * Handle a callback or webhook. Returns true when the invoice was paid.
     */
    public function handleCallback(\Illuminate\Http\Request $request, Invoice $invoice): bool;

    /**
     * Verify an incoming webhook signature. Returns null when the gateway
     * does not support signed webhooks.
     */
    public function verifyWebhook(\Illuminate\Http\Request $request): ?bool;
}

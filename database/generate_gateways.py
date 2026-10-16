#!/usr/bin/env python3
"""Generate the payment gateway drivers."""
import os

DIR = "/home/user/bets-site-ever/app/Services/Billing/Gateways"
os.makedirs(DIR, exist_ok=True)

FILES = {}

FILES['StripeGateway.php'] = r'''<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StripeGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'stripe';
    }

    public function name(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('secret_key'));
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Stripe is not configured yet. Add the secret key in the admin panel.');
        }

        $base = rtrim((string) $this->credential('base_url', 'https://api.stripe.com/v1'), '/');

        try {
            $response = $this->client()
                ->withToken((string) $this->credential('secret_key'))
                ->asForm()
                ->post($base.'/checkout/sessions', [
                    'mode' => 'payment',
                    'client_reference_id' => (string) $invoice->id,
                    'customer_email' => $user->email,
                    'success_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]).'&session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.invoice', ['invoice' => $invoice->id]),
                    'metadata[invoice_id]' => (string) $invoice->id,
                    'metadata[user_id]' => (string) $user->id,
                    'line_items[0][quantity]' => 1,
                    'line_items[0][price_data][currency]' => strtolower($this->currency()),
                    'line_items[0][price_data][unit_amount]' => (int) round($this->amountFor($invoice) * 100),
                    'line_items[0][price_data][product_data][name]' => $invoice->description ?: 'Subscription payment',
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Stripe could not be reached. Please try again.');
        }

        if (! $response->successful()) {
            return PaymentIntent::failed('Stripe rejected the request: '.Str::limit((string) $response->body(), 200));
        }

        $url = $response->json('url');

        if (blank($url)) {
            return PaymentIntent::failed('Stripe did not return a checkout link.');
        }

        return PaymentIntent::redirect($url, $response->json('id'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $sessionId = $request->query('session_id') ?: $request->input('session_id');

        if (blank($sessionId)) {
            return false;
        }

        $base = rtrim((string) $this->credential('base_url', 'https://api.stripe.com/v1'), '/');

        try {
            $response = $this->client()
                ->withToken((string) $this->credential('secret_key'))
                ->get($base.'/checkout/sessions/'.$sessionId);
        } catch (\Throwable $exception) {
            $this->logError('callback', $exception);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        if ($response->json('payment_status') !== 'paid') {
            return false;
        }

        return $this->markPaid($invoice, (string) $response->json('payment_intent'), $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $secret = (string) $this->credential('webhook_secret');

        if (blank($secret)) {
            return null;
        }

        $signature = (string) $request->header('Stripe-Signature');
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (blank($timestamp) || $signatures === []) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
'''

FILES['PayPalGateway.php'] = r'''<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayPalGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'paypal';
    }

    public function name(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('client_id')) && filled($this->credential('client_secret'));
    }

    private function baseUrl(): string
    {
        return $this->isLive()
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function accessToken(): ?string
    {
        try {
            $response = $this->client()
                ->asForm()
                ->post($this->baseUrl().'/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ])
                ->withBasicAuth((string) $this->credential('client_id'), (string) $this->credential('client_secret'));
        } catch (\Throwable $exception) {
            $this->logError('token', $exception);

            return null;
        }

        return $response->successful() ? $response->json('access_token') : null;
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('PayPal is not configured yet. Add the API credentials in the admin panel.');
        }

        $token = $this->accessToken();

        if (blank($token)) {
            return PaymentIntent::failed('PayPal authentication failed. Check the credentials in the admin panel.');
        }

        $currency = $this->currency();
        $value = number_format($this->amountFor($invoice), 2, '.', '');

        try {
            $response = $this->client()
                ->withToken($token)
                ->post($this->baseUrl().'/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => (string) $invoice->id,
                        'custom_id' => (string) $invoice->id,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => $value,
                        ],
                    ]],
                    'application_context' => [
                        'return_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                        'cancel_url' => route('billing.invoice', ['invoice' => $invoice->id]),
                    ],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('PayPal could not be reached. Please try again.');
        }

        $approve = collect($response->json('links') ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (blank($approve)) {
            return PaymentIntent::failed('PayPal did not return an approval link.');
        }

        return PaymentIntent::redirect($approve, $response->json('id'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $token = $request->query('token') ?: $request->input('token');

        if (blank($token)) {
            return false;
        }

        $access = $this->accessToken();

        if (blank($access)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withToken($access)
                ->post($this->baseUrl().'/v2/checkout/orders/'.$token.'/capture');
        } catch (\Throwable $exception) {
            $this->logError('capture', $exception);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $status = $response->json('status');

        if (! in_array($status, ['COMPLETED', 'APPROVED'], true)) {
            return false;
        }

        $captureId = collect($response->json('purchase_units.0.payments.captures') ?? [])->first()['id'] ?? $token;

        return $this->markPaid($invoice, (string) $captureId, $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $access = $this->accessToken();

        if (blank($access)) {
            return null;
        }

        try {
            $response = $this->client()
                ->withToken($access)
                ->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
                    'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
                    'cert_url' => $request->header('PAYPAL-CERT-URL'),
                    'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
                    'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
                    'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
                    'webhook_id' => (string) $this->credential('webhook_id'),
                    'webhook_event' => $request->json()->all(),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('webhook', $exception);

            return null;
        }

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }
}
'''

FILES['BkashGateway.php'] = r'''<?php

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
'''

FILES['NagadGateway.php'] = r'''<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class NagadGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'nagad';
    }

    public function name(): string
    {
        return 'Nagad';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('app_key')) && filled($this->credential('app_secret'));
    }

    private function baseUrl(): string
    {
        return $this->isLive()
            ? 'https://api.sandbox.nagad.com.bd/aggregate'
            : 'https://api.sandbox.nagad.com.bd/aggregate';
    }

    private function token(): ?string
    {
        try {
            $response = $this->client()
                ->withHeaders([
                    'X-APIM-Key' => (string) $this->credential('app_key'),
                    'X-Secret' => (string) $this->credential('app_secret'),
                ])
                ->post($this->baseUrl().'/common/v1.0.0/authentication', [
                    'appKey' => (string) $this->credential('app_key'),
                    'appSecret' => (string) $this->credential('app_secret'),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('token', $exception);

            return null;
        }

        return $response->successful() ? $response->json('access_token') : null;
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Nagad is not configured yet. Add the app key and secret in the admin panel.');
        }

        $token = $this->token();

        if (blank($token)) {
            return PaymentIntent::failed('Nagad authentication failed. Check the credentials in the admin panel.');
        }

        try {
            $response = $this->client()
                ->withToken($token)
                ->withHeaders(['X-APIM-Key' => (string) $this->credential('app_key')])
                ->post($this->baseUrl().'/common/v1.0.0/merchant/merchant/charges', [
                    'merchantInvoiceNumber' => $invoice->number,
                    'customerMobileNumber' => (string) $this->credential('merchant_number', '01700000000'),
                    'amount' => number_format($this->amountFor($invoice), 2, '.', ''),
                    'currency' => $this->currency(),
                    'callbackURL' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Nagad could not be reached. Please try again.');
        }

        $redirect = $response->json('chargeUrl');

        if (blank($redirect)) {
            return PaymentIntent::failed('Nagad did not return a payment link.');
        }

        return PaymentIntent::redirect($redirect, $response->json('paymentId'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $status = $request->query('status') ?: $request->input('status');

        if ($status !== 'Success') {
            return false;
        }

        $paymentId = $request->query('paymentId') ?: $request->input('paymentId');

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
                ->withHeaders(['X-APIM-Key' => (string) $this->credential('app_key')])
                ->get($this->baseUrl().'/common/v1.0.0/merchant/merchant/charges/'.$paymentId);
        } catch (\Throwable $exception) {
            $this->logError('verify', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('status') !== 'Success') {
            return false;
        }

        return $this->markPaid($invoice, (string) $paymentId, $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}
'''

FILES['SslCommerzGateway.php'] = r'''<?php

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

        if (blank($url)) {
            return PaymentIntent::failed('SSLCommerz did not return a checkout link.');
        }

        return PaymentIntent::redirect($url, $invoice->number);
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

        if (! $response->successful() || $response->json('status') !== 'VALID') {
            return false;
        }

        return $this->markPaid($invoice, (string) $response->json('bank_tran_id'), $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}
'''

FILES['AamarPayGateway.php'] = r'''<?php

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
'''

FILES['RazorpayGateway.php'] = r'''<?php

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
'''

FILES['CashfreeGateway.php'] = r'''<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class CashfreeGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'cashfree';
    }

    public function name(): string
    {
        return 'Cashfree';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('app_id')) && filled($this->credential('secret_key'));
    }

    private function baseUrl(): string
    {
        return $this->isLive() ? 'https://api.cashfree.com/pg' : 'https://sandbox.cashfree.com/pg';
    }

    private function apiVersion(): string
    {
        return '2023-08-01';
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Cashfree is not configured yet. Add the app id and secret key in the admin panel.');
        }

        try {
            $response = $this->client()
                ->withHeaders(['x-client-id' => (string) $this->credential('app_id'), 'x-client-secret' => (string) $this->credential('secret_key'), 'X-Cashfree-Version' => $this->apiVersion()])
                ->post($this->baseUrl().'/orders', [
                    'order_id' => $invoice->number,
                    'order_amount' => $this->amountFor($invoice),
                    'order_currency' => $this->currency(),
                    'customer_details' => [
                        'customer_id' => (string) $user->id,
                        'customer_name' => $user->name,
                        'customer_email' => $user->email,
                    ],
                    'order_meta' => [
                        'return_url' => route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]),
                        'payment_methods' => 'cc,up,netbanking,app',
                    ],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('create', $exception);

            return PaymentIntent::failed('Cashfree could not be reached. Please try again.');
        }

        $url = $response->json('payment_link');

        if (blank($url)) {
            return PaymentIntent::failed('Cashfree did not return a payment link.');
        }

        return PaymentIntent::redirect($url, $response->json('order_id'));
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $orderId = $request->input('order_id') ?: $request->query('order_id');

        if (blank($orderId)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withHeaders(['x-client-id' => (string) $this->credential('app_id'), 'x-client-secret' => (string) $this->credential('secret_key'), 'X-Cashfree-Version' => $this->apiVersion()])
                ->get($this->baseUrl().'/orders/'.urlencode($orderId));
        } catch (\Throwable $exception) {
            $this->logError('fetch', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('order_status') !== 'PAID') {
            return false;
        }

        return $this->markPaid($invoice, (string) $response->json('payment_id'), $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        $signature = (string) $request->header('x-webhook-signature');
        $body = $request->getContent();

        if (blank($signature)) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $body, (string) $this->credential('secret_key'), true));

        return hash_equals($expected, $signature);
    }
}
'''

FILES['PaymobGateway.php'] = r'''<?php

namespace App\Services\Billing\Gateways;

use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\AbstractGateway;
use App\Services\Billing\PaymentIntent;
use Illuminate\Http\Request;

class PaymobGateway extends AbstractGateway
{
    public function slug(): string
    {
        return 'paymob';
    }

    public function name(): string
    {
        return 'Paymob';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('public_key')) && filled($this->credential('secret_key'));
    }

    private function baseUrl(): string
    {
        return 'https://accept.paymob.com/api';
    }

    private function authToken(): ?string
    {
        try {
            $response = $this->client()
                ->post($this->baseUrl().'/auth/tokens', [
                    'api_key' => (string) $this->credential('public_key'),
                ]);
        } catch (\Throwable $exception) {
            $this->logError('token', $exception);

            return null;
        }

        return $response->successful() ? $response->json('token') : null;
    }

    public function createPayment(Invoice $invoice, User $user): PaymentIntent
    {
        if (! $this->isConfigured()) {
            return PaymentIntent::failed('Paymob is not configured yet. Add the public key and secret in the admin panel.');
        }

        $token = $this->authToken();

        if (blank($token)) {
            return PaymentIntent::failed('Paymob authentication failed. Check the credentials in the admin panel.');
        }

        try {
            $order = $this->client()
                ->withToken($token)
                ->post($this->baseUrl().'/ecommerce/orders', [
                    'amount_cents' => (int) round($this->amountFor($invoice) * 100),
                    'currency' => $this->currency(),
                    'merchant_order_id' => (string) $invoice->id,
                    'items' => [[
                        'name' => $invoice->description ?: 'Subscription',
                        'amount_cents' => (int) round($this->amountFor($invoice) * 100),
                        'quantity' => 1,
                    ]],
                ]);
        } catch (\Throwable $exception) {
            $this->logError('order', $exception);

            return PaymentIntent::failed('Paymob could not be reached. Please try again.');
        }

        $orderId = $order->json('id');

        if (blank($orderId)) {
            return PaymentIntent::failed('Paymob did not return an order.');
        }

        try {
            $payment = $this->client()
                ->withToken($token)
                ->post($this->baseUrl().'/ecommerce/payments', [
                    'amount_cents' => (int) round($this->amountFor($invoice) * 100),
                    'currency' => $this->currency(),
                    'order_id' => $orderId,
                ]);
        } catch (\Throwable $exception) {
            $this->logError('payment', $exception);

            return PaymentIntent::failed('Paymob could not start the payment. Please try again.');
        }

        $token = (string) $payment->json('token');
        $iframe = route('billing.callback', ['gateway' => $this->slug(), 'invoice' => $invoice->id]);

        return PaymentIntent::redirect(
            $iframe.'?payment_token='.$token,
            $token,
            ['script' => '<script src="https://accept.paymob.com/api/accept.js" data-token="'.$token.'"></script>']
        );
    }

    public function handleCallback(Request $request, Invoice $invoice): bool
    {
        $transactionId = $request->input('data.order_id') ?: $request->query('order_id');

        if (blank($transactionId)) {
            return false;
        }

        $token = $this->authToken();

        if (blank($token)) {
            return false;
        }

        try {
            $response = $this->client()
                ->withToken($token)
                ->get($this->baseUrl().'/ecommerce/orders/'.urlencode((string) $transactionId));
        } catch (\Throwable $exception) {
            $this->logError('fetch', $exception);

            return false;
        }

        if (! $response->successful() || $response->json('pending') === true) {
            return false;
        }

        return $this->markPaid($invoice, (string) $transactionId, $response->json());
    }

    public function verifyWebhook(Request $request): ?bool
    {
        return null;
    }
}
'''

FILES['FlutterwaveGateway.php'] = r'''<?php

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
'''

FILES['ManualGateway.php'] = r'''<?php

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
        $instructions = (array) $this->credential('instructions', [
            'Send the amount to the account listed below and include invoice number '.$invoice->number.' in the reference.',
        ]);

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
'''

for name, content in FILES.items():
    with open(os.path.join(DIR, name), 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} gateways")

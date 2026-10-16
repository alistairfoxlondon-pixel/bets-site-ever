<?php

namespace Tests\Unit;

use App\Models\Gateway;
use App\Models\Invoice;
use App\Services\Billing\Gateways\AamarPayGateway;
use App\Services\Billing\Gateways\PaymobGateway;
use Illuminate\Http\Request;
use Tests\TestCase;

class UnverifiedGatewaysTest extends TestCase
{
    public function test_aamarpay_stays_out_of_checkout_and_cannot_confirm_a_browser_callback(): void
    {
        $gateway = new AamarPayGateway(new Gateway(['slug' => 'aamarpay']));
        $request = Request::create('/billing/callback', 'GET', [
            'status' => 'success',
            'tran_id' => 'invoice-1',
            'amount' => '10.00',
            'hash' => 'unverified',
        ]);

        $this->assertFalse($gateway->isConfigured());
        $this->assertFalse($gateway->handleCallback($request, new Invoice));
        $this->assertNull($gateway->verifyWebhook($request));
    }

    public function test_paymob_stays_out_of_checkout_and_cannot_confirm_a_browser_callback(): void
    {
        $gateway = new PaymobGateway(new Gateway(['slug' => 'paymob']));
        $request = Request::create('/billing/callback', 'GET', [
            'order_id' => 'unverified-order',
            'payment_token' => 'unverified-token',
        ]);

        $this->assertFalse($gateway->isConfigured());
        $this->assertFalse($gateway->handleCallback($request, new Invoice));
        $this->assertNull($gateway->verifyWebhook($request));
    }
}

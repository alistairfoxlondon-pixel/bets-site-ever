<?php

namespace App\Services\Billing;

use App\Models\Gateway;
use App\Services\Billing\Gateways\AamarPayGateway;
use App\Services\Billing\Gateways\BkashGateway;
use App\Services\Billing\Gateways\CashfreeGateway;
use App\Services\Billing\Gateways\FlutterwaveGateway;
use App\Services\Billing\Gateways\ManualGateway;
use App\Services\Billing\Gateways\NagadGateway;
use App\Services\Billing\Gateways\PaymobGateway;
use App\Services\Billing\Gateways\PayPalGateway;
use App\Services\Billing\Gateways\RazorpayGateway;
use App\Services\Billing\Gateways\SslCommerzGateway;
use App\Services\Billing\Gateways\StripeGateway;

class GatewayManager
{
    /**
     * @var array<string, class-string<PaymentGateway>>
     */
    protected array $drivers = [
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
        'bkash' => BkashGateway::class,
        'nagad' => NagadGateway::class,
        'sslcommerz' => SslCommerzGateway::class,
        'aamarpay' => AamarPayGateway::class,
        'rocket' => AamarPayGateway::class,
        'razorpay' => RazorpayGateway::class,
        'paymob' => PaymobGateway::class,
        'cashfree' => CashfreeGateway::class,
        'flutterwave' => FlutterwaveGateway::class,
        'manual' => ManualGateway::class,
    ];

    public function driver(?Gateway $gateway): ?PaymentGateway
    {
        if (! $gateway) {
            return null;
        }

        $class = $this->drivers[$gateway->slug] ?? null;

        if (! $class) {
            return null;
        }

        return new $class($gateway);
    }

    public function driverForSlug(string $slug): ?PaymentGateway
    {
        return $this->driver(Gateway::query()->where('slug', $slug)->first());
    }

    /**
     * Gateways that are switched on and ready to take money.
     */
    public function enabled(): \Illuminate\Support\Collection
    {
        return Gateway::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Gateway $gateway) => $this->driver($gateway) !== null)
            ->values();
    }

    public function ready(): \Illuminate\Support\Collection
    {
        return $this->enabled()->filter(function (Gateway $gateway) {
            return $this->driver($gateway)?->isConfigured() ?? false;
        })->filter(function (Gateway $gateway) {
            return $gateway->checkoutCurrency() !== null;
        })->values();
    }

    /**
     * @return array<int, class-string<PaymentGateway>>
     */
    public function availableDrivers(): array
    {
        return $this->drivers;
    }
}

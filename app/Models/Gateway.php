<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'logo',
        'is_enabled',
        'is_test_mode',
        'credentials',
        'settings',
        'currencies',
        'countries',
        'sort_order',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'currencies' => 'array',
            'countries' => 'array',
            'is_enabled' => 'boolean',
            'is_test_mode' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    /**
     * Public URL of the brand mark for this gateway. The stored logo may or
     * may not carry the extension, so it is normalised here.
     */
    public function logoUrl(): string
    {
        $logo = trim((string) $this->logo);

        if ($logo === '') {
            $logo = 'bank';
        }

        if (! str_ends_with(strtolower($logo), '.svg')) {
            $logo .= '.svg';
        }

        return asset('brand/'.$logo);
    }

    public function isReady(): bool
    {
        return $this->is_enabled && filled($this->credentials) && $this->checkoutCurrency() !== null;
    }

    /**
     * Return a settlement currency supported by both this product's prices
     * and the currencies configured for the gateway.
     */
    public function checkoutCurrency(): ?string
    {
        $currencies = array_map(fn ($currency) => strtoupper((string) $currency), (array) $this->currencies);

        if (in_array('USD', $currencies, true)) {
            return 'USD';
        }

        if (in_array('BDT', $currencies, true)) {
            return 'BDT';
        }

        return null;
    }

    /**
     * The credential fields the administrator fills in for this gateway.
     *
     * @return array<string, string>
     */
    public function credentialFields(): array
    {
        return match ($this->slug) {
            'stripe' => [
                'secret_key' => 'sk_live_...',
                'webhook_secret' => 'whsec_...',
            ],
            'paypal' => [
                'client_id' => 'Client ID',
                'client_secret' => 'Client secret',
                'webhook_id' => 'Webhook ID',
            ],
            'bkash' => [
                'app_key' => 'App key',
                'app_secret' => 'App secret',
                'username' => 'Integration username',
                'password' => 'Integration password',
                'merchant_number' => 'Merchant number',
            ],
            'nagad' => [
                'app_key' => 'App key',
                'app_secret' => 'App secret',
                'merchant_number' => 'Merchant number',
            ],
            'sslcommerz' => [
                'store_id' => 'Store ID',
                'store_password' => 'Store password',
            ],
            'aamarpay' => [
                'store_id' => 'Store ID',
                'store_password' => 'Store password',
            ],
            'razorpay' => [
                'key_id' => 'Key ID',
                'key_secret' => 'Key secret',
                'webhook_secret' => 'Webhook signing secret',
            ],
            'paymob' => [
                'public_key' => 'Public key',
                'secret_key' => 'Secret key',
            ],
            'cashfree' => [
                'app_id' => 'App ID',
                'secret_key' => 'Secret key',
            ],
            'flutterwave' => [
                'public_key' => 'Public key',
                'secret_key' => 'Secret key',
                'secret_hash' => 'Verification hash',
            ],
            'manual' => [
                'account_name' => 'Account name',
                'account_number' => 'Account number',
                'bank_name' => 'Bank name',
                'instructions' => 'Instructions shown to the customer',
            ],
            default => [],
        };
    }
}

<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'slug' => 'stripe',
                'name' => 'Stripe',
                'description' => 'Cards and digital wallets through Stripe.',
                'logo' => 'stripe.svg',
                'currencies' => ['USD', 'BDT'],
                'countries' => ['Worldwide'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'paypal',
                'name' => 'PayPal',
                'description' => 'PayPal balance, cards and bank accounts.',
                'logo' => 'paypal.svg',
                'currencies' => ['USD', 'BDT'],
                'countries' => ['Worldwide'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'bkash',
                'name' => 'bKash',
                'description' => 'Mobile wallet payments in Bangladesh.',
                'logo' => 'bkash.svg',
                'currencies' => ['BDT'],
                'countries' => ['Bangladesh'],
                'sort_order' => 3,
            ],
            [
                'slug' => 'nagad',
                'name' => 'Nagad',
                'description' => 'Mobile wallet payments in Bangladesh.',
                'logo' => 'nagad.svg',
                'currencies' => ['BDT'],
                'countries' => ['Bangladesh'],
                'sort_order' => 4,
            ],
            [
                'slug' => 'sslcommerz',
                'name' => 'SSLCommerz',
                'description' => 'Cards, mobile banking and internet banking in Bangladesh.',
                'logo' => 'sslcommerz.svg',
                'currencies' => ['BDT'],
                'countries' => ['Bangladesh'],
                'sort_order' => 5,
            ],
            [
                'slug' => 'aamarpay',
                'name' => 'AamarPay',
                'description' => 'Cards and mobile wallet payments in Bangladesh.',
                'logo' => 'aamarpay.svg',
                'currencies' => ['BDT'],
                'countries' => ['Bangladesh'],
                'sort_order' => 6,
            ],
            [
                'slug' => 'razorpay',
                'name' => 'Razorpay',
                'description' => 'Payment processing for eligible merchants in supported regions.',
                'logo' => 'razorpay.svg',
                'currencies' => ['USD', 'INR'],
                'countries' => ['India'],
                'sort_order' => 7,
            ],
            [
                'slug' => 'paymob',
                'name' => 'Paymob',
                'description' => 'Cards and wallets in supported markets.',
                'logo' => 'paymob.svg',
                'currencies' => ['USD', 'EGP'],
                'countries' => ['Egypt', 'Saudi Arabia', 'United Arab Emirates'],
                'sort_order' => 8,
            ],
            [
                'slug' => 'cashfree',
                'name' => 'Cashfree',
                'description' => 'Payment processing for eligible merchants in supported regions.',
                'logo' => 'cashfree.svg',
                'currencies' => ['USD', 'INR'],
                'countries' => ['India'],
                'sort_order' => 9,
            ],
            [
                'slug' => 'flutterwave',
                'name' => 'Flutterwave',
                'description' => 'Cards and mobile money in supported markets.',
                'logo' => 'flutterwave.svg',
                'currencies' => ['USD', 'NGN', 'GHS', 'KES', 'ZAR'],
                'countries' => ['Nigeria', 'Ghana', 'Kenya', 'South Africa'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'manual',
                'name' => 'Bank transfer',
                'description' => 'Transfer instructions configured by the site administrator.',
                'logo' => 'bank.svg',
                'currencies' => ['USD', 'BDT'],
                'countries' => ['Worldwide'],
                'sort_order' => 11,
            ],
        ];

        foreach ($gateways as $attributes) {
            $gateway = Gateway::firstOrNew(['slug' => $attributes['slug']]);
            $isNew = ! $gateway->exists;

            if ($isNew) {
                $gateway->fill($attributes);
                $gateway->is_enabled = false;
                $gateway->is_test_mode = true;
                $gateway->credentials = [];
                $gateway->settings = [];
            }

            // Clean up the previous placeholder bank details once, without ever
            // overwriting credentials an administrator has since configured.
            if ($gateway->slug === 'manual'
                && ($gateway->credential('account_number') === '0000000000'
                    || $gateway->credential('bank_name') === 'Your bank')) {
                $gateway->is_enabled = false;
                $gateway->credentials = [];
            }

            $gateway->save();
        }
    }
}

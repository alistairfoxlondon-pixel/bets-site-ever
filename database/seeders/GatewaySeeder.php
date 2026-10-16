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
                'description' => 'Cards in more than 135 countries.',
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
                'description' => 'The mobile wallet used across Bangladesh.',
                'logo' => 'bkash.svg',
                'currencies' => ['BDT'],
                'countries' => ['Bangladesh'],
                'sort_order' => 3,
            ],
            [
                'slug' => 'nagad',
                'name' => 'Nagad',
                'description' => 'Fast mobile payments in Bangladesh.',
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
                'name' => 'Rocket by AamarPay',
                'description' => 'Rocket wallet and cards through AamarPay.',
                'logo' => 'rocket.svg',
                'currencies' => ['BDT'],
                'countries' => ['Bangladesh'],
                'sort_order' => 6,
            ],
            [
                'slug' => 'razorpay',
                'name' => 'Razorpay',
                'description' => 'Cards, net banking and wallets in India.',
                'logo' => 'razorpay.svg',
                'currencies' => ['USD', 'INR'],
                'countries' => ['India'],
                'sort_order' => 7,
            ],
            [
                'slug' => 'paymob',
                'name' => 'Paymob',
                'description' => 'Cards and wallets in Egypt and the Gulf.',
                'logo' => 'paymob.svg',
                'currencies' => ['USD', 'EGP'],
                'countries' => ['Egypt', 'Saudi Arabia', 'United Arab Emirates'],
                'sort_order' => 8,
            ],
            [
                'slug' => 'cashfree',
                'name' => 'Cashfree',
                'description' => 'A single integration for cards, net banking and wallets in India.',
                'logo' => 'cashfree.svg',
                'currencies' => ['USD', 'INR'],
                'countries' => ['India'],
                'sort_order' => 9,
            ],
            [
                'slug' => 'flutterwave',
                'name' => 'Flutterwave',
                'description' => 'Cards and mobile money across Africa.',
                'logo' => 'flutterwave.svg',
                'currencies' => ['USD', 'NGN', 'GHS', 'KES', 'ZAR'],
                'countries' => ['Nigeria', 'Ghana', 'Kenya', 'South Africa'],
                'sort_order' => 10,
            ],
            [
                'slug' => 'manual',
                'name' => 'Bank transfer',
                'description' => 'Transfer the amount and we confirm the order by hand.',
                'logo' => 'bank.svg',
                'currencies' => ['USD', 'BDT'],
                'countries' => ['Worldwide'],
                'sort_order' => 11,
            ],
        ];

        foreach ($gateways as $gateway) {
            Gateway::updateOrCreate(
                ['slug' => $gateway['slug']],
                array_merge($gateway, [
                    'is_enabled' => $gateway['slug'] === 'manual',
                    'is_test_mode' => true,
                    'credentials' => $gateway['slug'] === 'manual' ? [
                        'account_name' => 'AdReady Limited',
                        'account_number' => '0000000000',
                        'bank_name' => 'Your bank',
                        'instructions' => 'Transfer the invoice total and include the invoice number as the reference.',
                    ] : [],
                    'settings' => [],
                ])
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'tagline' => 'For a first audit and a first idea.',
                'description' => 'Everything you need to check one site properly and read the report.',
                'features' => [
                    '10 credits every month',
                    'One tracked site',
                    'Full PDF readiness report',
                    'AdSense content writer',
                    'Email support',
                ],
                'price_monthly' => 9,
                'price_yearly' => 86,
                'price_monthly_bdt' => 1100,
                'price_yearly_bdt' => 10500,
                'credits' => 10,
                'max_sites' => 1,
                'trial_days' => 0,
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'tagline' => 'For publishers running several sites.',
                'description' => 'The balance most people want: enough credits to audit deeply and publish often.',
                'features' => [
                    '40 credits every month',
                    'Five tracked sites',
                    'Full PDF readiness report',
                    'AdSense and affiliate writers',
                    'Quick indexing tool',
                    'Priority support',
                ],
                'price_monthly' => 24,
                'price_yearly' => 230,
                'price_monthly_bdt' => 2950,
                'price_yearly_bdt' => 28200,
                'credits' => 40,
                'max_sites' => 5,
                'trial_days' => 7,
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Agency',
                'slug' => 'agency',
                'tagline' => 'For teams that look after many properties.',
                'description' => 'Deep audits, more writing, and the referral programme that pays for itself.',
                'features' => [
                    '150 credits every month',
                    'Twenty five tracked sites',
                    'Full PDF readiness report',
                    'AdSense and affiliate writers',
                    'Quick indexing tool',
                    'Affiliate programme access',
                    'Named support contact',
                ],
                'price_monthly' => 79,
                'price_yearly' => 758,
                'price_monthly_bdt' => 9700,
                'price_yearly_bdt' => 93000,
                'credits' => 150,
                'max_sites' => 25,
                'trial_days' => 7,
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}

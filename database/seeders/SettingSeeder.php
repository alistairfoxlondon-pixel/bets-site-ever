<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'ai.api_key', 'value' => '', 'group' => 'ai', 'label' => 'OpenRouter API key', 'type' => 'secret', 'is_public' => false],
            ['key' => 'ai.base_url', 'value' => 'https://openrouter.ai/api/v1', 'group' => 'ai', 'label' => 'OpenRouter base URL', 'type' => 'text', 'is_public' => false],
            ['key' => 'ai.site_url', 'value' => 'https://your-domain.com', 'group' => 'ai', 'label' => 'Site URL sent to OpenRouter', 'type' => 'text', 'is_public' => false],
            ['key' => 'ai.site_name', 'value' => 'AdReady', 'group' => 'ai', 'label' => 'Application name sent to OpenRouter', 'type' => 'text', 'is_public' => false],
            ['key' => 'ai.price_per_million', 'value' => 0, 'group' => 'ai', 'label' => 'Assumed price per million tokens', 'type' => 'number', 'is_public' => false],
            ['key' => 'ai.model_normal', 'value' => 'kimi/kimi-k2-thinking', 'group' => 'ai', 'label' => 'Model for the Normal audit', 'type' => 'text', 'is_public' => false],
            ['key' => 'ai.model_standard', 'value' => 'anthropic/claude-sonnet-4.5', 'group' => 'ai', 'label' => 'Model for the Standard audit', 'type' => 'text', 'is_public' => false],
            ['key' => 'ai.model_premium', 'value' => 'anthropic/claude-opus-4.1', 'group' => 'ai', 'label' => 'Model for the Premium audit', 'type' => 'text', 'is_public' => false],
            ['key' => 'ai.model_writer_standard', 'value' => 'anthropic/claude-sonnet-4.5', 'group' => 'ai', 'label' => 'Model for the Standard writer', 'type' => 'text', 'is_public' => false],
            ['key' => 'ai.model_writer_premium', 'value' => 'anthropic/claude-opus-4.1', 'group' => 'ai', 'label' => 'Model for the Premium writer', 'type' => 'text', 'is_public' => false],

            ['key' => 'indexing.google_service_account', 'value' => '', 'group' => 'indexing', 'label' => 'Google Indexing API service account JSON', 'type' => 'textarea', 'is_public' => false],
            ['key' => 'indexing.indexnow_key', 'value' => '', 'group' => 'indexing', 'label' => 'IndexNow API key', 'type' => 'secret', 'is_public' => false],
            ['key' => 'indexing.indexnow_key_location', 'value' => '', 'group' => 'indexing', 'label' => 'IndexNow key file URL', 'type' => 'text', 'is_public' => false],

            ['key' => 'currency.primary', 'value' => 'USD', 'group' => 'currency', 'label' => 'Primary currency', 'type' => 'text', 'is_public' => true],
            ['key' => 'currency.bdt_rate', 'value' => 122.5, 'group' => 'currency', 'label' => 'Taka exchange rate', 'type' => 'number', 'is_public' => true],

            ['key' => 'billing.free_signup_credits', 'value' => 2, 'group' => 'billing', 'label' => 'Free credits for new accounts', 'type' => 'number', 'is_public' => false],
            ['key' => 'billing.credits_price', 'value' => 1.5, 'group' => 'billing', 'label' => 'Price for one credit', 'type' => 'number', 'is_public' => false],

            ['key' => 'affiliate.commission_rate', 'value' => 20, 'group' => 'affiliate', 'label' => 'Commission rate in percent', 'type' => 'number', 'is_public' => true],
            ['key' => 'affiliate.payout_minimum', 'value' => 50, 'group' => 'affiliate', 'label' => 'Minimum withdrawal in USD', 'type' => 'number', 'is_public' => true],
            ['key' => 'affiliate.cookie_days', 'value' => 30, 'group' => 'affiliate', 'label' => 'Attribution window in days', 'type' => 'number', 'is_public' => true],

            ['key' => 'auth.google_client_id', 'value' => '', 'group' => 'auth', 'label' => 'Google OAuth client id', 'type' => 'text', 'is_public' => false],
            ['key' => 'auth.google_client_secret', 'value' => '', 'group' => 'auth', 'label' => 'Google OAuth client secret', 'type' => 'secret', 'is_public' => false],
            ['key' => 'support_email', 'value' => 'support@your-domain.com', 'group' => 'general', 'label' => 'Support email address', 'type' => 'text', 'is_public' => true],
            ['key' => 'contact_address', 'value' => '', 'group' => 'general', 'label' => 'Company address', 'type' => 'text', 'is_public' => true],
            ['key' => 'crawl_max_pages', 'value' => 25, 'group' => 'general', 'label' => 'Hard cap on crawled pages', 'type' => 'number', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}

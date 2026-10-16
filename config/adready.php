<?php

return [

    'bootstrap' => [
        'admin_email' => env('ADREADY_BOOTSTRAP_ADMIN_EMAIL'),
        'admin_name' => env('ADREADY_BOOTSTRAP_ADMIN_NAME', 'AdReady Administrator'),
        'admin_password' => env('ADREADY_BOOTSTRAP_ADMIN_PASSWORD'),
        'demo_email' => env('ADREADY_DEMO_EMAIL'),
        'demo_name' => env('ADREADY_DEMO_NAME', 'Demo Publisher'),
        'demo_password' => env('ADREADY_DEMO_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Product modes
    |--------------------------------------------------------------------------
    |
    | Each mode maps to a model tier, a credit cost and how deep the crawler
    | and rule engine go. Administrators can override the model from the
    | admin panel; the values below are the shipped defaults.
    |
    */

    'modes' => [
        'normal' => [
            'label' => 'Normal',
            'blurb' => 'Quick review of a small public-page sample with a concise model review.',
            'model' => env('ADREADY_MODEL_NORMAL', 'kimi/kimi-k2-thinking'),
            'credits' => (int) env('ADREADY_CREDITS_NORMAL', 1),
            'max_pages' => 4,
            'temperature' => 0.3,
            'max_tokens' => 4000,
            'with_ai' => true,
            'featured' => false,
        ],
        'standard' => [
            'label' => 'Standard',
            'blurb' => 'Broader page sample with the complete deterministic rule set and model review.',
            'model' => env('ADREADY_MODEL_STANDARD', 'anthropic/claude-sonnet-4.5'),
            'credits' => (int) env('ADREADY_CREDITS_STANDARD', 3),
            'max_pages' => 10,
            'temperature' => 0.25,
            'max_tokens' => 9000,
            'with_ai' => true,
            'featured' => true,
        ],
        'premium' => [
            'label' => 'Premium',
            'blurb' => 'Largest configured page sample and a more detailed model review; some URLs may still be missed.',
            'model' => env('ADREADY_MODEL_PREMIUM', 'anthropic/claude-opus-4.1'),
            'credits' => (int) env('ADREADY_CREDITS_PREMIUM', 6),
            'max_pages' => 25,
            'temperature' => 0.2,
            'max_tokens' => 16000,
            'with_ai' => true,
            'featured' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring
    |--------------------------------------------------------------------------
    |
    | Product-defined heuristic weights used to summarise observed findings.
    | These are not Google's or any partner's review criteria.
    |
    */

    'score' => [
        'categories' => [
            'content_quality' => ['label' => 'Content quality', 'weight' => 26],
            'policy' => ['label' => 'Policy and compliance', 'weight' => 20],
            'trust' => ['label' => 'Trust and E-E-A-T', 'weight' => 16],
            'seo' => ['label' => 'SEO and indexing', 'weight' => 14],
            'technical' => ['label' => 'Technical and UX', 'weight' => 12],
            // A public crawl cannot inspect sessions, CTR, bots or referrals.
            // Keep this area visible in the report, but exclude it from the score.
            'traffic' => ['label' => 'Traffic quality', 'weight' => 0],
        ],
        'penalties' => [
            'critical' => 16,
            'warning' => 5,
            'pass' => 0,
        ],
        'verdicts' => [
            'apply_now' => ['min' => 78, 'label' => 'Strong public signals'],
            'wait_and_improve' => ['min' => 55, 'label' => 'Improvements recommended'],
            'high_risk' => ['min' => 0, 'label' => 'Significant public gaps'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ad networks we score against
    |--------------------------------------------------------------------------
    */

    'networks' => [
        'adsense' => [
            'label' => 'Google AdSense',
            'requirements' => [
                'Review original, useful content and site quality',
                'No universal published post-count or word-count minimum',
                'Clear navigation and transparent publisher information',
                'Policy compliance and original rights to published material',
                'Traffic quality and account history require publisher-side evidence',
            ],
        ],
        'adx' => [
            'label' => 'Google Ad Manager',
            'requirements' => [
                'Eligibility depends on account status and current Google terms',
                'Inventory, demand and traffic are not visible in a public crawl',
                'Consent and privacy requirements depend on audience and setup',
                'Ad tags and serving behavior require a technical integration review',
                'Confirm current access requirements with Google or your partner',
            ],
        ],
        'ezoic' => [
            'label' => 'Ezoic',
            'requirements' => [
                'Review current publisher terms directly before applying',
                'Original content and a useful publishing experience',
                'Traffic, account status and revenue cannot be inferred from pages',
                'Clear navigation, publisher identity and working pages',
                'Policy compliance and rights to published material',
            ],
        ],
        'mediavine' => [
            'label' => 'Mediavine',
            'requirements' => [
                'Review current publisher program requirements directly',
                'Original content with a clear editorial point of view',
                'Traffic, revenue and audience geography need analytics evidence',
                'Visible publisher identity and a professional site experience',
                'Policy compliance and rights to published material',
            ],
        ],
        'raptive' => [
            'label' => 'Raptive',
            'requirements' => [
                'Review current publisher program requirements directly',
                'Original, useful content and a trustworthy publisher identity',
                'Traffic, audience geography and account status are not crawlable',
                'Clear navigation and working public pages',
                'Policy compliance and rights to published material',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Crawler limits
    |--------------------------------------------------------------------------
    */

    'crawler' => [
        'timeout' => 25,
        'connect_timeout' => 10,
        'max_bytes' => 786432,
        'max_total_bytes' => 8388608,
        'max_crawl_seconds' => 90,
        'max_redirects' => 4,
        'user_agent' => 'Mozilla/5.0 (compatible; AdReadyBot/1.0; +https://your-domain.com/bot)',
        'blocked_hosts' => [
            'localhost', '127.0.0.1', '0.0.0.0', '::1',
            '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '169.254.0.0/16',
            'metadata.google.internal', 'metadata.goog',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    */

    'currency' => [
        'primary' => env('ADREADY_PRIMARY_CURRENCY', 'USD'),
        'secondary' => 'BDT',
        'bdt_rate' => (float) env('ADREADY_BDT_RATE', 122.5),
        'rate_source' => env('ADREADY_CURRENCY_UPDATE_URL'),
    ],

    'referral' => [
        'rate' => (float) env('ADREADY_REFERRAL_RATE', 20),
        'min_payout' => (float) env('ADREADY_MIN_PAYOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content writer
    |--------------------------------------------------------------------------
    */

    'writer' => [
        'modes' => [
            'standard' => [
                'label' => 'Standard',
                'model' => env('ADREADY_MODEL_STANDARD', 'anthropic/claude-sonnet-4.5'),
                'credits' => 2,
                'max_tokens' => 12000,
                'temperature' => 0.5,
            ],
            'premium' => [
                'label' => 'High quality',
                'model' => env('ADREADY_MODEL_PREMIUM', 'anthropic/claude-opus-4.1'),
                'credits' => 5,
                'max_tokens' => 20000,
                'temperature' => 0.6,
            ],
        ],
        'image_styles' => [
            'vector' => 'Flat vector illustration, single accent colour, geometric shapes, no text',
            'photo' => 'Editorial photograph, natural light, shallow depth of field, no text',
            'neo_brutalist' => 'Neo brutalist layout, bold blocks of colour, heavy borders, no text',
            'diagram' => 'Clean labelled diagram, thin lines, restrained palette, minimal text',
        ],
        'min_words' => 600,
        'max_words' => 3200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */

    'pdf' => [
        'disk' => env('ADREADY_PDF_DISK', 'local'),
        'path' => env('ADREADY_PDF_PATH', 'reports'),
        'paper' => 'a4',
        'orientation' => 'portrait',
    ],

    'defaults' => [
        'guest_audits_per_day' => 1,
        'free_signup_credits' => 2,
        'support_email' => 'support@your-domain.com',
    ],
];

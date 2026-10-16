<?php

return [

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
            'blurb' => 'Fast rules based pass over the site with a lightweight model.',
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
            'blurb' => 'Full crawl with the complete policy rule set and a frontier model.',
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
            'blurb' => 'Deepest analysis: every page type, every policy line, top tier model.',
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
    | Category weights used to turn findings into a single readiness score.
    | The weights mirror how heavily Google's reviewers weigh each area.
    |
    */

    'score' => [
        'categories' => [
            'content_quality' => ['label' => 'Content quality', 'weight' => 26],
            'policy' => ['label' => 'Policy and compliance', 'weight' => 20],
            'trust' => ['label' => 'Trust and E-E-A-T', 'weight' => 16],
            'seo' => ['label' => 'SEO and indexing', 'weight' => 14],
            'technical' => ['label' => 'Technical and UX', 'weight' => 12],
            'traffic' => ['label' => 'Traffic quality', 'weight' => 12],
        ],
        'penalties' => [
            'critical' => 16,
            'warning' => 5,
            'pass' => 0,
        ],
        'verdicts' => [
            'apply_now' => ['min' => 78, 'label' => 'Apply now'],
            'wait_and_improve' => ['min' => 55, 'label' => 'Wait and improve'],
            'high_risk' => ['min' => 0, 'label' => 'High risk of rejection'],
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
                'Original content worth advertising on',
                'At least 25 to 30 published posts of real value',
                'Clear navigation, contact, about and privacy pages',
                'Enough original traffic to fill ads for advertisers',
                'No policy restricted content, no copied material',
            ],
        ],
        'adx' => [
            'label' => 'Google Ad Manager',
            'requirements' => [
                'AdSense approved, or publisher requirement met',
                'Direct sold inventory and 100 thousand monthly page views',
                'Working ad tag or content management system',
                'Consent management for EEA and UK traffic',
                'No invalid traffic and no self served ad calls',
            ],
        ],
        'ezoic' => [
            'label' => 'Ezoic',
            'requirements' => [
                'English content with clear value and originality',
                'Minimum of five hundred monthly visitors',
                'Blog, news or reference style publishing',
                'Clean design, fast pages and working contact details',
                'No prohibited content or scraped articles',
            ],
        ],
        'mediavine' => [
            'label' => 'Mediavine',
            'requirements' => [
                'Fifty thousand monthly sessions across all networks',
                'Strong original content with a point of view',
                'Professional layout, clear about page and real authors',
                'Primarily English, informational or lifestyle content',
                'No copied, spun or auto translated articles',
            ],
        ],
        'raptive' => [
            'label' => 'Raptive',
            'requirements' => [
                'At least twenty five thousand monthly page views',
                'Original, useful content with clear author identity',
                'Professional and fast website with contact details',
                'Clean navigation, working links and real comments policy',
                'No copyright or policy violations',
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
        'max_bytes' => 3145728,
        'max_redirects' => 5,
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

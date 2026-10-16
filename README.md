# AdReady

Laravel SaaS that audits a site against Google AdSense, AdX, Ezoic, Mediavine and Raptive, then returns an approval probability, a ranked fix list and a branded PDF report. The same account includes an AdSense / affiliate writer, URL indexing, billing, referrals and a `/manage` admin.

## Stack

- Laravel 12, PHP 8.4+, SQLite or MySQL
- Vite, Tailwind CSS 4, Tenor Sans + Source Sans 3
- Dompdf reports, OpenRouter for model reviews, 2FA, Google sign-in

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php -S 0.0.0.0:8000 -t public server.php
```

Seeded logins (change these in production):

- User: `demo@adready.test` / `ChangeMe!2024`
- Admin: `admin@adready.test` / `ChangeMe!2024` → `/manage`

Put OpenRouter, payment gateway and Google OAuth keys in admin settings or `.env`. Referral commission defaults to 20% and is adjustable in admin.

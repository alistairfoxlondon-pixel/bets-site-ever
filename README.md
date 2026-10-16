# AdReady

Laravel SaaS for reviewing public publisher-site signals across Google AdSense, Google Ad Manager, Ezoic, Mediavine, Raptive and similar networks. Audits return evidence-linked findings, a product-defined readiness index and a branded PDF. The index is a heuristic, not an approval probability or network eligibility decision. Public crawls do not assess private analytics, traffic quality or account status.

The account also includes informational and affiliate article drafting, supported indexing-request workflows, billing, referrals and a `/manage` administration area.

## Stack

- Laravel 12, PHP 8.2+ (per `composer.json`), SQLite or MySQL; PHP cURL is required for DNS-pinned hostname audits, and `intl` enables internationalised hostnames
- Vite, Tailwind CSS 4, Tenor Sans + Source Sans 3
- Dompdf reports, configurable AI provider, two-factor-authentication support, Google sign-in

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan migrate
```

Before running `php artisan db:seed`, configure a unique initial administrator email and password in your secret manager or local `.env`:

```dotenv
ADREADY_BOOTSTRAP_ADMIN_EMAIL=admin@your-domain.example
ADREADY_BOOTSTRAP_ADMIN_PASSWORD=<unique secret of at least 20 characters>
```

The seeder creates this account only if the email does not already exist. Re-seeding will not reset an existing password, role, suspension status or other account state. If no bootstrap credentials are configured, the seeder does not create a default administrator. Do not put production credentials in `.env.example`, source control, or chat.

Optional demonstration credentials use `ADREADY_DEMO_EMAIL` and `ADREADY_DEMO_PASSWORD`. Demo accounts and illustrative audit data are seeded only in local and testing environments; sample reports use the reserved `publisher-demo.example` domain. No default demo login is shipped.

To serve the application locally after completing setup:

```bash
php artisan serve --host=0.0.0.0
```

Configure the database, mail, OAuth, AI, payment and indexing integrations in `.env` or through `/manage/settings`. Payment gateways, including bank transfer, are disabled and in test mode by default; add real credentials and verify each provider's currency, callbacks and test transaction before enabling it. AamarPay and Paymob are deliberately unavailable in checkout until their server-side payment-verification flows are completed against confirmed provider documentation. Webhooks without a verified signature are rejected. The seed data contains no bank-account details. Review current third-party provider terms before enabling an integration. Referral commission defaults to 20% and is adjustable in admin.

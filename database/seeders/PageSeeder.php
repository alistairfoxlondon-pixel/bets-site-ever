<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'About AdReady',
                'meta_title' => 'About AdReady, publisher-site audit tools',
                'meta_description' => 'Learn how AdReady reviews sampled public-site signals, generates article drafts and tracks supported indexing requests without predicting approval.',
                'body' => <<<'HTML'
<p>AdReady helps publishers review public-site signals and organise practical improvements. The audit service samples pages and files that a crawler can access, then presents deterministic checks, model-assisted observations and a product-defined readiness index.</p>
<p>The index is an internal heuristic, not a calibrated probability, official network checklist or prediction of approval. A public crawl cannot verify analytics dashboards, traffic quality, revenue, ownership, account history or a partner's current eligibility terms. Check current requirements directly with each network and use your own analytics for private data.</p>
<h2>What we believe</h2>
<ul>
<li><strong>Evidence before certainty.</strong> Reports distinguish observed signals from assumptions and explain the limits of a sampled crawl.</li>
<li><strong>Useful drafts, not guarantees.</strong> The writing tools create editable content for a publisher to fact-check, source and review before publication.</li>
<li><strong>Submission is not indexing.</strong> Supported indexing requests can be tracked, but search engines decide whether and when to crawl or index a page.</li>
</ul>
<h2>Who it is for</h2>
<p>Independent publishers, bloggers and agencies who want a structured review of public site signals, a record of changes over time and tools to prepare content for human review.</p>
<h2>Contact</h2>
<p>Questions, corrections and partnership enquiries are welcome. Use the contact form and pick a reason so the right person answers it.</p>
HTML,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of service',
                'meta_title' => 'Terms of service',
                'meta_description' => 'The terms that govern the use of the AdReady platform, its audits, reports, credits and subscriptions.',
                'body' => <<<'HTML'
<p>These terms govern your use of AdReady. By creating an account you accept them. If you do not accept them, do not use the service.</p>
<h2>1. The service</h2>
<p>AdReady audits public websites you nominate, produces heuristic reports, generates editable article drafts and may submit supported URLs to configured indexing services. Reports are based on sampled public information and automated checks. They are not official network reviews, eligibility decisions, traffic assessments or guarantees.</p>
<h2>2. No approval or indexing guarantee</h2>
<p>We do not control Google AdSense, Google Ad Manager, Ezoic, Mediavine, Raptive, search engines or other services. We do not predict or guarantee ad-network approval, search visibility, crawling, indexing, traffic, revenue or rankings. The readiness index is a product-defined heuristic and is not an official or calibrated probability. Decisions rest with the relevant network or search engine.</p>
<h2>3. Accounts</h2>
<p>You must provide accurate registration information, keep your password secure and use one account. You are responsible for everything done through your account, including anything done by an automated system you enable. Accounts may be suspended for abuse, for attempting to audit networks or private infrastructure, or for a breach of these terms.</p>
<h2>4. Credits and subscriptions</h2>
<p>Audits and articles consume credits. Credits are granted by a plan, by an administrator, or by a promotion. Credits do not carry a monetary value and cannot be exchanged for cash except through the affiliate programme, which is a separate arrangement. Subscriptions renew until cancelled, and cancellation takes effect at the end of the paid period. Prices are shown in US dollars and Bangladeshi taka, and the taka figure is calculated from the exchange rate we publish.</p>
<h2>5. Refunds</h2>
<p>Unused credits are refunded if you cancel within seven days of a purchase. After that, credits remain available in your account and can be used at any time. Completed audits and delivered articles are not refundable because the work has already been performed.</p>
<h2>6. Acceptable use</h2>
<p>Do not use the service to attack, overload or probe networks, to audit private, internal or reserved addresses, to bypass rate limits, to resell the reports as your own product, or to attempt to reverse engineer the scoring system. Automated crawling is limited to what is reasonable for one site at a time.</p>
<h2>7. Intellectual property</h2>
<p>We own the platform, the rule engine, the report layout and the software. You own your account data and the content you publish. Articles written for you are yours to publish, including for monetised sites. We keep the right to use anonymised, aggregated usage data to improve the product.</p>
<h2>8. Availability</h2>
<p>We aim for high availability but do not promise it. Planned maintenance is announced in the product. Credits consumed by a service failure are returned to your balance.</p>
<h2>9. Liability</h2>
<p>To the extent permitted by law, our total liability for any claim is limited to the amount you paid us in the twelve months before the claim. We are not liable for lost profits, lost traffic or lost advertising revenue.</p>
<h2>10. Changes</h2>
<p>We may update these terms. Material changes are announced by email and in the product at least fourteen days before they take effect. Continuing to use the service after that means you accept the change.</p>
<h2>11. Governing law</h2>
<p>These terms are governed by the laws of the jurisdiction in which the company that operates AdReady is registered. Disputes are handled by the courts of that jurisdiction.</p>
HTML,
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy policy',
                'meta_title' => 'Privacy policy',
                'meta_description' => 'What AdReady collects, why it is collected, how long it is kept and what you can ask us to delete.',
                'body' => <<<'HTML'
<p>This policy explains what AdReady collects, why, and what you can ask us to do about it. It is written to be read, not to be skipped.</p>
<h2>What we collect</h2>
<ul>
<li><strong>Account information.</strong> Your name, email address, optional phone number and country. If you sign in with Google we receive the name, email address and profile picture that Google shares.</li>
<li><strong>Payment information.</strong> Card details never reach our servers. Payment gateways handle them and send us a transaction reference, an amount and a status.</li>
<li><strong>Audit data.</strong> The addresses you ask us to audit, the pages we fetched from them, the findings we produced and the reports you downloaded.</li>
<li><strong>Written content.</strong> The keywords you enter and the articles we write for you.</li>
<li><strong>Technical data.</strong> IP address, browser type and sign in times, kept for security and abuse prevention.</li>
</ul>
<h2>Why we collect it</h2>
<p>To run the service you signed up for, to keep your account secure, to take payment, to answer support requests and to understand which parts of the product are used so we can improve them. We do not sell your personal data, and we do not share it with ad networks.</p>
<h2>Third parties</h2>
<p>We use a small number of processors, each limited to the data it needs: the AI provider that reviews audit evidence, the payment gateway that takes your money, the mail provider that sends account emails, and the hosting provider that runs the platform. When you enable the indexing tool, the URL you submit is sent to the search engine you selected.</p>
<h2>Retention</h2>
<p>Account data is kept while your account is open. After you close an account we keep invoices and audit logs for the period required by tax and accounting law, and we delete everything else within thirty days.</p>
<h2>Your rights</h2>
<p>You can ask for a copy of your data, ask us to correct it, ask us to delete it, or ask us to stop processing it. Write to us and we will deal with the request within thirty days. If you are in the EEA or the UK, these are your statutory rights and you may also complain to your data protection authority.</p>
<h2>Cookies</h2>
<p>We use a session cookie to keep you signed in, a cookie to remember which affiliate referred you, and a preference cookie for your currency. There is no advertising or tracking cookie on this site.</p>
<h2>Security</h2>
<p>Passwords are hashed, two factor authentication is available on every account, sensitive values are encrypted at rest, and privileged actions are written to an audit log.</p>
HTML,
            ],
            [
                'slug' => 'refund-policy',
                'title' => 'Refund policy',
                'meta_title' => 'Refund policy',
                'meta_description' => 'When AdReady refunds a payment, how long it takes and what is not refundable.',
                'body' => <<<'HTML'
<h2>Plans</h2>
<p>If you cancel within seven days of a plan payment and you have used less than half of the credits the plan gave you, we refund the payment in full. After seven days the plan runs to the end of the period you paid for and then stops.</p>
<h2>Credits</h2>
<p>Credits that you have not used are refunded in the same case. Credits that have been used on audits or articles are not refundable, because that work has already been carried out and the report or the article belongs to you.</p>
<h2>How refunds are made</h2>
<p>Refunds go back to the payment method you used. Banks and mobile wallets usually take three to five working days. We confirm the refund by email once it is sent.</p>
<h2>Failed or duplicated payments</h2>
<p>If a payment was taken twice, or taken and the order never reached us, write to support with the invoice number and we will correct it and return the money.</p>
<h2>Affiliate commissions</h2>
<p>Commission that has been paid out cannot be reclaimed. Commission that is still pending can be withdrawn normally, and a rejected withdrawal returns the amount to your balance.</p>
HTML,
            ],
            [
                'slug' => 'acceptable-use',
                'title' => 'Acceptable use',
                'meta_title' => 'Acceptable use policy',
                'meta_description' => 'The rules that keep the auditor fair, fast and available for everyone.',
                'body' => <<<'HTML'
<p>AdReady crawls websites that you point it at. That is a real request to someone else's server, so we keep it polite and bounded.</p>
<h2>What the crawler does</h2>
<p>It requests the home page, robots.txt, the sitemap and a limited number of article pages, using a descriptive user agent and a low request rate. It reads public HTML only. It never signs in, never submits forms, never bypasses a paywall and never follows a page into a private or internal address.</p>
<h2>What is not allowed</h2>
<ul>
<li>Auditing a site in a way that harms it, including deliberate load.</li>
<li>Auditing private, internal or reserved network addresses, or using the service to reach systems you should not be able to reach.</li>
<li>Running many audits at once from one account to use the service as a crawler.</li>
<li>Reselling the reports or the platform as your own product.</li>
<li>Reverse engineering the scoring system to publish a competing service.</li>
</ul>
<h2>Fair use of the writers and the indexer</h2>
<p>One article request at a time per account. The indexing tool sends a URL to the engine you select, so use it for your own pages.</p>
<h2>Reporting a problem</h2>
<p>If our crawler causes trouble on your site, tell us the address and we will stop crawling it and add a rule so we do not return.</p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                array_merge($page, ['status' => 'published'])
            );
        }
    }
}

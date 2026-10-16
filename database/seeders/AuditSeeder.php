<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\User;
use App\Services\Audit\ScoringEngine;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $demoEmail = strtolower(trim((string) config('adready.bootstrap.demo_email')));

        if ($demoEmail === '') {
            return;
        }

        $user = User::where('email', $demoEmail)->first();

        if (! $user) {
            $this->command?->warn('Local demo user missing; configure ADREADY_DEMO_EMAIL and ADREADY_DEMO_PASSWORD first.');

            return;
        }

        $domain = 'publisher-demo.example';
        $user->audits()->where('domain', $domain)->each(function (Audit $existing): void {
            $existing->findings()->delete();
            $existing->delete();
        });

        foreach ($this->sampleRuns() as $run) {
            $scored = app(ScoringEngine::class)->score($run['findings']);
            $audit = Audit::create([
                'user_id' => $user->id,
                'url' => 'https://'.$domain,
                'normalized_url' => 'https://'.$domain,
                'domain' => $domain,
                'mode' => 'standard',
                'status' => Audit::STATUS_COMPLETED,
                'score' => $scored['score'],
                // Kept in sync for compatibility with the legacy database column.
                'probability' => $scored['readiness_index'],
                'verdict' => $scored['verdict'],
                'model' => null,
                'tokens_used' => 0,
                'cost' => 0,
                'duration_ms' => 0,
                'pages_crawled' => $run['pages_crawled'],
                'findings_count' => count($run['findings']),
                'started_at' => now()->subDays($run['days_ago'])->subMinutes(3),
                'completed_at' => now()->subDays($run['days_ago']),
                'meta' => [
                    'sample_data' => true,
                    'credits_cost' => 0,
                    'category_scores' => $scored['category_scores'],
                    'network_readiness' => $this->sampleNetworkSignals($run['network_shift']),
                    'analysis' => $run['analysis'],
                    'strengths' => $run['strengths'],
                    'risks' => $run['risks'],
                    'critical_fixes' => $run['priority_fixes'],
                    'roadmap' => $run['roadmap'],
                    'summary' => $run['summary'],
                ],
            ]);

            foreach ($run['findings'] as $index => $finding) {
                AuditFinding::create(array_merge($finding, [
                    'audit_id' => $audit->id,
                    'sort_order' => $index,
                ]));
            }
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function sampleRuns(): array
    {
        $beforeFindings = [
            $this->finding('content_quality', 'warning', 'No article-style pages detected', 'The sample crawl found no article-style page URLs. A limited crawl can miss pages, and there is no fixed post-count minimum.', 'Check that useful pages are publicly reachable from navigation or a sitemap.', ['articles_found' => 0], 12),
            $this->finding('content_quality', 'warning', 'Many sampled pages have limited visible text', 'Several sampled pages fall below the product\'s internal 350-word review heuristic. Short pages can still be useful; context matters.', 'Review the shortest pages for missing answers and keep concise pages that serve a clear purpose.', ['thin_pages' => 3, 'pages_checked' => 8], 14),
            $this->finding('trust', 'warning', 'No Privacy policy link detected', 'The crawl did not find a matching privacy-page link on the home page. The page may exist at another URL or be linked elsewhere.', 'Make sure visitors can find a current privacy notice appropriate to your site and audience.', [], 10),
            $this->finding('trust', 'info', 'No clear author signal found in the sample', 'The crawl did not detect a byline or author-related signal in the sampled pages. This does not prove authorship information is absent.', 'Where useful, identify authors and explain relevant experience without overstating it.', [], 0),
            $this->finding('seo', 'warning', 'Title element not detected on the home page', 'The sample markup did not include a document title. This may affect how the page is identified in browser tabs and search results.', 'Add an accurate descriptive title; no fixed character-count requirement applies.', [], 8),
            $this->finding('technical', 'warning', 'Slow response observed during this crawl', 'The illustrative sample includes a slow response measurement. Actual performance varies by server load, network and cache state.', 'Measure again from target regions and review caching or hosting if the delay is consistent.', ['duration_ms' => 3400], 8),
            $this->finding('traffic', 'info', 'Traffic quality was not assessed', 'A public crawl cannot inspect analytics dashboards, sessions, referrals, bots, engagement or click-through rates.', 'Review your own analytics and search-console evidence separately.', [], 0),
            $this->finding('technical', 'pass', 'The sample URL used HTTPS', 'The illustrative URL uses a secure connection. This does not establish partner eligibility.', null, [], 0),
        ];

        $afterFindings = [
            $this->finding('content_quality', 'info', 'Article-style pages detected', 'Three article-style URLs were detected in this illustrative sample. The count does not measure usefulness or establish a requirement.', 'Keep important pages discoverable and review their usefulness for readers.', ['articles_found' => 3], 0),
            $this->finding('content_quality', 'warning', 'One sampled page has limited visible text', 'One page falls below the product\'s internal 350-word review heuristic. A concise page may still be appropriate for its purpose.', 'Check whether the page answers its intended question; avoid adding filler.', ['thin_pages' => 1, 'pages_checked' => 10], 5),
            $this->finding('trust', 'pass', 'A privacy-page link was detected', 'A matching link was found on the home page. The crawl did not assess the destination page\'s legal adequacy or completeness.', null, ['path' => 'privacy'], 0),
            $this->finding('trust', 'info', 'Author signal detected in the sample', 'An author-related signal appeared in sampled text. Confirm that it identifies the relevant author and reflects accurate information.', 'Keep author and publisher information current and substantiated.', [], 0),
            $this->finding('seo', 'pass', 'A sitemap URL list was detected', 'The illustrative crawl extracted URLs from a sitemap response. This does not guarantee indexing.', null, ['urls' => 42], 0),
            $this->finding('technical', 'warning', 'Response time varied between sample runs', 'A single response-time measurement is not conclusive. Network conditions, caching and server load can change results.', 'Repeat performance measurements before prioritising infrastructure changes.', ['duration_ms' => 1800], 5),
            $this->finding('traffic', 'info', 'Traffic quality was not assessed', 'A public crawl cannot inspect analytics dashboards, sessions, referrals, bots, engagement or click-through rates.', 'Review your own analytics and search-console evidence separately.', [], 0),
            $this->finding('technical', 'pass', 'The sample URL used HTTPS', 'The illustrative URL uses a secure connection. This does not establish partner eligibility.', null, [], 0),
        ];

        return [
            [
                'days_ago' => 26,
                'pages_crawled' => 8,
                'network_shift' => 0,
                'findings' => $beforeFindings,
                'analysis' => [
                    'website_quality' => 'Illustrative sample: a limited set of public pages was available to the crawler. Inspect the report evidence before treating any finding as fact.',
                    'content_quality' => 'The sample did not reveal article-style URLs and several pages had limited visible text. These are crawl-based observations, not a universal content threshold.',
                    'traffic_quality' => 'Not assessed from a public crawl. Use publisher-side analytics and traffic-source evidence.',
                    'policy_risks' => 'No policy verdict can be established from this sample. Check current network policies against the actual content and use case.',
                    'trust_eeat' => 'A privacy link and author signal were not detected in the sample. They may exist elsewhere or be labelled differently.',
                    'seo_indexing' => 'The sample did not detect a home-page title or sitemap list. Validate the site with dedicated crawling and indexing tools.',
                ],
                'strengths' => ['The sample URL uses HTTPS.', 'The report separates public observations from private data that cannot be assessed.'],
                'risks' => ['Limited crawl coverage may miss important pages.', 'Some sampled pages have limited visible text by an internal heuristic.'],
                'priority_fixes' => ['Review the title markup and public sitemap configuration.', 'Inspect sampled short pages for missing useful information.', 'Confirm that privacy and publisher information are easy to find.'],
                'roadmap' => [
                    ['title' => 'Review crawl coverage', 'description' => 'Check the pages discovered by the sample and add important public URLs to navigation or a sitemap where appropriate.'],
                    ['title' => 'Inspect short pages', 'description' => 'Confirm that concise pages still answer their intended questions; avoid writing to an arbitrary word target.'],
                    ['title' => 'Check publisher information', 'description' => 'Make contact, privacy and authorship information discoverable where relevant to your site.'],
                    ['title' => 'Review private evidence separately', 'description' => 'Use analytics and account data to assess traffic and current partner eligibility; the public crawl cannot do this.'],
                ],
                'summary' => 'Illustrative sample only. The index summarises product-defined checks over a limited public-page crawl; it is not an approval prediction.',
            ],
            [
                'days_ago' => 2,
                'pages_crawled' => 10,
                'network_shift' => 8,
                'findings' => $afterFindings,
                'analysis' => [
                    'website_quality' => 'Illustrative follow-up sample: more article-style pages and a sitemap list were detected. A larger sample does not prove that the full site is complete.',
                    'content_quality' => 'One sampled page falls below an internal text-depth heuristic. The right treatment depends on its purpose and reader value.',
                    'traffic_quality' => 'Not assessed from a public crawl. Use publisher-side analytics and traffic-source evidence.',
                    'policy_risks' => 'No policy verdict can be established from this sample. Confirm current rules directly with each selected network.',
                    'trust_eeat' => 'A privacy link and author-related signal were detected, but the crawl did not verify the accuracy or adequacy of their content.',
                    'seo_indexing' => 'A sitemap URL list was detected. A sitemap does not guarantee crawling or indexing.',
                ],
                'strengths' => ['Article-style URLs and a sitemap list were detected in the sample.', 'A privacy-page link and author-related signal were found.'],
                'risks' => ['One sampled page has limited visible text by an internal heuristic.', 'Traffic and account eligibility remain unassessed.'],
                'priority_fixes' => ['Review the short page in context.', 'Use analytics and current network terms to assess private eligibility factors.'],
                'roadmap' => [
                    ['title' => 'Check the flagged page', 'description' => 'Decide whether its current length and structure serve the reader; do not expand it merely to meet a word count.'],
                    ['title' => 'Validate the sitemap', 'description' => 'Check that listed URLs are canonical, public and useful. A sitemap is a discovery aid, not an indexing guarantee.'],
                    ['title' => 'Verify publisher information', 'description' => 'Open the detected privacy and author destinations to confirm their content and accuracy.'],
                    ['title' => 'Review analytics and terms', 'description' => 'Assess traffic, audience and account conditions with private data and the network\'s current published requirements.'],
                ],
                'summary' => 'Illustrative follow-up sample. Some public signals changed, while traffic, account data and partner eligibility remain unassessed.',
            ],
        ];
    }

    private function finding(
        string $category,
        string $severity,
        string $title,
        string $detail,
        ?string $recommendation,
        array $evidence = [],
        int $weight = 0,
    ): array {
        return [
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'detail' => $detail,
            'recommendation' => $recommendation,
            'evidence' => $evidence,
            'weight' => $weight,
        ];
    }

    /** @return array<string,array{label:string,ready:bool,score:int,notes:array<int,string>}> */
    private function sampleNetworkSignals(int $shift): array
    {
        $networks = [
            'adsense' => ['Google AdSense', 44],
            'adx' => ['Google Ad Manager', 31],
            'ezoic' => ['Ezoic', 48],
            'mediavine' => ['Mediavine', 38],
            'raptive' => ['Raptive', 36],
        ];
        $results = [];

        foreach ($networks as $key => [$label, $score]) {
            $results[$key] = [
                'label' => $label,
                'ready' => false,
                'score' => max(0, min(100, $score + $shift)),
                'notes' => [
                    'Illustrative sample value only; not an official requirement score.',
                    'Traffic, account status and current eligibility are not assessed.',
                ],
            ];
        }

        return $results;
    }
}

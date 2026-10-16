<?php

namespace App\Services\Audit;

/**
 * Summarises public-page signals by network. These checks are not official
 * eligibility requirements and cannot verify traffic, account status or a
 * network's current application terms.
 */
class AdNetworkAnalyzer
{
    /**
     * @return array<string,array{label:string,ready:bool,score:int,notes:array<int,string>}>
     */
    public function analyze(SiteSnapshot $snapshot): array
    {
        $home = $snapshot->home;
        $posts = count($snapshot->posts());
        $averageWords = $snapshot->averageWordCount();
        $hasContact = $this->hasLink($home, ['contact', 'contact-us', 'support']);
        $hasAbout = $this->hasLink($home, ['about', 'about-us', 'our-story']);
        $hasPrivacy = $this->hasLink($home, ['privacy', 'privacy-policy', 'privacy-notice']);
        $hasTrustPages = $hasContact && $hasAbout && $hasPrivacy;
        $hasContent = $posts > 0 && $averageWords >= 350;
        $hasMobileViewport = filled($home?->viewport);
        $hasSitemap = $snapshot->sitemapParsed && count($snapshot->sitemapUrls) > 0;

        $definitions = [
            'adsense' => [
                'label' => 'Google AdSense',
                'checks' => [
                    ['Article-style pages detected', $posts > 0, "{$posts} article-style pages were found in the crawl. This is not a fixed post-count threshold."],
                    ['Useful page depth signal', $averageWords >= 350, "Sampled pages average {$averageWords} words. Word count alone does not determine approval."],
                    ['About, contact and privacy links', $hasTrustPages, 'At least one visible link to an about, contact and privacy page was not found.'],
                    ['HTTPS on the audited URL', $snapshot->https, 'The audited URL does not use HTTPS.'],
                    ['Mobile viewport declared', $hasMobileViewport, 'The home page has no visible mobile viewport declaration.'],
                    ['Sitemap discoverable', $hasSitemap, 'A parseable sitemap was not found at the standard location.'],
                ],
            ],
            'adx' => [
                'label' => 'Google Ad Manager',
                'checks' => [
                    ['Article-style pages detected', $posts > 0, 'No article-style pages were detected in the sampled crawl.'],
                    ['Useful page depth signal', $hasContent, 'The sampled site content may need more substance.'],
                    ['About, contact and privacy links', $hasTrustPages, 'At least one visible link to an about, contact and privacy page was not found.'],
                    ['HTTPS and mobile signals', $snapshot->https && $hasMobileViewport, 'HTTPS or a mobile viewport declaration is missing.'],
                ],
            ],
            'ezoic' => [
                'label' => 'Ezoic',
                'checks' => [
                    ['Article-style pages detected', $posts > 0, 'No article-style pages were detected in the sampled crawl.'],
                    ['Useful page depth signal', $hasContent, 'The sampled site content may need more substance.'],
                    ['About and contact links', $hasAbout && $hasContact, 'An about or contact page link was not found.'],
                    ['HTTPS and mobile signals', $snapshot->https && $hasMobileViewport, 'HTTPS or a mobile viewport declaration is missing.'],
                ],
            ],
            'mediavine' => [
                'label' => 'Mediavine',
                'checks' => [
                    ['Original article pages detected', $posts > 0, 'No article-style pages were detected in the sampled crawl.'],
                    ['Useful page depth signal', $hasContent, 'The sampled site content may need more substance.'],
                    ['Author or byline signal', $this->hasAuthor($snapshot), 'No clear author or byline signal was found in sampled page text.'],
                    ['About and privacy links', $hasAbout && $hasPrivacy, 'An about or privacy page link was not found.'],
                ],
            ],
            'raptive' => [
                'label' => 'Raptive',
                'checks' => [
                    ['Original article pages detected', $posts > 0, 'No article-style pages were detected in the sampled crawl.'],
                    ['Useful page depth signal', $hasContent, 'The sampled site content may need more substance.'],
                    ['Author and contact signals', $this->hasAuthor($snapshot) && $hasContact, 'An author or contact signal was not found.'],
                    ['HTTPS and mobile signals', $snapshot->https && $hasMobileViewport, 'HTTPS or a mobile viewport declaration is missing.'],
                ],
            ],
        ];

        $results = [];

        foreach ($definitions as $key => $definition) {
            $passed = 0;
            $notes = [];

            foreach ($definition['checks'] as [$checkLabel, $passedCheck, $note]) {
                if ($passedCheck) {
                    $passed++;

                    continue;
                }

                $notes[] = $checkLabel.': '.$note;
            }

            $checkCount = max(1, count($definition['checks']));
            $results[$key] = [
                'label' => $definition['label'],
                'ready' => $passed === $checkCount,
                'score' => (int) round($passed / $checkCount * 100),
                'notes' => $notes,
            ];
        }

        $limitations = 'Public-page signals only. This crawl cannot verify traffic, sessions, revenue, account history, ownership, invitation status or current partner eligibility. Confirm current requirements with the network.';

        foreach ($results as &$result) {
            $result['notes'][] = $limitations;
        }
        unset($result);

        return $results;
    }

    private function hasAuthor(SiteSnapshot $snapshot): bool
    {
        foreach ($snapshot->pages as $page) {
            $text = strtolower($page->document->text);

            if (preg_match('/\b(?:written\s+by|author|posted\s+by|reviewed\s+by)\b/', $text)) {
                return true;
            }
        }

        return false;
    }

    private function hasLink(?HtmlDocument $document, array $needles): bool
    {
        if (! $document) {
            return false;
        }

        foreach ($document->links as $link) {
            $path = strtolower(trim((string) parse_url($link['href'], PHP_URL_PATH), '/'));
            $text = strtolower(trim($link['text']));

            foreach ($needles as $needle) {
                if ($path !== '' && str_contains($path, $needle)) {
                    return true;
                }

                if ($text !== '' && str_contains($text, str_replace('-', ' ', $needle))) {
                    return true;
                }
            }
        }

        return false;
    }
}

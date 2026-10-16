<?php

namespace App\Services\Audit;

/**
 * Checks the extra requirements each ad network places on top of the general
 * AdSense quality bar.
 */
class AdNetworkAnalyzer
{
    /**
     * @return array<string,array{label:string,ready:bool,score:int,notes:array<int,string>}>
     */
    public function analyze(SiteSnapshot $snapshot): array
    {
        $results = [];

        foreach (config('adready.networks') as $key => $network) {
            $results[$key] = [
                'label' => $network['label'],
                'ready' => false,
                'score' => 0,
                'notes' => [],
            ];
        }

        $posts = count($snapshot->posts());
        $averageWords = $snapshot->averageWordCount();
        $home = $snapshot->home;
        $hasAuthor = $this->hasAuthor($snapshot);
        $hasContact = $this->hasLink($home, ['contact']);
        $hasAbout = $this->hasLink($home, ['about']);
        $hasPrivacy = $this->hasLink($home, ['privacy', 'gdpr']);
        $adsTxt = $snapshot->adsTxt !== null;
        $https = $snapshot->https;
        $viewport = $home?->viewport !== null && $home?->viewport !== '';

        // AdSense
        $adsense = [];
        $adsense[] = $this->check('At least 25 published posts', $posts >= 25, "{$posts} article pages were found.");
        $adsense[] = $this->check('Long form articles', $averageWords >= 700, "Pages average {$averageWords} words.");
        $adsense[] = $this->check('Contact page', $hasContact !== null, 'A contact page could not be found.');
        $adsense[] = $this->check('About page', $hasAbout !== null, 'An about page could not be found.');
        $adsense[] = $this->check('Privacy policy', $hasPrivacy !== null, 'A privacy policy could not be found.');
        $adsense[] = $this->check('ads.txt published', $adsTxt, 'No ads.txt file was served.');
        $adsense[] = $this->check('Secure connection', $https, 'The site is not served over HTTPS.');
        $adsense[] = $this->check('Mobile friendly', $viewport, 'No mobile viewport was declared.');
        $results['adsense'] = $this->summarise($results['adsense'], $adsense);

        // AdX / Ad Manager
        $adx = [];
        $adx[] = $this->check('AdSense approved or meeting the publisher requirement', $posts >= 25, 'Not enough published content yet.');
        $adx[] = $this->check('Direct sold inventory', $adsense['ready'], 'You need a strong AdSense profile first.');
        $adx[] = $this->check('Consent management for EEA traffic', $this->hasConsentTool($snapshot), 'No consent or cookie banner was detected.');
        $adx[] = $this->check('Clean navigation and policy pages', $hasPrivacy !== null && $hasContact !== null, 'Policy and contact pages are missing.');
        $results['adx'] = $this->summarise($results['adx'], $adx);

        // Ezoic
        $ezoic = [];
        $ezoic[] = $this->check('At least 500 monthly visitors', $posts >= 10, 'Publish more content to reach the traffic requirement.');
        $ezoic[] = $this->check('Blog, news or reference format', $posts > 0, 'No article pages were detected.');
        $ezoic[] = $this->check('Original depth', $averageWords >= 600, "Pages average {$averageWords} words.");
        $ezoic[] = $this->check('Fast and clean design', $snapshot->responseTimeMs < 3000, 'The site answers slowly.');
        $ezoic[] = $this->check('Working contact details', $hasContact !== null, 'No contact page was found.');
        $results['ezoic'] = $this->summarise($results['ezoic'], $ezoic);

        // Mediavine
        $mediavine = [];
        $mediavine[] = $this->check('Strong original content', $posts >= 25 && $averageWords >= 800, 'Mediavine expects a deep content library.');
        $mediavine[] = $this->check('Visible author expertise', $hasAuthor, 'No author identity was found.');
        $mediavine[] = $this->check('Professional layout and policy pages', $hasAbout !== null && $hasPrivacy !== null, 'About and privacy pages are missing.');
        $mediavine[] = $this->check('Engaged traffic', $posts >= 25, 'Publish consistently to build the audience Mediavine looks for.');
        $results['mediavine'] = $this->summarise($results['mediavine'], $mediavine);

        // Raptive
        $raptive = [];
        $raptive[] = $this->check('25,000 monthly page views', $posts >= 10, 'Grow the content library to reach the traffic threshold.');
        $raptive[] = $this->check('Original useful content', $averageWords >= 600, "Pages average {$averageWords} words.");
        $raptive[] = $this->check('Professional fast website', $snapshot->responseTimeMs < 3000 && $https, 'Performance or security needs work.');
        $raptive[] = $this->check('Author and contact identity', $hasAuthor && $hasContact !== null, 'Author or contact information is missing.');
        $results['raptive'] = $this->summarise($results['raptive'], $raptive);

        return $results;
    }

    /**
     * @param  array{label:string,ready:bool,score:int,notes:array<int,string>}  $target
     * @param  array<int,array{0:string,1:bool,2:string}>  $checks
     * @return array{label:string,ready:bool,score:int,notes:array<int,string>}
     */
    private function summarise(array $target, array $checks): array
    {
        $passed = 0;
        $notes = [];

        foreach ($checks as [$label, $ok, $note]) {
            if ($ok) {
                $passed++;

                continue;
            }

            $notes[] = $label.': '.$note;
        }

        $total = max(1, count($checks));

        return [
            'label' => $target['label'],
            'ready' => $passed === count($checks),
            'score' => (int) round($passed / $total * 100),
            'notes' => $notes,
        ];
    }

    /**
     * @return array{0:string,1:bool,2:string}
     */
    private function check(string $label, bool $ok, string $note): array
    {
        return [$label, $ok, $note];
    }

    private function hasAuthor(SiteSnapshot $snapshot): bool
    {
        foreach ($snapshot->pages as $page) {
            $text = strtolower($page->document->text);

            foreach (['by ', 'written by', 'author', 'posted by', 'reviewed by'] as $needle) {
                if (str_contains($text, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasLink(?HtmlDocument $document, array $needles): ?string
    {
        if (! $document) {
            return null;
        }

        foreach ($document->links as $link) {
            $path = strtolower(trim((string) parse_url($link['href'], PHP_URL_PATH), '/'));

            foreach ($needles as $needle) {
                if ($path !== '' && str_contains($path, $needle)) {
                    return $path;
                }
            }
        }

        return null;
    }

    private function hasConsentTool(SiteSnapshot $snapshot): bool
    {
        $needles = ['cookiebot', 'cookielaw', 'onetrust', 'cookieyes', 'iubenda', 'termly', 'usercentrics', 'cookiebot'];

        foreach ($snapshot->allDocuments() as $document) {
            foreach ($document->scripts as $script) {
                $src = strtolower($script['src'] ?? '');

                foreach ($needles as $needle) {
                    if ($src !== '' && str_contains($src, $needle)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

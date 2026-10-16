<?php

namespace App\Services\Audit;

use Illuminate\Support\Str;

/**
 * Deterministic checks that mirror what a human AdSense reviewer looks at.
 * Every finding carries a category, a severity, an explanation and a concrete
 * fix so the user can act on the report.
 */
class RuleEngine
{
    /** Phrases that scream "written by a model" to both readers and reviewers. */
    private const AI_PHRASES = [
        'delve into', 'delve deeper', 'in today\'s fast-paced', 'in today\'s digital world',
        'it\'s important to note', 'navigate the complexities', 'unlock the potential',
        'game-changer', 'game changer', 'in conclusion', 'testament to', 'a testament to',
        'tapestry', 'embark on', 'elevate your', 'in the ever-evolving', 'whether you\'re a',
        'this comprehensive guide', 'robust framework', 'seamlessly integrate', 'leverage the power',
        'the world of', 'landscape of', 'at the end of the day', 'it\'s worth noting',
    ];

    private const SENSITIVE_TOPICS = [
        'casino', 'gambling', 'betting', 'poker', 'sports betting', 'loans', 'payday loan',
        'credit card', 'forex', 'crypto signals', 'weight loss pills', 'supplements', 'medicine',
        'prescription', 'abortion', 'adult', 'escort', 'dating', 'bitcoin investment',
    ];

    private const UNSAFE_DOWNLOADS = [
        '.exe', '.apk', '.msi', '.dmg', '.torrent', '.crack', '.keygen', '.zip', '.rar', '.7z',
    ];

    /**
     * @return array<int,array<string,mixed>>
     */
    public function check(SiteSnapshot $snapshot): array
    {
        $findings = array_merge(
            $this->contentChecks($snapshot),
            $this->policyChecks($snapshot),
            $this->trustChecks($snapshot),
            $this->seoChecks($snapshot),
            $this->technicalChecks($snapshot),
            $this->trafficChecks($snapshot),
        );

        return $findings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function contentChecks(SiteSnapshot $snapshot): array
    {
        $findings = [];
        $home = $snapshot->home;

        if (! $home) {
            return $findings;
        }

        $homeWords = $home->wordCount;

        if ($homeWords < 300) {
            $findings[] = $this->finding(
                'content_quality',
                'warning',
                'Home page is thin',
                "The home page carries {$homeWords} words of visible text. Reviewers expect a home page that shows what the site is about and links to substantive content.",
                'Rewrite the home page so it explains the topic, shows your best writing and links to at least five strong articles.',
                ['home_page_words' => $homeWords],
                6
            );
        } else {
            $findings[] = $this->finding(
                'content_quality',
                'pass',
                'Home page has meaningful content',
                "The home page carries {$homeWords} words, which is a healthy amount of original copy.",
                null,
                ['home_page_words' => $homeWords]
            );
        }

        $average = $snapshot->averageWordCount;

        if ($average > 0 && $average < 500) {
            $findings[] = $this->finding(
                'content_quality',
                'warning',
                'Average page length is below the review threshold',
                "Pages average {$average} words. Networks that judge quality look for pages that genuinely answer a question, and 800 words or more is the safe range for a content site.",
                'Expand the weakest pages with first hand experience, examples and step by step instructions rather than padding them.',
                ['average_words' => $average],
                8
            );
        }

        $posts = $snapshot->posts();
        $postCount = count($posts);

        if ($postCount < 10) {
            $findings[] = $this->finding(
                'content_quality',
                $postCount < 5 ? 'critical' : 'warning',
                'Not enough published articles',
                "Only {$postCount} article pages were found. AdSense reviewers usually want to see a library of at least 25 to 30 original posts before a site is monetised.",
                'Publish consistently. A steady schedule of two to three well researched posts a week for two months is more convincing than ten thin posts.',
                ['articles_found' => $postCount],
                14
            );
        } else {
            $findings[] = $this->finding(
                'content_quality',
                'pass',
                'The site has a real content library',
                "{$postCount} article pages were crawled, which shows the site is more than a shell.",
                null,
                ['articles_found' => $postCount]
            );
        }

        $thin = $snapshot->thinPages();

        if ($thin !== [] && count($snapshot->pages) > 0) {
            $ratio = round(count($thin) / count($snapshot->pages) * 100);

            if ($ratio >= 30) {
                $findings[] = $this->finding(
                    'content_quality',
                    'critical',
                    'Most pages are thin',
                    "{$ratio} percent of the crawled pages have fewer than 350 words. Pages that thin are treated as low value content.",
                    'Either deepen the existing pages or remove the ones that have nothing useful to say. Do not leave empty category and tag archives indexed.',
                    ['thin_pages' => count($thin), 'pages_checked' => count($snapshot->pages)],
                    15
                );
            }
        }

        $aiHits = $this->detectAiPhrases($home->text);

        if ($aiHits !== []) {
            $findings[] = $this->finding(
                'content_quality',
                'warning',
                'Phrasing that reads like machine written copy',
                'The page contains stock phrases that both readers and reviewers recognise as generated text: '.implode(', ', array_slice($aiHits, 0, 5)).'.',
                'Rewrite those sentences in your own voice. Describe what you have actually seen or tested, and cut the filler introductions.',
                ['phrases' => array_slice($aiHits, 0, 8)],
                9
            );
        }

        $stuffed = $this->detectKeywordStuffing($home->text);

        if ($stuffed !== []) {
            $findings[] = $this->finding(
                'content_quality',
                'warning',
                'Possible keyword stuffing',
                'These terms take an unusually large share of the text: '.implode(', ', array_keys($stuffed)).'.',
                'Use each term where it reads naturally. Cover the topic properly instead of repeating one phrase.',
                ['terms' => $stuffed],
                7
            );
        }

        $titles = [];
        foreach ($snapshot->pages as $page) {
            if ($page->document->title) {
                $titles[$page->document->title] = ($titles[$page->document->title] ?? 0) + 1;
            }
        }
        $duplicates = array_filter($titles, fn (int $count) => $count > 1);

        if ($duplicates !== []) {
            $findings[] = $this->finding(
                'content_quality',
                'warning',
                'Duplicate page titles',
                count($duplicates).' page titles appear more than once, which makes the pages look like copies of each other.',
                'Give every page a title that matches the page itself and drop the site name from all but one place on the page.',
                ['duplicate_titles' => array_keys($duplicates)],
                6
            );
        }

        if ($home->imagesWithoutAlt() > 0 && count($home->images) > 0) {
            $missing = $home->imagesWithoutAlt();
            $total = count($home->images);

            $findings[] = $this->finding(
                'content_quality',
                $missing / $total > 0.5 ? 'warning' : 'info',
                'Images are missing alt text',
                "{$missing} of {$total} images on the home page have no alt attribute.",
                'Describe each image in a sentence a screen reader user would understand. Alt text also helps image search traffic.',
                ['missing' => $missing, 'total' => $total]
            );
        }

        return $findings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function policyChecks(SiteSnapshot $snapshot): array
    {
        $findings = [];
        $home = $snapshot->home;

        if (! $home) {
            return $findings;
        }

        if (! $snapshot->adsTxt) {
            $findings[] = $this->finding(
                'policy',
                'warning',
                'ads.txt is missing',
                'No ads.txt file was served at /ads.txt. Ad networks use this file to list who is allowed to sell your inventory, and its absence is a trust signal against you.',
                'Create an ads.txt file listing the ad networks you work with, for example Google, Media.net and your direct sponsors.',
                [],
                8
            );
        }

        $copyright = $this->detectCopyright($home->text);

        if ($copyright === null) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'No copyright notice found',
                'The home page does not show a copyright notice, so ownership of the content is not obvious.',
                'Add a footer line such as "Copyright 2026 Your Site Name. All rights reserved."',
                []
            );
        } elseif ($copyright < (int) date('Y')) {
            $findings[] = $this->finding(
                'policy',
                'warning',
                'Copyright notice is out of date',
                "The copyright line says {$copyright} while the current year is ".date('Y').'.',
                'Update the year in your footer. It is a small detail but reviewers do notice it.',
                ['year' => $copyright],
                4
            );
        } else {
            $findings[] = $this->finding(
                'policy',
                'pass',
                'Copyright notice is present and current',
                "The footer shows a {$copyright} copyright notice.",
                null,
                ['year' => $copyright]
            );
        }

        $unsafe = $this->detectUnsafeLinks($home);

        if ($unsafe !== []) {
            $findings[] = $this->finding(
                'policy',
                'critical',
                'Links to unsafe downloads',
                'The site links to files that reviewers treat as unsafe content: '.implode(', ', array_slice($unsafe, 0, 5)).'.',
                'Remove any download, crack, torrent or installer links until you can hold a proper licence for the files.',
                ['links' => array_slice($unsafe, 0, 10)],
                20
            );
        }

        if ($this->hasAutoRedirect($home)) {
            $findings[] = $this->finding(
                'policy',
                'critical',
                'The page redirects visitors automatically',
                'A meta refresh or automatic script redirect was detected. Automatic redirects are one of the clearest signs of a deceptive site.',
                'Remove the automatic redirect and let visitors stay on the page they asked for.',
                [],
                18
            );
        }

        $adScripts = $this->countAdScripts($home);

        if ($adScripts >= 6) {
            $findings[] = $this->finding(
                'policy',
                'warning',
                'A lot of advertising scripts on one page',
                "{$adScripts} advertising or analytics scripts load on the home page. Reviewers see dense ad placement as a low value experience.",
                'Reduce ad units per screen, keep the placement clear and leave breathing room around the content.',
                ['ad_scripts' => $adScripts],
                7
            );
        }

        $hiddenText = $this->detectHiddenText($home);

        if ($hiddenText) {
            $findings[] = $this->finding(
                'policy',
                'critical',
                'Hidden text detected',
                'Keywords appear in text that is styled to be invisible to visitors. Hidden text is treated as manipulation.',
                'Delete the hidden block. Use visible copy instead.',
                [],
                16
            );
        }

        $sensitive = $this->detectSensitiveTopics($home->text);

        if ($sensitive !== []) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'The topic sits close to a policy sensitive area',
                'The site mentions: '.implode(', ', array_slice($sensitive, 0, 4)).'. These topics are monetised, but they attract far closer review.',
                'Keep the site away from claims about earnings, cures and guaranteed outcomes, and document any affiliations clearly.',
                ['topics' => $sensitive],
                5
            );
        }

        return $findings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function trustChecks(SiteSnapshot $snapshot): array
    {
        $findings = [];
        $home = $snapshot->home;

        if (! $home) {
            return $findings;
        }

        $links = $this->linkPaths($home);

        $required = [
            'About page' => ['about', 'about-us', 'who-we-are', 'our-story', 'aboutus'],
            'Contact page' => ['contact', 'contact-us', 'get-in-touch', 'support'],
            'Privacy policy' => ['privacy', 'privacy-policy', 'privacy-notice', 'gdpr'],
            'Disclaimer' => ['disclaimer', 'terms', 'terms-of-use', 'terms-and-conditions', 'legal'],
        ];

        foreach ($required as $label => $needles) {
            $found = $this->hasLinkMatching($links, $needles);

            if (! $found) {
                $severity = $label === 'Privacy policy' ? 'critical' : 'warning';

                $findings[] = $this->finding(
                    'trust',
                    $severity,
                    "{$label} is missing",
                    "No {$label} was linked from the pages we crawled. Trust and safety checks look for these pages before they look at your articles.",
                    "Add a {$label} page, link it in the footer and make it specific to your site rather than a generic template.",
                    [],
                    $severity === 'critical' ? 14 : 9
                );
            } else {
                $findings[] = $this->finding(
                    'trust',
                    'pass',
                    "{$label} is in place",
                    "A {$label} was found and linked from the site.",
                    null,
                    ['path' => $found]
                );
            }
        }

        $hasAuthor = $this->detectAuthorSignals($snapshot);
        $hasSchemaPerson = $this->hasPersonSchema($snapshot);

        if (! $hasAuthor && ! $hasSchemaPerson) {
            $findings[] = $this->finding(
                'trust',
                'warning',
                'No visible author or expert identity',
                'Nothing on the pages we crawled identifies who writes the content. Reviewers look for experience and expertise signals, and an anonymous site is harder to trust.',
                'Add an author box on every article with a photo, a short biography and a link to a page about the author.',
                [],
                11
            );
        } else {
            $findings[] = $this->finding(
                'trust',
                'pass',
                'Authorship is visible',
                $hasAuthor
                    ? 'Article pages name an author or show a byline.'
                    : 'Structured data identifies the author of the content.',
                null,
                []
            );
        }

        $social = $this->countSocialLinks($home);

        if ($social === 0) {
            $findings[] = $this->finding(
                'trust',
                'info',
                'No social profiles linked',
                'Visitors get no way to follow the site outside your own pages.',
                'Link your social profiles in the footer. It helps reviewers verify that a real person is behind the site.',
                [],
                3
            );
        }

        $hasContactDetails = $this->detectContactDetails($home->text) || $this->hasLinkMatching($links, ['contact']);

        if (! $hasContactDetails) {
            $findings[] = $this->finding(
                'trust',
                'warning',
                'No visible contact details',
                'Visitors cannot find a phone number, address or business name anywhere on the home page.',
                'Show a working email address, and add a contact page with a form that actually reaches you.',
                [],
                8
            );
        }

        $hasOrgSchema = $this->hasOrganizationSchema($snapshot);

        if (! $hasOrgSchema) {
            $findings[] = $this->finding(
                'trust',
                'info',
                'No organisation structured data',
                'The site does not describe itself with organisation structured data, so search engines cannot confirm who publishes it.',
                'Add Organization or Person JSON-LD with your name, logo, contact details and social profiles.',
                [],
                3
            );
        }

        return $findings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function seoChecks(SiteSnapshot $snapshot): array
    {
        $findings = [];
        $home = $snapshot->home;

        if (! $home) {
            return $findings;
        }

        if (! $snapshot->robots) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'robots.txt is missing',
                'No robots.txt file was found. Reviewers also check that you are not hiding anything from crawlers.',
                'Publish a robots.txt that allows search engines and blocks nothing important.',
                [],
                8
            );
        } else {
            $robots = strtolower($snapshot->robots->body);
            $blocksAds = (str_contains($robots, 'user-agent: googleadsbot') && preg_match('/user-agent:\s*googleadsbot.*?disallow:\s*\/\s*$/ms', $robots))
                || (str_contains($robots, 'user-agent: mediapartners-google') && preg_match('/user-agent:\s*mediapartners-google.*?disallow:\s*\/\s*$/ms', $robots));

            if ($blocksAds) {
                $findings[] = $this->finding(
                    'seo',
                    'critical',
                    'robots.txt blocks the advertising crawlers',
                    'Google AdsBot or Media Partners Google is disallowed in robots.txt, so the ad review system cannot read your pages.',
                    'Allow Google AdsBot and Media Partners Google. Block them only for sections that truly must stay private.',
                    [],
                    20
                );
            } else {
                $findings[] = $this->finding(
                    'seo',
                    'pass',
                    'robots.txt is published and does not block ad crawlers',
                    'A robots.txt file was found and the advertising crawlers are allowed.',
                    null,
                    []
                );
            }
        }

        if (! $snapshot->sitemap) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'No XML sitemap',
                'The site does not serve a sitemap.xml, so you are relying on search engines to find every page on their own.',
                'Generate a sitemap that lists your posts and main pages, reference it in robots.txt and submit it in Google Search Console.',
                [],
                9
            );
        } elseif (! $snapshot->sitemapParsed) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'The sitemap could not be read',
                'A sitemap file was served but no page list was found inside it, so it may be broken or empty.',
                'Regenerate the sitemap and check that it contains loc entries for your real pages.',
                [],
                7
            );
        } else {
            $findings[] = $this->finding(
                'seo',
                'pass',
                'A working XML sitemap is published',
                count($snapshot->sitemapUrls).' URLs are listed in your sitemap.',
                null,
                ['urls' => count($snapshot->sitemapUrls)]
            );
        }

        if (blank($home->canonical)) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'No canonical tag on the home page',
                'Without a canonical tag a site with several URL variants can be seen as duplicate content.',
                'Add a self referencing canonical tag to every page, and keep one address per article.',
                [],
                6
            );
        }

        $description = $home->description;
        $descriptionLength = $description ? mb_strlen($description) : 0;

        if ($descriptionLength === 0) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'Meta description is missing',
                'The home page has no meta description, so search engines write their own snippet.',
                'Write a 140 to 160 character description that says what the site covers and who it helps.',
                [],
                5
            );
        } elseif ($descriptionLength < 70 || $descriptionLength > 170) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Meta description length is off',
                "The meta description is {$descriptionLength} characters. The comfortable range is 140 to 160 characters.",
                'Rewrite the description so it fits the range and still sells the click.',
                ['length' => $descriptionLength],
                3
            );
        }

        $title = $home->title ?? '';
        $titleLength = mb_strlen($title);

        if ($titleLength === 0) {
            $findings[] = $this->finding(
                'seo',
                'critical',
                'The home page has no title',
                'A page without a title cannot be indexed properly and looks broken to a reviewer.',
                'Add a descriptive title of roughly 50 to 60 characters.',
                [],
                12
            );
        } elseif ($titleLength > 70) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Title tag is long',
                "The title is {$titleLength} characters, so search engines will cut it off.",
                'Shorten the title to about 55 characters and move the extra words into the description.',
                ['length' => $titleLength],
                2
            );
        }

        if ($home->headingCount(1) === 0) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'No H1 heading on the home page',
                'The page has no H1, which leaves the topic of the page undeclared.',
                'Use exactly one H1 that names the topic of the page.',
                [],
                5
            );
        } elseif ($home->headingCount(1) > 1) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Several H1 headings on one page',
                'The page uses more than one H1, which splits the topic signal.',
                'Keep one H1 and demote the rest to H2.',
                ['count' => $home->headingCount(1)],
                2
            );
        }

        if ($home->openGraph('title') === null) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Social sharing metadata is missing',
                'Links to your site look plain on social networks because there is no Open Graph data.',
                'Add og:title, og:description, og:image and og:url to your pages.',
                [],
                3
            );
        }

        if ($home->jsonLd === []) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'No structured data',
                'The site does not use JSON-LD, so it cannot show article, breadcrumb or author information in search results.',
                'Add Article, BreadcrumbList and Person structured data to your posts.',
                [],
                3
            );
        }

        $broken = $this->detectBrokenLinks($snapshot);

        if ($broken !== []) {
            $findings[] = $this->finding(
                'seo',
                'critical',
                'Broken internal links',
                'These pages returned an error: '.implode(', ', array_slice($broken, 0, 6)).'.',
                'Fix or remove the broken links. A site full of dead ends reads as abandoned.',
                ['links' => $broken],
                15
            );
        }

        return $findings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function technicalChecks(SiteSnapshot $snapshot): array
    {
        $findings = [];
        $home = $snapshot->home;

        if (! $home) {
            return $findings;
        }

        if (! $snapshot->https) {
            $findings[] = $this->finding(
                'technical',
                'critical',
                'The site is not served over HTTPS',
                'Browsers mark the site as not secure and advertising networks will not serve ads to it.',
                'Install a free certificate, force HTTPS and redirect every HTTP address to the secure one.',
                [],
                20
            );
        } else {
            $findings[] = $this->finding(
                'technical',
                'pass',
                'The site is served over HTTPS',
                'The site loaded over a secure connection.',
                null,
                []
            );
        }

        if (blank($home->viewport)) {
            $findings[] = $this->finding(
                'technical',
                'critical',
                'No mobile viewport is declared',
                'The page does not tell mobile browsers how wide the screen is, so the layout is scaled down and looks broken on a phone.',
                'Add the viewport meta tag with width=device-width and an initial scale of one.',
                [],
                16
            );
        } else {
            $findings[] = $this->finding(
                'technical',
                'pass',
                'Mobile viewport is configured',
                'The page declares a mobile viewport, so it scales correctly on phones.',
                null,
                []
            );
        }

        $weight = $snapshot->homeBytes;

        if ($weight > 2097152) {
            $findings[] = $this->finding(
                'technical',
                'warning',
                'The home page is heavy',
                sprintf('The home page weighs %s KB, which is slow on mobile data.', number_format($weight / 1024)),
                'Compress your images, serve modern formats, and load scripts only where they are needed.',
                ['bytes' => $weight],
                8
            );
        }

        if ($snapshot->responseTimeMs > 3000) {
            $findings[] = $this->finding(
                'technical',
                'warning',
                'The site responds slowly',
                "The home page took ".number_format($snapshot->responseTimeMs / 1000, 1).' seconds to answer.',
                'Turn on page caching, use a fast host near your audience and trim the plugins or scripts that run on every request.',
                ['duration_ms' => $snapshot->responseTimeMs],
                8
            );
        }

        if ($snapshot->homeBytes > 0 && $home->textToHtmlRatio() < 0.08 && $home->wordCount < 500) {
            $findings[] = $this->finding(
                'technical',
                'warning',
                'Most of the page is markup, not content',
                'The page is mostly code and very little readable text, which usually means heavy widgets, sliders or template leftovers.',
                'Remove unused widgets, sidebars and decorative scripts so the content is the page.',
                [],
                7
            );
        }

        if (! $home->favicon) {
            $findings[] = $this->finding(
                'technical',
                'info',
                'No favicon is declared',
                'The site does not declare an icon, so browsers show a default placeholder.',
                'Add a small square favicon and reference it in the page head.',
                [],
                1
            );
        }

        return $findings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function trafficChecks(SiteSnapshot $snapshot): array
    {
        $findings = [];
        $home = $snapshot->home;

        if (! $home) {
            return $findings;
        }

        $analytics = $this->detectAnalytics($home);

        if ($analytics === []) {
            $findings[] = $this->finding(
                'traffic',
                'warning',
                'No analytics or search console script found',
                'There is no sign of an analytics property on the pages we crawled. Ad networks like to see that a publisher measures their own traffic.',
                'Install Google Analytics or a privacy friendly alternative, and connect Google Search Console.',
                [],
                7
            );
        } else {
            $findings[] = $this->finding(
                'traffic',
                'pass',
                'Analytics is installed',
                'Found: '.implode(', ', $analytics).'.',
                null,
                ['providers' => $analytics]
            );
        }

        $placeholder = $this->detectPlaceholderAnalytics($home);

        if ($placeholder !== []) {
            $findings[] = $this->finding(
                'traffic',
                'critical',
                'Analytics is still using a placeholder property',
                'These identifiers look like unfilled examples: '.implode(', ', $placeholder).'. Traffic measured with an unfilled property shows as not set, and the ad account sees the same.',
                'Replace the example IDs with your real measurement ID, then confirm that data arrives in your reports.',
                ['identifiers' => $placeholder],
                12
            );
        }

        $spam = $this->detectReferrerSpam($home);

        if ($spam !== []) {
            $findings[] = $this->finding(
                'traffic',
                'warning',
                'Links to known traffic spam sites',
                'The page links to: '.implode(', ', array_slice($spam, 0, 4)).'. These referrers send junk traffic and hurt your approval chances.',
                'Remove the links and set up a referrer spam filter in your analytics.',
                ['referrers' => $spam],
                6
            );
        }

        return $findings;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

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

    /**
     * @return array<int,string>
     */
    private function detectAiPhrases(string $text): array
    {
        $lower = Str::lower($text);
        $hits = [];

        foreach (self::AI_PHRASES as $phrase) {
            if (Str::contains($lower, $phrase)) {
                $hits[] = $phrase;
            }
        }

        return $hits;
    }

    /**
     * @return array<string,float>
     */
    private function detectKeywordStuffing(string $text): array
    {
        $words = str_word_count(strtolower($text));

        if ($words < 120) {
            return [];
        }

        $counts = array_count_values(preg_split('/[^a-z0-9]+/', strtolower($text)) ?: []);
        unset($counts['']);

        $stopWords = ['the', 'and', 'for', 'you', 'that', 'this', 'with', 'from', 'your', 'are', 'was', 'not', 'have', 'has', 'but', 'all', 'can', 'how', 'what', 'when', 'will', 'they', 'their', 'our', 'its', 'about', 'into', 'more', 'than', 'then', 'them', 'these', 'some', 'such', 'only', 'other', 'also', 'been', 'being', 'does', 'did', 'doing', 'get', 'got', 'like', 'make', 'made', 'many', 'much', 'need', 'one', 'two', 'use', 'used', 'using', 'via', 'per', 'etc'];
        $suspicious = [];

        foreach ($counts as $word => $count) {
            if (in_array($word, $stopWords, true) || mb_strlen($word) < 4) {
                continue;
            }

            $share = $count / $words * 100;

            if ($share > 3.0 && $count >= 6) {
                $suspicious[$word] = round($share, 1);
            }
        }

        arsort($suspicious);

        return $suspicious;
    }

    private function detectCopyright(string $text): ?int
    {
        if (preg_match('/(?:©|copyright|copyright|copyright|all rights reserved)\s*(\d{4})/i', $text, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(?:©|copyright)\s*(\d{4})/i', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private function detectUnsafeLinks(HtmlDocument $document): array
    {
        $unsafe = [];

        foreach ($document->links as $link) {
            $href = strtolower($link['href']);

            if ($href === '') {
                continue;
            }

            foreach (self::UNSAFE_DOWNLOADS as $extension) {
                if (str_ends_with($href, $extension)) {
                    $unsafe[] = $link['href'];

                    break;
                }
            }
        }

        return array_values(array_unique($unsafe));
    }

    private function hasAutoRedirect(HtmlDocument $document): bool
    {
        if (preg_match('/<meta[^>]+http-equiv=["\']refresh["\'][^>]*>/i', $document->html)) {
            return true;
        }

        foreach ($document->scripts as $script) {
            if ($script['src'] !== null) {
                continue;
            }

            if (preg_match('/(window\.location\.(replace|assign)|location\.href\s*=|location\.replace\s*=|history\.replaceState)/i', $script['content'] ?? '')) {
                return true;
            }
        }

        return false;
    }

    private function detectHiddenText(HtmlDocument $document): bool
    {
        $patterns = [
            '/style=["\'][^"\']*(display\s*:\s*none|visibility\s*:\s*hidden|opacity\s*:\s*0|text-indent\s*:\s*-\d{3,})/i',
            '/<div[^>]+class=["\'][^"\']*(hidden|sr-only|visually-hidden|off-screen)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $document->html)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int,string>
     */
    private function detectSensitiveTopics(string $text): array
    {
        $lower = Str::lower($text);
        $found = [];

        foreach (self::SENSITIVE_TOPICS as $topic) {
            if (Str::contains($lower, $topic)) {
                $found[] = $topic;
            }
        }

        return $found;
    }

    private function countAdScripts(HtmlDocument $document): int
    {
        $needles = [
            'adsbygoogle', 'googlesyndication', 'doubleclick', 'googleadservices',
            'adslot', 'adunit', 'adservice', 'amazon-adsystem', 'criteo', 'taboola',
            'outbrain', 'adthrive', 'media.net', 'adblade', 'revcontent', 'mgid',
        ];

        $count = 0;

        foreach ($document->scripts as $script) {
            if ($script['src'] === null) {
                continue;
            }

            foreach ($needles as $needle) {
                if (Str::contains(strtolower($script['src']), $needle)) {
                    $count++;

                    break;
                }
            }
        }

        return $count;
    }

    /**
     * @return array<int,string>
     */
    private function linkPaths(HtmlDocument $document): array
    {
        $paths = [];

        foreach ($document->links as $link) {
            $href = $link['href'];

            if ($href === '') {
                continue;
            }

            $path = (string) parse_url($href, PHP_URL_PATH);
            $paths[] = strtolower(trim($path, '/'));
        }

        return $paths;
    }

    /**
     * @param  array<int,string>  $paths
     * @param  array<int,string>  $needles
     */
    private function hasLinkMatching(array $paths, array $needles): ?string
    {
        foreach ($paths as $path) {
            foreach ($needles as $needle) {
                if ($path === $needle || str_contains($path, $needle)) {
                    return $path;
                }
            }
        }

        return null;
    }

    private function detectAuthorSignals(SiteSnapshot $snapshot): bool
    {
        foreach ($snapshot->pages as $page) {
            $text = Str::lower($page->document->text);

            foreach (['by ', 'written by', 'author', 'posted by', 'reviewed by'] as $needle) {
                if (str_contains($text, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasPersonSchema(SiteSnapshot $snapshot): bool
    {
        return $this->hasSchemaType($snapshot, 'person');
    }

    private function hasOrganizationSchema(SiteSnapshot $snapshot): bool
    {
        return $this->hasSchemaType($snapshot, 'organization') || $this->hasSchemaType($snapshot, 'localbusiness');
    }

    private function hasSchemaType(SiteSnapshot $snapshot, string $type): bool
    {
        foreach ($snapshot->pages as $page) {
            foreach ($page->document->jsonLd as $block) {
                $flat = Str::lower(json_encode($block) ?: '');

                if (str_contains($flat, '"@type":"'.$type.'"') || str_contains($flat, '"@type": "'.$type.'"')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function countSocialLinks(HtmlDocument $document): int
    {
        $needles = ['facebook.com', 'twitter.com', 'x.com/', 'instagram.com', 'linkedin.com', 'youtube.com', 'pinterest.', 't.me/', 'threads.net', 'reddit.com'];
        $count = 0;

        foreach ($document->links as $link) {
            $href = strtolower($link['href']);

            foreach ($needles as $needle) {
                if (str_contains($href, $needle)) {
                    $count++;

                    break;
                }
            }
        }

        return $count;
    }

    private function detectContactDetails(string $text): bool
    {
        if (preg_match('/[\w.+-]+@[\w-]+\.[\w.]+/', $text)) {
            return true;
        }

        return (bool) preg_match('/(\+?\d[\d\s().-]{7,}\d)/', $text);
    }

    /**
     * @return array<int,string>
     */
    private function detectAnalytics(HtmlDocument $document): array
    {
        $needles = [
            'google-analytics.com' => 'Google Analytics',
            'googletagmanager.com' => 'Google Tag Manager',
            'gtag/js' => 'Google Analytics',
            'clarity.ms' => 'Microsoft Clarity',
            'facebook.net/en_US/fbevents.js' => 'Meta Pixel',
            'connect.facebook.net' => 'Meta Pixel',
            'hotjar.com' => 'Hotjar',
            'matomo' => 'Matomo',
            'plausible.io' => 'Plausible',
            'yandex.ru/metrika' => 'Yandex Metrica',
        ];

        $found = [];

        foreach ($document->scripts as $script) {
            $src = strtolower($script['src'] ?? '');

            foreach ($needles as $needle => $label) {
                if (str_contains($src, $needle) && ! in_array($label, $found, true)) {
                    $found[] = $label;
                }
            }
        }

        return $found;
    }

    /**
     * @return array<int,string>
     */
    private function detectPlaceholderAnalytics(HtmlDocument $document): array
    {
        $patterns = [
            '/UA-\d{4,}-\d{1,2}/' => 'Universal Analytics id',
            '/G-[A-Z0-9]{4,10}/' => 'Google Analytics 4 id',
            '/GTM-[A-Z0-9]{4,10}/' => 'Tag Manager id',
            '/UA-XXXXX-X/i' => 'example analytics id',
            '/G-XXXXXX/i' => 'example measurement id',
        ];

        $found = [];
        $html = $document->text;

        foreach ($document->scripts as $script) {
            $html .= ' '.$script['src'];
        }

        foreach ($patterns as $pattern => $label) {
            if (preg_match($pattern, $html, $matches)) {
                $value = $matches[0];

                if (preg_match('/X{3,}/i', $value)) {
                    $found[] = $label.' ('.$value.')';
                }
            }
        }

        return $found;
    }

    /**
     * @return array<int,string>
     */
    private function detectReferrerSpam(HtmlDocument $document): array
    {
        $spam = [
            'free-share-buttons.com', 'social-buttons.com', 'buttonshare', 'videos-for-your-business',
            'monetizer.com', 'adcash.com', 'sharebutton.net', 'traffic2money', 'hulfingtonpost',
        ];

        $found = [];

        foreach ($document->links as $link) {
            $href = strtolower($link['href']);

            foreach ($spam as $needle) {
                if (str_contains($href, $needle)) {
                    $found[] = $link['href'];

                    break;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * @return array<int,string>
     */
    private function detectBrokenLinks(SiteSnapshot $snapshot): array
    {
        $broken = [];

        foreach ($snapshot->pages as $page) {
            if ($page->status >= 400) {
                $broken[] = $page->url;
            }
        }

        return array_values(array_unique($broken));
    }
}

<?php

namespace App\Services\Audit;

use Illuminate\Support\Str;

/**
 * Deterministic checks over the pages the crawler could read. Findings are
 * product heuristics, not official network criteria or a substitute for human
 * review; each includes observed evidence and a practical next step.
 */
class RuleEngine
{
    /** Generic stock phrases used only for a non-scoring writing-quality note. */
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
            $this->crawlLimitChecks($snapshot),
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
                'info',
                'Limited visible copy on the home page',
                "The crawler counted {$homeWords} visible words on the home page. This product heuristic does not measure usefulness or establish a network requirement.",
                'Make the site purpose clear and link to your most useful pages; do not add text just to meet a word target.',
                ['home_page_words' => $homeWords],
                0
            );
        } else {
            $findings[] = $this->finding(
                'content_quality',
                'pass',
                'Home page has substantial visible copy',
                "The crawler counted {$homeWords} visible words on the home page. This count alone does not assess quality or originality.",
                null,
                ['home_page_words' => $homeWords]
            );
        }

        $average = $snapshot->averageWordCount();

        if ($average > 0 && $average < 500) {
            $findings[] = $this->finding(
                'content_quality',
                'info',
                'Review the shortest pages for usefulness',
                "Crawled pages average {$average} words. This is a rough product heuristic, not an official or universal word-count requirement.",
                'Check whether short pages answer their intended question. Improve usefulness rather than adding filler to reach a target.',
                ['average_words' => $average],
                0
            );
        }

        $posts = $snapshot->posts();
        $postCount = count($posts);

        if ($postCount === 0) {
            $findings[] = $this->finding(
                'content_quality',
                'warning',
                'No article pages detected',
                "Only {$postCount} article-style pages were detected in this crawl. This is not a fixed post-count requirement, and a limited crawl can miss pages.",
                'Make sure your strongest original pages are discoverable from navigation or a sitemap, then improve usefulness and coverage rather than chasing a post count.',
                ['articles_found' => $postCount],
                4
            );
        } elseif ($postCount < 10) {
            $findings[] = $this->finding(
                'content_quality',
                'info',
                'Article-style pages detected',
                "{$postCount} article-style pages were detected. There is no fixed post-count requirement, and this crawl may not find every page.",
                'Make sure your most useful pages are reachable from navigation or your sitemap.',
                ['articles_found' => $postCount],
                0
            );
        } else {
            $findings[] = $this->finding(
                'content_quality',
                'pass',
                'Article-style pages detected',
                "{$postCount} article-style pages were identified in this limited crawl. This count does not assess page quality.",
                null,
                ['articles_found' => $postCount]
            );
        }

        $thin = $snapshot->thinPages();

        if ($thin !== [] && count($snapshot->contentPages()) > 0) {
            $ratio = round(count($thin) / count($snapshot->contentPages()) * 100);

            if ($ratio >= 30) {
                $findings[] = $this->finding(
                    'content_quality',
                    'warning',
                    'Many sampled pages have limited visible text',
                    "{$ratio} percent of the sampled pages have fewer than 350 visible words. The 350-word cutoff is an internal review heuristic; a short page can still be useful, and some page types are expected to be brief.",
                    'Review these pages for missing answers or context. Keep concise pages that serve a clear purpose; do not add filler or remove useful pages to meet a count.',
                    ['thin_pages' => count($thin), 'pages_checked' => count($snapshot->contentPages())],
                    5
                );
            }
        }

        $aiHits = $this->detectAiPhrases($home->text);

        if ($aiHits !== []) {
            $findings[] = $this->finding(
                'content_quality',
                'info',
                'Generic stock phrases found',
                'Common generic phrases appear on the sampled home page: '.implode(', ', array_slice($aiHits, 0, 5)).'. This wording alone cannot determine who or what wrote the page.',
                'Replace vague openings with specific, useful information and only describe experiences you can substantiate.',
                ['phrases' => array_slice($aiHits, 0, 8)],
                0
            );
        }

        $stuffed = $this->detectKeywordStuffing($home->text);

        if ($stuffed !== []) {
            $findings[] = $this->finding(
                'content_quality',
                'info',
                'Repeated terms on the home page',
                'These terms occur unusually often in the sampled text: '.implode(', ', array_keys($stuffed)).'. This frequency heuristic can flag natural topic language and does not establish manipulation.',
                'Read the surrounding copy for awkward repetition and revise only where it reduces clarity.',
                ['terms' => $stuffed],
                0
            );
        }

        $titles = [];
        foreach ($snapshot->contentPages() as $page) {
            if ($page->document->title) {
                $titles[$page->document->title] = ($titles[$page->document->title] ?? 0) + 1;
            }
        }
        $duplicates = array_filter($titles, fn (int $count) => $count > 1);

        if ($duplicates !== []) {
            $findings[] = $this->finding(
                'content_quality',
                'warning',
                'Repeated page titles in the sample',
                count($duplicates).' title values repeat across sampled pages. This can make search snippets less distinct, but repeated titles alone do not prove duplicate content.',
                'Check whether each title clearly describes its page and distinguish pages where it helps users.',
                ['duplicate_titles' => array_keys($duplicates)],
                3
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
                'Add concise alt text to meaningful images so assistive technologies can describe them; use empty alt text for decorative images.',
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

        if ($snapshot->adsTxtMissing) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'No ads.txt file found at the checked location',
                'The standard /ads.txt URL returned not found. This file is used to declare authorized sellers when you participate in advertising programs; its absence alone does not establish an AdSense approval issue.',
                'If a network asks you to publish ads.txt, use its exact account-specific entry and verify that the file is served from your domain root.',
                [],
                0
            );
        } elseif (! $snapshot->adsTxt && ! $snapshot->adsTxtChecked) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'ads.txt could not be checked',
                'The crawl could not confirm whether /ads.txt is present. The request may have been unavailable or the response-size budget may have been reached.',
                'Check the file directly at your domain root if your advertising partners require it.',
                [],
                0
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
                'info',
                'Copyright year may be outdated',
                "The detected copyright line says {$copyright}; the current year is ".date('Y').'. The crawl cannot determine whether the year range is intentional.',
                'Review the footer and update it if the date range no longer reflects your content.',
                ['year' => $copyright],
                0
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
                'info',
                'Download links found for manual review',
                'The sampled page links to files with these extensions: '.implode(', ', array_slice($unsafe, 0, 5)).'. The file type alone does not show whether a download is unsafe or violates policy.',
                'Check the destination, safety, licensing and context of each file. Remove or clearly label any link that is inappropriate for your audience.',
                ['links' => array_slice($unsafe, 0, 10)],
                0
            );
        }

        if ($this->hasAutoRedirect($home)) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'Automatic navigation code found',
                'The page contains a meta refresh or script that can change location. The crawl cannot tell whether it runs automatically or only after a visitor action.',
                'Check the page in a browser and confirm that navigation occurs only when expected and clearly explained.',
                [],
                0
            );
        }

        $adScripts = $this->countAdScripts($home);

        if ($adScripts >= 6) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'Several advertising-related script references found',
                "The home page markup contains {$adScripts} references to advertising-related scripts. This does not show how many ads are rendered or whether the layout is crowded.",
                'Review the live page for ad density, layout shifts and interference with reading; this script count alone is not a policy finding.',
                ['ad_scripts' => $adScripts],
                0
            );
        }

        $hiddenText = $this->detectHiddenText($home);

        if ($hiddenText) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'Potentially visually hidden text styling found',
                'A style or class associated with visually hidden text was found. This can be intentional for accessible labels and is not evidence of keyword manipulation by itself.',
                'Inspect the specific element in a browser and confirm the hidden content is useful, appropriate and not keyword stuffing.',
                [],
                0
            );
        }

        $sensitive = $this->detectSensitiveTopics($home->text);

        if ($sensitive !== []) {
            $findings[] = $this->finding(
                'policy',
                'info',
                'Policy-related terms found for context review',
                'The keyword scan found: '.implode(', ', array_slice($sensitive, 0, 4)).'. A term match alone does not establish restricted content or a policy violation.',
                'Review the surrounding content against the current policies of each network you plan to use, and make any required disclosures.',
                ['topics' => $sensitive],
                0
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

        $publisherLinks = [
            'About page' => ['about', 'about-us', 'who-we-are', 'our-story', 'aboutus'],
            'Contact page' => ['contact', 'contact-us', 'get-in-touch', 'support'],
            'Privacy policy' => ['privacy', 'privacy-policy', 'privacy-notice', 'gdpr'],
            'Disclaimer' => ['disclaimer', 'terms', 'terms-of-use', 'terms-and-conditions', 'legal'],
        ];

        foreach ($publisherLinks as $label => $needles) {
            $found = $this->hasLinkMatching($links, $needles);

            if (! $found) {
                $severity = $label === 'Privacy policy' ? 'warning' : 'info';

                $findings[] = $this->finding(
                    'trust',
                    $severity,
                    "No {$label} link detected",
                    "The crawl did not find a link matching the expected {$label} paths on the home page. The page may exist under another URL or be linked elsewhere.",
                    "Check that your {$label} is publicly accessible and linked where visitors can find it. Review whether the content is appropriate and current for your site.",
                    [],
                    $severity === 'warning' ? 5 : 0
                );
            } else {
                $findings[] = $this->finding(
                    'trust',
                    'pass',
                    "{$label} is in place",
                    "A home-page link with a matching {$label} path was found. The crawl did not verify the destination page's full content or legal adequacy.",
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
                'info',
                'No clear author signal found in the sample',
                'The crawler did not detect a byline, author term or Person schema in sampled pages. This does not prove that authorship information is absent from the site.',
                'Where useful, identify authors and explain relevant experience so readers can assess the source.',
                [],
                0
            );
        } else {
            $findings[] = $this->finding(
                'trust',
                'pass',
                'Authorship is visible',
                $hasAuthor
                    ? 'An author-related word or byline signal was detected in sampled text; confirm it identifies a real author.'
                    : 'Person structured data was detected; confirm it refers to the relevant author.',
                null,
                []
            );
        }

        $social = $this->countSocialLinks($home);

        if ($social === 0) {
            $findings[] = $this->finding(
                'trust',
                'info',
                'No social profile links found on the home page',
                'No recognised social-profile link was detected in the home-page sample. This does not affect whether your content is useful or establish a network requirement.',
                'Add social links only if they are active and useful to your readers.',
                [],
                0
            );
        }

        $hasContactDetails = $this->detectContactDetails($home->text) || $this->hasLinkMatching($links, ['contact']);

        if (! $hasContactDetails) {
            $findings[] = $this->finding(
                'trust',
                'warning',
                'No contact method detected on the home page',
                'The crawl did not find an email address, phone number or link with a contact-related path in the home-page sample. Contact details may be present elsewhere.',
                'If visitors need to reach you, provide a working contact method and verify that it is publicly accessible.',
                [],
                4
            );
        }

        $hasOrgSchema = $this->hasOrganizationSchema($snapshot);

        if (! $hasOrgSchema) {
            $findings[] = $this->finding(
                'trust',
                'info',
                'Organisation structured data not detected',
                'The crawl did not find Organization or LocalBusiness JSON-LD in sampled pages. This optional markup is not a substitute for clear publisher information.',
                'If relevant, add accurate Organization or Person structured data and validate it with a structured-data testing tool.',
                [],
                0
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

        if ($snapshot->robotsMissing) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'robots.txt not found at the checked location',
                'The standard /robots.txt URL returned not found. Many sites do not need custom rules; this alone is not an indexing or approval issue.',
                'If you publish robots.txt, check that its rules do not unintentionally block pages or resources you want crawled.',
                [],
                0
            );
        } elseif (! $snapshot->robots && ! $snapshot->robotsChecked) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'robots.txt could not be checked',
                'The crawl could not confirm whether /robots.txt is present. The request may have been unavailable or the response-size budget may have been reached.',
                'Check the file directly at your domain root if you rely on custom crawl rules.',
                [],
                0
            );
        } elseif ($snapshot->robots?->truncated) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'robots.txt response was size-limited',
                'Only the beginning of robots.txt was inspected, so rules later in the file may not have been considered.',
                'Review the complete file directly before changing crawler access rules.',
                [],
                0
            );
        } else {
            $robots = strtolower($snapshot->robots->body);
            $blocksAds = (str_contains($robots, 'user-agent: googleadsbot') && preg_match('/user-agent:\s*googleadsbot.*?disallow:\s*\/\s*$/ms', $robots))
                || (str_contains($robots, 'user-agent: mediapartners-google') && preg_match('/user-agent:\s*mediapartners-google.*?disallow:\s*\/\s*$/ms', $robots));

            if ($blocksAds) {
                $findings[] = $this->finding(
                    'seo',
                    'warning',
                    'robots.txt may block an advertising crawler',
                    'A simple text check found a broad Disallow rule under an advertising crawler user-agent. The crawler does not fully emulate robots.txt group precedence or the partner review process.',
                    'Review the complete robots.txt rules and confirm the intended public pages are crawlable before changing access rules.',
                    [],
                    6
                );
            } else {
                $findings[] = $this->finding(
                    'seo',
                    'pass',
                    'No broad advertising-crawler block detected',
                    'The basic text check did not find a broad Disallow rule for the named advertising crawlers. This is not a full robots.txt validation.',
                    null,
                    []
                );
            }
        }

        if ($snapshot->sitemapMissing) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Sitemap not found at the checked location',
                'The standard /sitemap.xml URL returned not found. A sitemap can be published elsewhere, and this finding does not prove that pages cannot be discovered.',
                'If the site has many important URLs, publish a current sitemap and reference its location in robots.txt or your search-console account.',
                [],
                0
            );
        } elseif (! $snapshot->sitemap && ! $snapshot->sitemapChecked) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Sitemap could not be checked',
                'The crawl could not confirm whether a sitemap is present at the standard location. It may be unavailable or the response-size budget may have been reached.',
                'Check the sitemap directly and confirm its location in robots.txt or your search-console account.',
                [],
                0
            );
        } elseif (! $snapshot->sitemapParsed) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Sitemap could not be parsed by this crawler',
                'A sitemap response was received but no page URLs were extracted. The format may be unsupported, or the response may be incomplete.',
                'Inspect the sitemap in a dedicated validator and confirm it contains canonical public URLs.',
                [],
                0
            );
        } else {
            $findings[] = $this->finding(
                'seo',
                'pass',
                'Sitemap entries detected at the checked location',
                'At least '.count($snapshot->sitemapUrls).' URL entries were extracted from the bounded /sitemap.xml response using a lightweight text check; this is not a full sitemap validator or total URL count.',
                null,
                ['urls' => count($snapshot->sitemapUrls)]
            );
        }

        if (blank($home->canonical)) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Canonical link not detected on the home page',
                'No canonical link element was found in the home-page markup. Canonical tags are useful in some setups but are not required on every site.',
                'If your site has duplicate URL variants, review canonical configuration for affected pages.',
                [],
                0
            );
        }

        $description = $home->description;
        $descriptionLength = $description ? mb_strlen($description) : 0;

        if ($descriptionLength === 0) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Meta description not detected on the home page',
                'The home-page markup has no meta description. Search engines may choose page text for a snippet, and they are not required to use the meta description.',
                'If useful, add a concise, accurate description of the page rather than targeting a fixed character count.',
                [],
                0
            );
        } elseif ($descriptionLength < 70 || $descriptionLength > 170) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Meta description length note',
                "The meta description is {$descriptionLength} characters. Displayed snippets vary by query and device; this product check does not define an ideal or required length.",
                'Keep the description accurate, useful and concise; search engines may rewrite it.',
                ['length' => $descriptionLength],
                0
            );
        }

        $title = $home->title ?? '';
        $titleLength = mb_strlen($title);

        if ($titleLength === 0) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'Title element not detected on the home page',
                'The home-page markup has no document title. A clear title helps users, browsers and search results identify the page, but this crawl cannot determine its broader indexing status.',
                'Add a descriptive title that accurately names the page; there is no fixed character-count requirement.',
                [],
                5
            );
        } elseif ($titleLength > 70) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Long title may be truncated in some displays',
                "The title is {$titleLength} characters. Search-result display length varies, so this is a presentation heuristic rather than a strict limit.",
                'Keep the most useful description near the start and remove repetition if the title is hard to scan.',
                ['length' => $titleLength],
                0
            );
        }

        if ($home->headingCount(1) === 0) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'No H1 heading detected on the home page',
                'No H1 element was found in the sampled markup. Heading structure is a usability and document-structure signal, not an approval or ranking guarantee.',
                'Use headings that clearly describe the page and preserve a logical hierarchy; a rigid one-H1 rule is not required.',
                [],
                0
            );
        } elseif ($home->headingCount(1) > 1) {
            $findings[] = $this->finding(
                'seo',
                'info',
                'Multiple H1 headings detected',
                'The page contains multiple H1 elements. This is a structural observation, not by itself an indexing or approval problem.',
                'Check that the heading hierarchy is clear and meaningful for readers and assistive technology.',
                ['count' => $home->headingCount(1)],
                0
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
                'No JSON-LD detected in sampled pages',
                'This crawl did not find JSON-LD blocks. Structured data is optional and can make pages eligible for some search-result features, but it does not guarantee display or indexing.',
                'If relevant, add accurate structured data that matches visible page content and validate it with a structured-data testing tool.',
                [],
                0
            );
        }

        $broken = $this->detectBrokenLinks($snapshot);

        if ($broken !== []) {
            $findings[] = $this->finding(
                'seo',
                'warning',
                'Some crawled page URLs returned errors',
                'The crawler received HTTP errors for: '.implode(', ', array_slice($broken, 0, 6)).'. Temporary server issues, access rules or crawler blocking can cause false positives.',
                'Open these URLs in a browser and review the response before correcting internal links or page configuration.',
                ['links' => $broken],
                5
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
                'warning',
                'The audited URL did not use HTTPS',
                'The requested page was reached without HTTPS. Secure transport protects visitors and is a good modern default; this crawl cannot determine a network decision.',
                'Configure a valid TLS certificate and redirect the public site to HTTPS if the site supports it.',
                [],
                8
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
                'warning',
                'No mobile viewport declaration detected',
                'The home-page markup does not include a viewport declaration. This can affect mobile rendering, though a browser check is needed to assess the actual layout.',
                'Add an appropriate viewport meta tag and test the page on common phone and tablet sizes.',
                [],
                6
            );
        } else {
            $findings[] = $this->finding(
                'technical',
                'pass',
                'Mobile viewport is configured',
                'A mobile viewport declaration was found. It does not guarantee that the full layout works well on phones.',
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
                sprintf("The sampled home-page response was %s KB, above the product's 2 MB review heuristic. Load time depends on network, caching and asset delivery.", number_format($weight / 1024)),
                'Compress your images, serve modern formats, and load scripts only where they are needed.',
                ['bytes' => $weight],
                5
            );
        }

        if ($snapshot->responseTimeMs > 3000) {
            $findings[] = $this->finding(
                'technical',
                'warning',
                'Slow response observed during this crawl',
                'The home page took '.number_format($snapshot->responseTimeMs / 1000, 1).' seconds to respond in this request. Results vary with network conditions, caching and server load.',
                'Repeat the measurement from your target regions and review hosting, caching and heavy server-side work if the delay is consistent.',
                ['duration_ms' => $snapshot->responseTimeMs],
                5
            );
        }

        if ($snapshot->homeBytes > 0 && $home->textToHtmlRatio() < 0.08 && $home->wordCount < 500) {
            $findings[] = $this->finding(
                'technical',
                'info',
                'Low text-to-markup ratio in the home-page sample',
                'The visible-text-to-HTML ratio is low and the page contains fewer than 500 visible words. Image-heavy, tool or application pages may be intentional; this heuristic does not measure value.',
                'Review the page in a browser and make sure users can understand its purpose and find the information they need.',
                [],
                0
            );
        }

        if (! $home->favicon) {
            $findings[] = $this->finding(
                'technical',
                'info',
                'No favicon link was detected',
                'The page markup did not declare a favicon. Browsers can also discover default icon paths without a link element.',
                'Add a favicon if you want consistent browser-tab and bookmark branding.',
                [],
                0
            );
        }

        return $findings;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function crawlLimitChecks(SiteSnapshot $snapshot): array
    {
        $truncatedPages = array_values(array_filter($snapshot->pages, fn (PageSnapshot $page) => $page->truncated));
        $limitReached = $snapshot->pageLimitReached || $snapshot->byteBudgetExhausted || $snapshot->timeBudgetExhausted || $truncatedPages !== [] || $snapshot->truncatedResources !== [];

        if (! $limitReached) {
            return [];
        }

        $reasons = [];

        if ($snapshot->pageLimitReached) {
            $reasons[] = 'the page-count limit was reached';
        }

        if ($snapshot->byteBudgetExhausted) {
            $reasons[] = 'the total response-size budget was reached';
        }

        if ($snapshot->timeBudgetExhausted) {
            $reasons[] = 'the total crawl-time budget was reached';
        }

        if ($truncatedPages !== [] || $snapshot->truncatedResources !== []) {
            $reasons[] = 'one or more responses were read only up to their size limit';
        }

        return [$this->finding(
            'technical',
            'info',
            'Crawl coverage was limited',
            'The crawl was bounded for safety: '.implode('; ', $reasons).'. Some site evidence may therefore be incomplete or not assessed.',
            'If an important page or site file was not included, review it directly and rerun the audit after confirming it is publicly accessible.',
            [
                'pages_truncated' => count($truncatedPages),
                'resources_truncated' => count(array_unique($snapshot->truncatedResources)),
                'page_limit_reached' => $snapshot->pageLimitReached,
                'byte_budget_exhausted' => $snapshot->byteBudgetExhausted,
                'time_budget_exhausted' => $snapshot->timeBudgetExhausted,
            ],
            0
        )];
    }

    private function trafficChecks(SiteSnapshot $snapshot): array
    {
        $findings = [];
        $home = $snapshot->home;

        if (! $home) {
            return $findings;
        }

        $analytics = $this->detectAnalytics($home);

        $findings[] = $this->finding(
            'traffic',
            'info',
            $analytics === [] ? 'Traffic quality was not assessed' : 'Analytics tag detected, traffic quality still not assessed',
            $analytics === []
                ? 'A public crawl cannot see analytics dashboards, sessions, referrals, bots, engagement or click-through rates. No analytics tag was visible on the sampled pages.'
                : 'Found: '.implode(', ', $analytics).'. A tag only proves that code is present; it does not verify traffic sources, sessions, bots, engagement or click-through rates.',
            'Use your own analytics and search console reports to review traffic quality. Do not treat the presence or absence of a tracking script as evidence of approval readiness.',
            ['providers' => $analytics],
            0
        );

        $placeholder = $this->detectPlaceholderAnalytics($home);

        if ($placeholder !== []) {
            $findings[] = $this->finding(
                'traffic',
                'info',
                'Analytics contains a placeholder identifier',
                'These identifiers look like unfilled examples: '.implode(', ', $placeholder).'. The crawler cannot confirm whether analytics data is collected or how any ad network evaluates it.',
                'Replace the example IDs with your real measurement ID, then confirm that data arrives in your reports.',
                ['identifiers' => $placeholder],
                0
            );
        }

        $spam = $this->detectReferrerSpam($home);

        if ($spam !== []) {
            $findings[] = $this->finding(
                'traffic',
                'warning',
                'Potentially low-quality outbound links',
                'The sampled page links to: '.implode(', ', array_slice($spam, 0, 4)).'. This is an outbound-link signal only; it does not show that these domains sent you traffic.',
                'Review whether these links are relevant, safe and disclosed. Use your analytics reports to investigate incoming referral traffic separately.',
                ['outbound_links' => $spam],
                0
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
        foreach ($snapshot->contentPages() as $page) {
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
        foreach ($snapshot->contentPages() as $page) {
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

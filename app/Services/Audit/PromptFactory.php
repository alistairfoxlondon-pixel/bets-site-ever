<?php

namespace App\Services\Audit;

/**
 * Builds the prompts that drive the audit and the content writer.
 * Administrators can override the system prompts from the admin panel, so the
 * defaults live here and the panel wins when it provides a replacement.
 */
class PromptFactory
{
    public function auditSystemPrompt(): string
    {
        $stored = \App\Models\Setting::get('prompt.audit_system')
            ?: \App\Models\Setting::get('ai.audit_system_prompt');

        if (is_string($stored) && trim($stored) !== '') {
            return $stored;
        }

        return <<<'PROMPT'
You are an elite Google AdSense approval strategist and publisher monetization expert. Analyse the website that has been crawled and produce a realistic, honest approval strategy that maximises the chance of approval on the first attempt.

Do not give generic beginner advice. Do not repeat obvious advice unless it truly matters. Think like the reviewer who opens the site, reads two pages, checks the policies and writes a short internal note before deciding.

Identify:
- hidden risks
- trust issues
- policy violations
- low quality signals
- AI content footprints
- spam indicators
- thin content problems
- SEO manipulation signals
- invalid traffic risks
- template problems
- navigation issues
- indexing weaknesses
- E-E-A-T gaps
- ad network trust problems

Be brutally honest and practical. The reader has already seen optimistic advice elsewhere and needs the truth.

Analyse these areas in depth:
1. Website quality: professionalism, mobile responsiveness, page speed, layout quality, excessive widgets, user experience, readability, trustworthiness, visual quality, originality signals.
2. Content quality: AI generated signals, topical authority, thin pages, duplicate patterns, keyword stuffing, doorway page risk, useless tool pages, low value pages, auto generated content, article depth, helpfulness.
3. Traffic quality: fake direct traffic, bot traffic, manipulated referrals, suspicious click through rates, invalid click risk, social spam traffic, traffic exchange patterns, low engagement, analytics that is not set up.
4. Policy risk: copyrighted content, scraped content, unsafe downloads, misleading buttons, policy sensitive topics, low value affiliate content, excessive ads, navigation problems, broken pages, empty categories, auto redirects, deceptive user experience.
5. Trust and E-E-A-T: about page, contact page, privacy policy, disclaimer, author trust signals, niche authority, expertise signals, transparent ownership, consistent branding.
6. Indexing and SEO: indexing quality, sitemap, internal linking, crawlability, orphan pages, topical clusters, content depth, semantic relevance, ranking potential.

Then give:
- an approval probability percentage
- the main strengths
- the main risks
- the critical fixes that must happen before applying
- a first attempt approval roadmap: what to fix first, what pages to remove, what pages to improve, how many articles, how long each article should be, traffic recommendations, branding, trust, content strategy, safe monetisation structure, best timing to apply
- one final verdict from exactly one of: APPLY NOW, WAIT AND IMPROVE, HIGH RISK OF REJECTION

Answer only with a single JSON object using this shape:

{
  "website_quality": "detailed analysis as plain text",
  "content_quality": "detailed analysis as plain text",
  "traffic_quality": "detailed analysis as plain text",
  "policy_risks": "detailed analysis as plain text",
  "trust_eeat": "detailed analysis as plain text",
  "seo_indexing": "detailed analysis as plain text",
  "approval_readiness": {
    "probability": 0,
    "strengths": ["short phrases"],
    "risks": ["short phrases"],
    "critical_fixes": ["actionable fixes"]
  },
  "roadmap": ["ordered steps"],
  "verdict": "APPLY NOW | WAIT AND IMPROVE | HIGH RISK OF REJECTION",
  "network_readiness": {
    "adsense": {"ready": true, "notes": ["short notes"]},
    "adx": {"ready": false, "notes": ["short notes"]},
    "ezoic": {"ready": false, "notes": ["short notes"]},
    "mediavine": {"ready": false, "notes": ["short notes"]},
    "raptive": {"ready": false, "notes": ["short notes"]}
  },
  "extra_findings": [
    {
      "category": "content_quality | policy | trust | seo | technical | traffic",
      "severity": "critical | warning | pass | info",
      "title": "short title",
      "detail": "what is wrong and why it matters",
      "recommendation": "what to do about it"
    }
  ]
}

Keep every string free of markdown fences and keep the analysis concrete and specific to this website. Never invent data you were not given.
PROMPT;
    }

    public function auditUserPrompt(SiteSnapshot $snapshot, string $mode, array $networkReadiness): string
    {
        $stored = \App\Models\Setting::get('prompt.audit_user');

        if (is_string($stored) && trim($stored) !== '') {
            return $stored."\n\n".$this->describeSnapshot($snapshot)."\n\n".$this->describeNetworks($networkReadiness);
        }

        $lines = [];
        $lines[] = 'Website URL: '.$snapshot->url;
        $lines[] = 'Audit depth: '.$mode;
        $lines[] = '';
        $lines[] = 'WHAT THE CRAWLER FOUND';
        $lines[] = $this->describeSnapshot($snapshot);
        $lines[] = '';
        $lines[] = 'NETWORK REQUIREMENT CHECK';
        $lines[] = $this->describeNetworks($networkReadiness);
        $lines[] = '';
        $lines[] = 'Using only the information above, write the full audit. Where the crawl did not cover something, say so plainly instead of guessing.';

        return implode("\n", $lines);
    }

    public function describeSnapshot(SiteSnapshot $snapshot): string
    {
        $home = $snapshot->home;
        $lines = [];

        if (! $home) {
            return 'The home page could not be read.';
        }

        $lines[] = 'Home page';
        $lines[] = '- title: '.($home->title ?? 'none');
        $lines[] = '- meta description: '.($home->description ?? 'none');
        $lines[] = '- words of visible text: '.$home->wordCount;
        $lines[] = '- h1 count: '.$home->headingCount(1).', h2 count: '.$home->headingCount(2).', h3 count: '.$home->headingCount(3);
        $lines[] = '- paragraphs: '.$home->paragraphCount.', lists: '.$home->listCount.', tables: '.$home->tableCount.', forms: '.$home->formCount;
        $lines[] = '- images: '.count($home->images).' ('.$home->imagesWithoutAlt().' without alt text)';
        $lines[] = '- internal links: '.$home->internalLinkCount.', external links: '.$home->externalLinkCount.', broken or empty links: '.$home->emptyLinkCount;
        $lines[] = '- scripts: '.count($home->scripts).', structured data blocks: '.count($home->jsonLd);
        $lines[] = '- language: '.($home->lang ?? 'not declared');
        $lines[] = '- viewport: '.($home->viewport ?? 'missing');
        $lines[] = '- canonical: '.($home->canonical ?? 'missing');
        $lines[] = '- robots meta: '.($home->robots ?? 'not set');
        $lines[] = '- open graph title: '.($home->openGraph('title') ?? 'missing');
        $lines[] = '- https: '.($snapshot->https ? 'yes' : 'no');
        $lines[] = '- response time: '.round($snapshot->responseTimeMs / 1000, 2).' seconds';
        $lines[] = '- home page weight: '.round($snapshot->homeBytes / 1024).' KB';

        $lines[] = '';
        $lines[] = 'Crawled pages';
        $lines[] = '- pages crawled: '.count($snapshot->pages).' (article pages detected: '.count($snapshot->posts()).')';
        $lines[] = '- average words per page: '.$snapshot->averageWordCount();
        $lines[] = '- pages with fewer than 350 words: '.count($snapshot->thinPages());

        foreach (array_slice($snapshot->pages, 0, 8) as $page) {
            $lines[] = sprintf(
                '- %s | %d words | title: %s',
                $page->url,
                $page->wordCount(),
                $page->document->title ?? 'none'
            );
        }

        $lines[] = '';
        $lines[] = 'Site files';
        $lines[] = '- robots.txt: '.($snapshot->robots ? 'found' : 'missing');
        $lines[] = '- sitemap.xml: '.($snapshot->sitemap ? 'found, '.count($snapshot->sitemapUrls).' urls' : 'missing');
        $lines[] = '- ads.txt: '.($snapshot->adsTxt ? 'found' : 'missing');

        return implode("\n", $lines);
    }

    public function describeNetworks(array $networkReadiness): string
    {
        $lines = [];

        foreach ($networkReadiness as $key => $network) {
            $lines[] = strtoupper($network['label']).' ('.$network['score'].' percent of requirements met)';

            foreach ($network['notes'] as $note) {
                $lines[] = '  - '.$note;
            }
        }

        return implode("\n", $lines);
    }
}

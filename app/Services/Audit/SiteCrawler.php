<?php

namespace App\Services\Audit;

use Illuminate\Support\Str;

/**
 * Crawls the public pages of a site so the rule engine and the model can
 * look at more than the home page.
 */
class SiteCrawler
{
    public function __construct(
        private readonly SiteFetcher $fetcher = new SiteFetcher,
        private readonly UrlGuard $guard = new UrlGuard,
    ) {
    }

    public function crawl(string $url, int $maxPages = 10): SiteSnapshot
    {
        $normalized = $this->guard->normalize($url);
        $domain = $this->guard->host($normalized);
        $snapshot = new SiteSnapshot($normalized, $domain);
        $snapshot->https = str_starts_with($normalized, 'https://');

        $home = $this->fetcher->fetch($normalized);

        if (! $home->successful) {
            throw new CrawlException($home->error ?? 'The site could not be reached.');
        }

        $document = HtmlDocument::make($home->body, $home->url);
        $snapshot->home = $document;
        $snapshot->homeDurationMs = $home->durationMs;
        $snapshot->homeBytes = $home->bytes;
        $snapshot->responseTimeMs = $home->durationMs;
        $snapshot->server = $home->header('Server');

        $snapshot->addPage(new PageSnapshot(
            url: $home->url,
            status: $home->status,
            durationMs: $home->durationMs,
            bytes: $home->bytes,
            document: $document,
            type: 'home',
        ));

        $this->fetchRobots($snapshot);
        $this->fetchSitemap($snapshot);
        $this->fetchAdsTxt($snapshot);

        $candidates = $this->collectCandidateUrls($snapshot, $document, $maxPages);

        foreach ($candidates as $candidate) {
            if (count($snapshot->pages) >= $maxPages) {
                break;
            }

            $result = $this->fetcher->fetch($candidate);

            if (! $result->successful) {
                continue;
            }

            $pageDocument = HtmlDocument::make($result->body, $result->url);

            $snapshot->addPage(new PageSnapshot(
                url: $result->url,
                status: $result->status,
                durationMs: $result->durationMs,
                bytes: $result->bytes,
                document: $pageDocument,
            ));
        }

        return $snapshot;
    }

    private function fetchRobots(SiteSnapshot $snapshot): void
    {
        $result = $this->fetcher->fetch($snapshot->url.'/robots.txt');
        $snapshot->robots = $result->successful ? $result : null;
    }

    private function fetchAdsTxt(SiteSnapshot $snapshot): void
    {
        $result = $this->fetcher->fetch($snapshot->url.'/ads.txt');
        $snapshot->adsTxt = $result->successful ? $result : null;
    }

    private function fetchSitemap(SiteSnapshot $snapshot): void
    {
        $result = $this->fetcher->fetch($snapshot->url.'/sitemap.xml');

        if (! $result->successful) {
            return;
        }

        $snapshot->sitemap = $result;

        if (! Str::contains(strtolower($result->contentType()), ['xml', 'text'])) {
            return;
        }

        if (preg_match_all('#<loc>\s*(.*?)\s*</loc>#i', $result->body, $matches)) {
            $snapshot->sitemapParsed = true;
            $snapshot->sitemapUrls = array_values(array_unique(array_map('trim', $matches[1])));
        } elseif (preg_match_all('#<sitemap>\s*<loc>\s*(.*?)\s*</loc>#i', $result->body, $matches)) {
            $snapshot->sitemapParsed = true;
            $snapshot->sitemapUrls = array_values(array_unique(array_map('trim', $matches[1])));
        }
    }

    /**
     * @return array<int,string>
     */
    private function collectCandidateUrls(SiteSnapshot $snapshot, HtmlDocument $home, int $maxPages): array
    {
        $urls = [];
        $seen = [$snapshot->url => true];

        // Sitemap entries first, they are the most reliable list of content.
        foreach (array_slice($snapshot->sitemapUrls, 0, $maxPages * 3) as $sitemapUrl) {
            if (count($urls) >= $maxPages) {
                break;
            }

            if (! $this->guard->isSameHost($sitemapUrl, $snapshot->url)) {
                continue;
            }

            $path = (string) parse_url($sitemapUrl, PHP_URL_PATH);

            if (in_array($path, ['/', ''], true) || isset($seen[$sitemapUrl])) {
                continue;
            }

            $seen[$sitemapUrl] = true;
            $urls[] = $sitemapUrl;
        }

        // Then internal links from the home page, posts first.
        $links = $home->links;
        usort($links, function (array $a, array $b) {
            $weight = function (array $link) {
                $href = strtolower($link['href']);
                $score = 0;

                foreach (['/blog', '/post', '/article', '/news', '/guide', '/how-to'] as $needle) {
                    if (str_contains($href, $needle)) {
                        $score += 10;
                    }
                }

                return $score;
            };

            return $weight($b) <=> $weight($a);
        });

        foreach ($links as $link) {
            if (count($urls) >= $maxPages) {
                break;
            }

            $href = $link['href'];

            if ($href === '' || ! Str::startsWith(strtolower($href), 'http')) {
                continue;
            }

            if (! $this->guard->isSameHost($href, $snapshot->url)) {
                continue;
            }

            $href = $this->stripFragment($href);

            if (isset($seen[$href])) {
                continue;
            }

            $path = (string) parse_url($href, PHP_URL_PATH);

            if (in_array($path, ['/', ''], true)) {
                continue;
            }

            $seen[$href] = true;
            $urls[] = $href;
        }

        return $urls;
    }

    private function stripFragment(string $url): string
    {
        $position = strpos($url, '#');

        return $position === false ? $url : substr($url, 0, $position);
    }
}

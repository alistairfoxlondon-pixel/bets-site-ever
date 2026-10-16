<?php

namespace App\Services\Audit;

use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Str;

/**
 * Crawls a bounded sample of public pages and site files for the audit.
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
        $normalized = $this->guard->assertSafe($url);
        $domain = $this->guard->host($normalized);
        $maxPages = max(1, min(25, $maxPages));
        $maxTotalBytes = max(1048576, min(20 * 1024 * 1024, (int) config('adready.crawler.max_total_bytes', 8388608)));
        $maxCrawlSeconds = max(10, min(180, (int) config('adready.crawler.max_crawl_seconds', 90)));
        $deadline = microtime(true) + $maxCrawlSeconds;
        $remainingBytes = $maxTotalBytes;
        $snapshot = new SiteSnapshot($normalized, $domain);

        $home = $this->fetchWithinBudget($normalized, $remainingBytes, $snapshot, $deadline);

        if (! $home->successful) {
            throw new CrawlException($home->error ?? 'The site could not be reached.');
        }

        $document = HtmlDocument::make($home->body, $home->url);
        $snapshot->home = $document;
        $snapshot->https = str_starts_with(strtolower($home->url), 'https://');
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
            truncated: $home->truncated,
        ));

        $this->fetchRobots($snapshot, $remainingBytes, $deadline);
        $this->fetchSitemap($snapshot, $remainingBytes, $deadline);
        $this->fetchAdsTxt($snapshot, $remainingBytes, $deadline);

        $candidates = $this->collectCandidateUrls($snapshot, $document, $maxPages);
        $candidateCount = count($candidates);

        if ($candidateCount >= $maxPages) {
            $snapshot->pageLimitReached = true;
        }

        foreach ($candidates as $index => $candidate) {
            if (count($snapshot->pages) >= $maxPages) {
                $snapshot->pageLimitReached = $index < $candidateCount;

                break;
            }

            if ($remainingBytes < 1024) {
                $snapshot->byteBudgetExhausted = true;

                break;
            }

            if (microtime(true) >= $deadline) {
                $snapshot->timeBudgetExhausted = true;

                break;
            }

            $result = $this->fetchWithinBudget($candidate, $remainingBytes, $snapshot, $deadline);

            if (! $result->successful) {
                if ($result->status >= 400) {
                    $snapshot->addPage(new PageSnapshot(
                        url: $result->url,
                        status: $result->status,
                        durationMs: $result->durationMs,
                        bytes: 0,
                        document: HtmlDocument::make('', $result->url),
                    ));
                }

                continue;
            }

            $pageDocument = HtmlDocument::make($result->body, $result->url);
            $snapshot->addPage(new PageSnapshot(
                url: $result->url,
                status: $result->status,
                durationMs: $result->durationMs,
                bytes: $result->bytes,
                document: $pageDocument,
                truncated: $result->truncated,
            ));
        }

        if (microtime(true) >= $deadline) {
            $snapshot->timeBudgetExhausted = true;
        }

        return $snapshot;
    }

    private function fetchRobots(SiteSnapshot $snapshot, int &$remainingBytes, float $deadline): void
    {
        $result = $this->fetchWithinBudget($this->rootUrl($snapshot->url).'/robots.txt', $remainingBytes, $snapshot, $deadline);

        if ($result->successful) {
            $snapshot->robotsChecked = true;
            $snapshot->robots = $result;
        } elseif ($result->status === 404) {
            $snapshot->robotsChecked = true;
            $snapshot->robotsMissing = true;
        }

    }

    private function fetchAdsTxt(SiteSnapshot $snapshot, int &$remainingBytes, float $deadline): void
    {
        $result = $this->fetchWithinBudget($this->rootUrl($snapshot->url).'/ads.txt', $remainingBytes, $snapshot, $deadline);

        if ($result->successful) {
            $snapshot->adsTxtChecked = true;
            $snapshot->adsTxt = $result;
        } elseif ($result->status === 404) {
            $snapshot->adsTxtChecked = true;
            $snapshot->adsTxtMissing = true;
        }

    }

    private function fetchSitemap(SiteSnapshot $snapshot, int &$remainingBytes, float $deadline): void
    {
        $result = $this->fetchWithinBudget($this->rootUrl($snapshot->url).'/sitemap.xml', $remainingBytes, $snapshot, $deadline);

        if (! $result->successful) {
            if ($result->status === 404) {
                $snapshot->sitemapChecked = true;
                $snapshot->sitemapMissing = true;
            }

            return;
        }

        $snapshot->sitemapChecked = true;
        $snapshot->sitemap = $result;

        if ($result->truncated) {
            return;
        }

        if (! Str::contains(strtolower($result->contentType()), ['xml', 'text'])) {
            return;
        }

        $snapshot->sitemapUrls = $this->extractSitemapLocations($result->body, 75);
        $snapshot->sitemapParsed = $snapshot->sitemapUrls !== [];
    }

    /**
     * @return array<int,string>
     */
    private function collectCandidateUrls(SiteSnapshot $snapshot, HtmlDocument $home, int $maxPages): array
    {
        $urls = [];
        $baseUrl = (string) ($snapshot->pages[0]->url ?? $snapshot->url);
        $seen = [
            $this->stripFragment($snapshot->url) => true,
            $this->stripFragment($baseUrl) => true,
        ];

        // Prefer sitemap URLs, then public same-host links from the home page.
        foreach (array_slice($snapshot->sitemapUrls, 0, $maxPages * 3) as $sitemapUrl) {
            if (count($urls) >= $maxPages) {
                break;
            }

            $sitemapUrl = $this->stripFragment(trim($sitemapUrl));

            if (! str_starts_with(strtolower($sitemapUrl), 'http')
                || ! $this->guard->isSameHost($sitemapUrl, $baseUrl)
                || isset($seen[$sitemapUrl])) {
                continue;
            }

            $path = (string) parse_url($sitemapUrl, PHP_URL_PATH);

            if (in_array($path, ['/', ''], true)) {
                continue;
            }

            $seen[$sitemapUrl] = true;
            $urls[] = $sitemapUrl;
        }

        $links = $home->links;
        usort($links, function (array $a, array $b): int {
            $weight = static function (array $link): int {
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

            $href = $this->resolveLink($baseUrl, $link['href']);

            if ($href === null
                || ! $this->guard->isSameHost($href, $baseUrl)
                || isset($seen[$href])) {
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

    private function fetchWithinBudget(string $url, int &$remainingBytes, SiteSnapshot $snapshot, float $deadline): FetchResult
    {
        if ($remainingBytes < 1024) {
            $snapshot->byteBudgetExhausted = true;

            return FetchResult::failed($url, 'The crawl response-size budget was reached.');
        }

        $remainingSeconds = (int) ceil($deadline - microtime(true));

        if ($remainingSeconds <= 0) {
            $snapshot->timeBudgetExhausted = true;

            return FetchResult::failed($url, 'The crawl time budget was reached.');
        }

        $perResponseBytes = max(1024, (int) config('adready.crawler.max_bytes', 786432));
        $limit = min($perResponseBytes, $remainingBytes);
        $result = $this->fetcher->fetch($url, null, $limit, $remainingSeconds);
        $remainingBytes = max(0, $remainingBytes - $result->bytes);

        if ($result->truncated) {
            $snapshot->truncatedResources[] = $url;
        }

        if ($remainingBytes === 0) {
            $snapshot->byteBudgetExhausted = true;
        }

        return $result;
    }

    /** @return array<int,string> */
    private function extractSitemapLocations(string $xml, int $limit): array
    {
        $urls = [];
        $offset = 0;
        $length = strlen($xml);
        $limit = max(1, min(100, $limit));

        while ($offset < $length && count($urls) < $limit) {
            $match = [];

            if (preg_match('#<loc>\s*(.*?)\s*</loc>#i', $xml, $match, PREG_OFFSET_CAPTURE, $offset) !== 1) {
                break;
            }

            $url = trim((string) ($match[1][0] ?? ''));

            if ($url !== '') {
                $urls[] = $url;
            }

            $fullMatch = (string) ($match[0][0] ?? '');
            $nextOffset = (int) ($match[0][1] ?? $offset) + strlen($fullMatch);

            if ($nextOffset <= $offset) {
                break;
            }

            $offset = $nextOffset;
        }

        return array_values(array_unique($urls));
    }

    private function resolveLink(string $baseUrl, string $href): ?string
    {
        $href = trim($href);

        if ($href === '') {
            return null;
        }

        try {
            $resolved = UriResolver::resolve(
                Utils::uriFor($baseUrl),
                Utils::uriFor($href),
            );
            $scheme = strtolower($resolved->getScheme());

            if (! in_array($scheme, ['http', 'https'], true) || blank($resolved->getHost())) {
                return null;
            }

            return $this->stripFragment((string) $resolved);
        } catch (\Throwable) {
            return null;
        }
    }

    private function rootUrl(string $url): string
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = trim((string) ($parts['host'] ?? ''), '[]');
        $authority = str_contains($host, ':') ? '['.$host.']' : $host;
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        $defaultPort = $scheme === 'https' ? 443 : 80;

        if ($port !== null && $port !== $defaultPort) {
            $authority .= ':'.$port;
        }

        return $scheme.'://'.$authority;
    }

    private function stripFragment(string $url): string
    {
        $position = strpos($url, '#');

        return $position === false ? $url : substr($url, 0, $position);
    }
}

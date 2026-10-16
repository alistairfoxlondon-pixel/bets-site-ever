<?php

namespace App\Services\Audit;

class SiteSnapshot
{
    /** @var array<int,PageSnapshot> */
    public array $pages = [];

    public ?FetchResult $robots = null;

    public ?FetchResult $sitemap = null;

    public ?FetchResult $adsTxt = null;

    public ?HtmlDocument $home = null;

    public int $homeDurationMs = 0;

    public int $homeBytes = 0;

    public int $responseTimeMs = 0;

    public bool $https = false;

    public ?string $server = null;

    public bool $sitemapParsed = false;

    public bool $robotsChecked = false;

    public bool $robotsMissing = false;

    public bool $sitemapChecked = false;

    public bool $sitemapMissing = false;

    public bool $adsTxtChecked = false;

    public bool $adsTxtMissing = false;

    public bool $pageLimitReached = false;

    public bool $byteBudgetExhausted = false;

    public bool $timeBudgetExhausted = false;

    /** @var array<int,string> */
    public array $sitemapUrls = [];

    /** @var array<int,string> */
    public array $truncatedResources = [];

    public function __construct(public readonly string $url, public readonly string $domain)
    {
    }

    public function addPage(PageSnapshot $page): void
    {
        $this->pages[] = $page;
    }

    /** @return array<int,PageSnapshot> */
    public function contentPages(): array
    {
        return array_values(array_filter($this->pages, fn (PageSnapshot $page) => $page->status >= 200 && $page->status < 400));
    }

    public function posts(): array
    {
        return array_values(array_filter($this->contentPages(), fn (PageSnapshot $page) => $page->isLikelyPost()));
    }

    public function totalWords(): int
    {
        return array_sum(array_map(fn (PageSnapshot $page) => $page->wordCount(), $this->contentPages()));
    }

    public function averageWordCount(): int
    {
        $contentPages = $this->contentPages();

        if ($contentPages === []) {
            return 0;
        }

        return (int) round($this->totalWords() / count($contentPages));
    }

    public function thinPages(): array
    {
        return array_values(array_filter($this->contentPages(), fn (PageSnapshot $page) => $page->isThin()));
    }

    public function allDocuments(): array
    {
        return array_map(fn (PageSnapshot $page) => $page->document, $this->contentPages());
    }
}

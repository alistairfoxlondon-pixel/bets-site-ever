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

    /** @var array<int,string> */
    public array $sitemapUrls = [];

    public function __construct(public readonly string $url, public readonly string $domain)
    {
    }

    public function addPage(PageSnapshot $page): void
    {
        $this->pages[] = $page;
    }

    public function posts(): array
    {
        return array_values(array_filter($this->pages, fn (PageSnapshot $page) => $page->isLikelyPost()));
    }

    public function totalWords(): int
    {
        return array_sum(array_map(fn (PageSnapshot $page) => $page->wordCount(), $this->pages));
    }

    public function averageWordCount(): int
    {
        if ($this->pages === []) {
            return 0;
        }

        return (int) round($this->totalWords() / count($this->pages));
    }

    public function thinPages(): array
    {
        return array_values(array_filter($this->pages, fn (PageSnapshot $page) => $page->isThin()));
    }

    public function allDocuments(): array
    {
        return array_map(fn (PageSnapshot $page) => $page->document, $this->pages);
    }
}

<?php

namespace App\Services\Audit;

class PageSnapshot
{
    public function __construct(
        public readonly string $url,
        public readonly int $status,
        public readonly int $durationMs,
        public readonly int $bytes,
        public readonly HtmlDocument $document,
        public readonly string $type = 'page',
        public readonly bool $truncated = false,
    ) {
    }

    public function wordCount(): int
    {
        return $this->document->wordCount;
    }

    public function isThin(): bool
    {
        return $this->wordCount() < 350;
    }

    public function isLikelyPost(): bool
    {
        if ($this->type === 'post') {
            return true;
        }

        $path = strtolower((string) parse_url($this->url, PHP_URL_PATH));

        foreach (['/blog/', '/post/', '/posts/', '/article/', '/articles/', '/news/', '/category/', '/guide/', '/how-to/'] as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }

        return (bool) preg_match('#/\d{4}/\d{1,2}/#', $path);
    }
}

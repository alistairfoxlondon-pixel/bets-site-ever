<?php

namespace App\Services\Audit;

use Illuminate\Support\Str;

/**
 * A small, dependency free HTML reader.
 *
 * It extracts the signals the audit needs (metadata, headings, links, images,
 * scripts, structured data, visible text) without pulling a full DOM parser
 * into the stack.
 */
class HtmlDocument
{
    /** @var array<string,string> */
    public array $meta = [];

    /** @var array<int,array{level:int,text:string}> */
    public array $headings = [];

    /** @var array<int,array{src:string,alt:?string,width:?string,height:?string,loading:?string}> */
    public array $images = [];

    /** @var array<int,array{href:string,text:string,rel:string}> */
    public array $links = [];

    /** @var array<int,array{src:?string,type:?string,inline:bool,content:string}> */
    public array $scripts = [];

    /** @var array<int,string> */
    public array $jsonLd = [];

    public ?string $title = null;

    public ?string $description = null;

    public ?string $canonical = null;

    public ?string $robots = null;

    public ?string $viewport = null;

    public ?string $lang = null;

    public ?string $favicon = null;

    public string $text = '';

    public int $wordCount = 0;

    public int $paragraphCount = 0;

    public int $listCount = 0;

    public int $tableCount = 0;

    public int $formCount = 0;

    public int $iframeCount = 0;

    public int $internalLinkCount = 0;

    public int $externalLinkCount = 0;

    public int $emptyLinkCount = 0;

    public int $nofollowLinkCount = 0;

    public int $htmlBytes = 0;

    public function __construct(
        public readonly string $html,
        private readonly string $url,
        private readonly ?UrlGuard $guard = null,
    ) {
        $this->htmlBytes = strlen($html);
        $this->parse();
    }

    public static function make(string $html, string $url): self
    {
        return new self($html, $url);
    }

    public function metaValue(string $key): ?string
    {
        return $this->meta[strtolower($key)] ?? null;
    }

    public function openGraph(string $key): ?string
    {
        return $this->meta['og:'.strtolower($key)] ?? null;
    }

    public function hasScript(string $needle): bool
    {
        foreach ($this->scripts as $script) {
            if ($script['src'] && Str::contains(strtolower($script['src']), strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    public function headingCount(int $level): int
    {
        return count(array_filter($this->headings, fn (array $heading) => $heading['level'] === $level));
    }

    public function imagesWithoutAlt(): int
    {
        return count(array_filter($this->images, fn (array $image) => blank($image['alt'])));
    }

    public function textToHtmlRatio(): float
    {
        if ($this->htmlBytes === 0) {
            return 0.0;
        }

        return round($this->wordCount * 6 / $this->htmlBytes, 4);
    }

    public function wordCount(): int
    {
        return $this->wordCount;
    }

    private function parse(): void
    {
        $html = $this->html;

        $this->lang = $this->firstMatch('/<html[^>]*\blang=["\']([^"\']+)["\']/i', $html);

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $this->title = $this->cleanText($matches[1]);
        }

        if (preg_match('/<link[^>]+rel=["\'][^"\']*canonical[^"\']*["\'][^>]*>/i', $html, $matches)) {
            $this->canonical = $this->attribute($matches[0], 'href');
        }

        if (preg_match('/<link[^>]+rel=["\'][^"\']*icon[^"\']*["\'][^>]*>/i', $html, $matches)) {
            $this->favicon = $this->attribute($matches[0], 'href');
        }

        $this->parseMeta($html);
        $this->parseHeadings($html);
        $this->parseImages($html);
        $this->parseLinks($html);
        $this->parseScripts($html);
        $this->parseStructuredData($html);
        $this->parseCounts($html);
        $this->parseText($html);

        $this->description = $this->metaValue('description');
        $this->robots = $this->metaValue('robots');
        $this->viewport = $this->metaValue('viewport');
    }

    private function parseMeta(string $html): void
    {
        if (! preg_match_all('/<meta\b[^>]*>/i', $html, $matches)) {
            return;
        }

        foreach ($matches[0] as $tag) {
            $name = $this->attribute($tag, 'name') ?? $this->attribute($tag, 'property') ?? $this->attribute($tag, 'itemprop');
            $content = $this->attribute($tag, 'content');

            if ($name === null || $content === null) {
                continue;
            }

            $this->meta[strtolower(trim($name))] = trim($content);
        }
    }

    private function parseHeadings(string $html): void
    {
        if (! preg_match_all('/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            $text = $this->cleanText($match[2]);

            if ($text === '') {
                continue;
            }

            $this->headings[] = [
                'level' => (int) $match[1],
                'text' => $text,
            ];
        }
    }

    private function parseImages(string $html): void
    {
        if (! preg_match_all('/<img\b[^>]*>/i', $html, $matches)) {
            return;
        }

        foreach ($matches[0] as $tag) {
            $this->images[] = [
                'src' => $this->attribute($tag, 'src') ?? '',
                'alt' => $this->attribute($tag, 'alt'),
                'width' => $this->attribute($tag, 'width'),
                'height' => $this->attribute($tag, 'height'),
                'loading' => $this->attribute($tag, 'loading'),
            ];
        }
    }

    private function parseLinks(string $html): void
    {
        if (! preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            return;
        }

        $guard = $this->guard ?? new UrlGuard;

        foreach ($matches as $match) {
            $attributes = $match[1];
            $href = $this->attributeFromString($attributes, 'href') ?? '';
            $rel = strtolower($this->attributeFromString($attributes, 'rel') ?? '');

            $this->links[] = [
                'href' => trim($href),
                'text' => $this->cleanText($match[2]),
                'rel' => $rel,
            ];

            if ($href === '' || $href === '#' || Str::startsWith(strtolower($href), 'javascript:')) {
                $this->emptyLinkCount++;

                continue;
            }

            if (Str::contains($rel, 'nofollow')) {
                $this->nofollowLinkCount++;
            }

            if (Str::startsWith(strtolower($href), 'mailto:') || Str::startsWith(strtolower($href), 'tel:')) {
                continue;
            }

            if (Str::startsWith(strtolower($href), 'http')) {
                if ($guard->isSameHost($href, $this->url)) {
                    $this->internalLinkCount++;
                } else {
                    $this->externalLinkCount++;
                }
            } elseif (Str::startsWith($href, '/') || Str::startsWith($href, '#') || Str::startsWith($href, '?')) {
                $this->internalLinkCount++;
            }
        }
    }

    private function parseScripts(string $html): void
    {
        if (! preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $html, $matches, PREG_SET_ORDER)) {
            return;
        }

        foreach ($matches as $match) {
            $attributes = $match[1];
            $src = $this->attributeFromString($attributes, 'src');
            $inline = trim($match[2]) !== '';

            $this->scripts[] = [
                'src' => $src ? trim($src) : null,
                'type' => $this->attributeFromString($attributes, 'type'),
                'inline' => $inline,
                'content' => $inline ? trim($match[2]) : '',
            ];
        }
    }

    private function parseStructuredData(string $html): void
    {
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return;
        }

        foreach ($matches[1] as $block) {
            $decoded = json_decode(trim($block), true);

            if (is_array($decoded)) {
                $this->jsonLd[] = $decoded;
            }
        }
    }

    private function parseCounts(string $html): void
    {
        $this->paragraphCount = preg_match_all('/<p\b/i', $html);
        $this->listCount = preg_match_all('/<(ul|ol)\b/i', $html);
        $this->tableCount = preg_match_all('/<table\b/i', $html);
        $this->formCount = preg_match_all('/<form\b/i', $html);
        $this->iframeCount = preg_match_all('/<iframe\b/i', $html);
    }

    private function parseText(string $html): void
    {
        $text = preg_replace('#<(script|style|noscript|template|svg|head)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = preg_replace('#<!--.*?-->#s', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        $this->text = trim($text);
        $this->wordCount = str_word_count(strip_tags($this->text));
    }

    private function attribute(string $tag, string $name): ?string
    {
        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*(["\'])(.*?)\1/i', $tag, $matches)) {
            return trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*([^\s"\'>]+)/i', $tag, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function attributeFromString(string $attributes, string $name): ?string
    {
        return $this->attribute('<x '.$attributes.'>', $name);
    }

    private function firstMatch(string $pattern, string $subject): ?string
    {
        if (preg_match($pattern, $subject, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function cleanText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}

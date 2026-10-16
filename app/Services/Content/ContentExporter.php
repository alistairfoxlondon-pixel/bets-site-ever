<?php

namespace App\Services\Content;

use App\Models\ContentDocument;
use Illuminate\Support\Str;

/**
 * Converts a generated article into the format a publisher needs.
 */
class ContentExporter
{
    public const FORMATS = [
        'html' => 'HTML',
        'markdown' => 'Markdown',
        'wordpress' => 'WordPress',
        'blogger' => 'Blogger',
        'shopify' => 'Shopify',
        'wix' => 'Wix',
    ];

    /**
     * @return array{body:string,filename:string,content_type:string}
     */
    public function export(ContentDocument $document, string $format): array
    {
        $format = array_key_exists($format, self::FORMATS) ? $format : 'html';

        $body = match ($format) {
            'markdown' => $this->markdown($document),
            'wordpress' => $this->wordpress($document),
            'blogger' => $this->blogger($document),
            'shopify' => $this->shopify($document),
            'wix' => $this->wix($document),
            default => $this->html($document),
        };

        $extension = match ($format) {
            'markdown' => 'md',
            'html' => 'html',
            default => 'txt',
        };

        $slug = $document->slug ?: Str::slug($document->title) ?: 'article';

        return [
            'body' => $body,
            'filename' => $slug.'-'.$format.'.'.$extension,
            'content_type' => match ($format) {
                'html' => 'text/html; charset=utf-8',
                'markdown' => 'text/markdown; charset=utf-8',
                default => 'text/plain; charset=utf-8',
            },
        ];
    }

    public function html(ContentDocument $document): string
    {
        $lines = [];

        $lines[] = '<!DOCTYPE html>';
        $lines[] = '<html lang="en">';
        $lines[] = '<head>';
        $lines[] = '<meta charset="utf-8">';
        $lines[] = '<title>'.e($document->title).'</title>';
        $lines[] = '<meta name="description" content="'.e((string) $document->meta_description).'">';
        $lines[] = '</head>';
        $lines[] = '<body>';
        $lines[] = '<article>';
        $lines[] = '<h1>'.e($document->title).'</h1>';
        $lines[] = $document->body;

        $faq = $document->faq ?? [];

        if ($faq !== []) {
            $lines[] = '<h2>Frequently asked questions</h2>';

            foreach ($faq as $item) {
                $question = is_array($item) ? ($item['question'] ?? '') : (string) $item;
                $answer = is_array($item) ? ($item['answer'] ?? '') : '';

                $lines[] = '<h3>'.e((string) $question).'</h3>';
                $lines[] = '<p>'.e((string) $answer).'</p>';
            }
        }

        $lines[] = '</article>';
        $lines[] = '</body>';
        $lines[] = '</html>';

        return implode("\n", $lines);
    }

    public function markdown(ContentDocument $document): string
    {
        $lines = [];

        $lines[] = '# '.$document->title;
        $lines[] = '';
        $lines[] = '**Meta title:** '.$document->meta_title;
        $lines[] = '';
        $lines[] = '**Meta description:** '.$document->meta_description;
        $lines[] = '';

        if ($document->excerpt) {
            $lines[] = '> '.$document->excerpt;
            $lines[] = '';
        }

        $lines[] = $this->htmlToMarkdown((string) $document->body);

        $faq = $document->faq ?? [];

        if ($faq !== []) {
            $lines[] = '';
            $lines[] = '## Frequently asked questions';
            $lines[] = '';

            foreach ($faq as $item) {
                $question = is_array($item) ? ($item['question'] ?? '') : (string) $item;
                $answer = is_array($item) ? ($item['answer'] ?? '') : '';

                $lines[] = '### '.$question;
                $lines[] = '';
                $lines[] = strip_tags((string) $answer);
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    public function wordpress(ContentDocument $document): string
    {
        $lines = [];

        $lines[] = '<!-- Title: '.$document->title.' -->';
        $lines[] = '<!-- Meta title: '.$document->meta_title.' -->';
        $lines[] = '<!-- Meta description: '.$document->meta_description.' -->';
        $lines[] = '<!-- Slug: '.($document->slug ?: Str::slug($document->title)).' -->';
        $lines[] = '';
        $lines[] = '<!-- Paste the body below into the WordPress editor on the HTML tab. -->';
        $lines[] = $document->body;

        $faq = $document->faq ?? [];

        if ($faq !== []) {
            $lines[] = '';
            $lines[] = '<!-- FAQs: paste into a FAQ block or the FAQ widget -->';

            foreach ($faq as $item) {
                $question = is_array($item) ? ($item['question'] ?? '') : (string) $item;
                $answer = is_array($item) ? ($item['answer'] ?? '') : '';

                $lines[] = '<!-- Q: '.$question.' -->';
                $lines[] = '<p><strong>'.$question.'</strong></p>';
                $lines[] = '<p>'.strip_tags((string) $answer).'</p>';
            }
        }

        return implode("\n", $lines);
    }

    public function blogger(ContentDocument $document): string
    {
        $lines = [];

        $lines[] = '<div class="post-body">';
        $lines[] = $document->body;

        $faq = $document->faq ?? [];

        if ($faq !== []) {
            $lines[] = '<h2>Frequently asked questions</h2>';

            foreach ($faq as $item) {
                $question = is_array($item) ? ($item['question'] ?? '') : (string) $item;
                $answer = is_array($item) ? ($item['answer'] ?? '') : '';

                $lines[] = '<h3>'.$question.'</h3>';
                $lines[] = '<p>'.strip_tags((string) $answer).'</p>';
            }
        }

        $lines[] = '</div>';
        $lines[] = '';
        $lines[] = '<!-- Post title: '.$document->title.' -->';
        $lines[] = '<!-- Search description: '.$document->meta_description.' -->';

        return implode("\n", $lines);
    }

    public function shopify(ContentDocument $document): string
    {
        $lines = [];

        $lines[] = '<!-- Shopify article. Paste into your theme editor or the Rich Text block. -->';
        $lines[] = '<!-- SEO title: '.$document->meta_title.' -->';
        $lines[] = '<!-- SEO description: '.$document->meta_description.' -->';
        $lines[] = '';
        $lines[] = $document->body;

        $faq = $document->faq ?? [];

        if ($faq !== []) {
            $lines[] = '';
            $lines[] = '<!-- FAQ schema hints -->';

            foreach ($faq as $item) {
                $question = is_array($item) ? ($item['question'] ?? '') : (string) $item;
                $answer = is_array($item) ? ($item['answer'] ?? '') : '';

                $lines[] = '<h3>'.$question.'</h3>';
                $lines[] = '<div class="rte">'.strip_tags((string) $answer).'</div>';
            }
        }

        return implode("\n", $lines);
    }

    public function wix(ContentDocument $document): string
    {
        $lines = [];

        $lines[] = '<!-- Wix blog post. Paste into the Wix Blog post editor. -->';
        $lines[] = '<!-- Title: '.$document->title.' -->';
        $lines[] = '<!-- Excerpt: '.Str::limit(strip_tags((string) $document->excerpt), 240).' -->';
        $lines[] = '<!-- SEO title: '.$document->meta_title.' -->';
        $lines[] = '<!-- SEO description: '.$document->meta_description.' -->';
        $lines[] = '';
        $lines[] = $document->body;

        $faq = $document->faq ?? [];

        if ($faq !== []) {
            $lines[] = '';
            $lines[] = '<h2>Frequently asked questions</h2>';

            foreach ($faq as $item) {
                $question = is_array($item) ? ($item['question'] ?? '') : (string) $item;
                $answer = is_array($item) ? ($item['answer'] ?? '') : '';

                $lines[] = '<h3>'.$question.'</h3>';
                $lines[] = '<p>'.strip_tags((string) $answer).'</p>';
            }
        }

        return implode("\n", $lines);
    }

    private function htmlToMarkdown(string $html): string
    {
        $text = $html;

        $text = preg_replace('#<h2[^>]*>(.*?)</h2>#s', "\n## $1\n", $text) ?? $text;
        $text = preg_replace('#<h3[^>]*>(.*?)</h3>#s', "\n### $1\n", $text) ?? $text;
        $text = preg_replace('#<li[^>]*>(.*?)</li>#s', "- $1\n", $text) ?? $text;
        $text = preg_replace('#<p[^>]*>(.*?)</p>#s', "\n$1\n", $text) ?? $text;
        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}

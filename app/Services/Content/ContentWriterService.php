<?php

namespace App\Services\Content;

use App\Models\ContentDocument;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiClient;
use Illuminate\Support\Str;

/**
 * Writes publish ready articles for AdSense sites and affiliate sites.
 */
class ContentWriterService
{
    public function __construct(private readonly AiClient $ai = new AiClient)
    {
    }

    /**
     * @param  array{keyword:string,mode?:string,type?:string,url?:string,language?:string,tone?:string,extra_notes?:string,site_context?:string}  $input
     */
    public function generate(User $user, array $input): ContentDocument
    {
        $type = $input['type'] ?? 'adsense_article';
        $mode = $input['mode'] ?? 'standard';
        $keyword = trim((string) $input['keyword']);

        $modes = config('adready.writer.modes');
        $modeConfig = $modes[$mode] ?? $modes['standard'];

        $model = (string) (Setting::get('ai.model_writer_'.$mode) ?: $modeConfig['model']);

        $response = $this->ai->chatJson(
            $this->messages($type, $keyword, $input, $mode),
            $model,
            [
                'temperature' => $modeConfig['temperature'],
                'max_tokens' => $modeConfig['max_tokens'],
                'response_format' => ['type' => 'json_object'],
            ]
        );

        if (! $response->successful) {
            throw new \RuntimeException($response->error ?? 'The writer could not produce an article.');
        }

        $data = $response->data ?? [];

        $body = (string) ($data['body'] ?? $data['content'] ?? '');
        $title = (string) ($data['title'] ?? Str::headline($keyword));
        $faq = array_values((array) ($data['faq'] ?? []));

        $document = new ContentDocument;
        $document->user_id = $user->id;
        $document->type = $type;
        $document->mode = $mode;
        $document->title = $title;
        $document->slug = Str::slug($title);
        $document->keyword = $keyword;
        $document->intent = (string) ($data['intent'] ?? 'informational');
        $document->word_count = $this->wordCount($body);
        $document->body = $body;
        $document->excerpt = (string) ($data['excerpt'] ?? Str::limit(strip_tags($body), 220));
        $document->meta_title = (string) ($data['meta_title'] ?? Str::limit($title, 60));
        $document->meta_description = (string) ($data['meta_description'] ?? Str::limit(strip_tags($data['excerpt'] ?? $body), 158));
        $document->faq = $faq;
        $document->outline = array_values((array) ($data['outline'] ?? []));
        $document->images = array_values((array) ($data['images'] ?? []));
        $document->internal_links = array_values((array) ($data['internal_links'] ?? []));
        $document->external_links = array_values((array) ($data['external_links'] ?? []));
        $document->model = $response->model;
        $document->tokens_used = $response->totalTokens;
        $document->cost = (float) Setting::get('ai.price_per_million', 0);
        $document->status = 'complete';
        $document->meta = [
            'primary_keyword' => $keyword,
            'secondary_keywords' => array_values((array) ($data['secondary_keywords'] ?? [])),
            'generated_at' => now()->toIso8601String(),
            'publisher_notes' => $data['publisher_notes'] ?? null,
            'checklist' => array_values((array) ($data['checklist'] ?? [])),
        ];

        $document->save();

        return $document;
    }

    /**
     * @return array<int,array{role:string,content:string}>
     */
    private function messages(string $type, string $keyword, array $input, string $mode): array
    {
        $isAffiliate = $type === 'affiliate_article';

        $system = $this->systemPrompt($isAffiliate, $mode);
        $user = $this->userPrompt($keyword, $input, $isAffiliate);

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    private function systemPrompt(bool $affiliate, string $mode): string
    {
        $base = Setting::get('prompt.writer_system');

        if (is_string($base) && filled($base)) {
            return $base;
        }

        $wordTarget = $mode === 'premium' ? '2200 to 3000 words' : '2000 to 2600 words';

        $rules = [
            'You are a senior English content writer who publishes on sites that are being reviewed for Google AdSense approval.',
            'Write like a real person who has used the thing they are describing. Confident, specific, plain English.',
            'Never use dashes as punctuation. Never use hashtags. Never use emoji. Never open with a generic hook such as "In today\'s digital world" or "In the ever evolving landscape".',
            'Never tell the reader that the content is AI generated, and never mention the writing process itself.',
            'Do not stuff keywords. Use the primary keyword where it belongs naturally, usually two or three times in a long piece.',
            'Do not invent statistics, studies, prices, awards or citations that were not provided. If a number is needed and you do not have one, describe the mechanism instead of inventing a figure.',
            'Write with clear experience signals: what the reader will notice, what it feels like, what changed, what to watch out for.',
            'Structure the piece with descriptive headings that a human would have chosen. Short paragraphs. No walls of text.',
            'Current date: '.now()->toFormattedDateString().'. If a topic changes over time, describe the current state and say so plainly.',
            'Target length: '.$wordTarget.'.',
        ];

        if ($affiliate) {
            array_push(
                $rules,
                'This is an affiliate article. The first paragraph must contain a clear, natural disclosure that the writer earns a commission if the reader buys through the links.',
                'Mark every monetised link with the tag {affiliate_link} so it can be styled later.',
                'Compare options honestly, including who should not buy each one. Trust beats promotion.'
            );
        } else {
            array_push(
                $rules,
                'This is an AdSense ready informational article. It must hold up to a policy review on its own.',
                'Mark internal links with {internal_link} and external references with {external_link} so they can be styled later.'
            );
        }

        array_push(
            $rules,
            'Include exactly ten FAQ items, each a real question a reader would type, with a complete answer of two to four sentences.',
            'Return strict JSON with these keys: title, slug, meta_title, meta_description, excerpt, intent, outline (array of strings), body (HTML string using h2, h3, p, ul, ol, li, strong and a tags), primary_keyword, secondary_keywords (array of strings), faq (array of objects with question and answer), internal_links (array of {anchor, url, note}), external_links (array of {anchor, url, note}), images (array of objects with prompt, alt, placement, caption), checklist (array of strings), publisher_notes.'
        );

        return implode("\n", $rules);
    }

    private function userPrompt(string $keyword, array $input, bool $affiliate): string
    {
        $lines = [];

        $lines[] = 'Primary keyword: '.$keyword;
        $lines[] = 'Target site: '.($input['url'] ?? 'not provided');
        $lines[] = 'Language: '.($input['language'] ?? 'English');
        $lines[] = 'Tone: '.($input['tone'] ?? ($affiliate ? 'honest, practical, lightly opinionated' : 'clear, factual, calm'));

        if (! empty($input['site_context'])) {
            $lines[] = 'Site context: '.$input['site_context'];
        }

        if (! empty($input['extra_notes'])) {
            $lines[] = 'Extra notes from the publisher: '.$input['extra_notes'];
        }

        if ($affiliate) {
            $lines[] = 'Product or network being promoted: '.($input['product'] ?? 'the recommended product');
        }

        $lines[] = '';
        $lines[] = 'Write the article now. Return JSON only.';

        return implode("\n", $lines);
    }

    private function wordCount(string $html): int
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');

        return $text === '' ? 0 : count(explode(' ', $text));
    }
}

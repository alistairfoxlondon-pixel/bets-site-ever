<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Thin, well behaved client for OpenRouter (or any OpenAI compatible
 * endpoint). Every call returns a structured result so the rest of the
 * application never has to think about transport details.
 */
class AiClient
{
    public const PROVIDER_OPENROUTER = 'openrouter';

    private ?string $lastError = null;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://openrouter.ai/api/v1',
        private readonly string $siteUrl = '',
        private readonly string $siteName = 'AdReady',
        private readonly int $timeout = 180,
    ) {
    }

    public static function make(?string $apiKey = null, ?string $model = null): self
    {
        $key = $apiKey ?? (string) (Setting::get('ai.api_key') ?: config('services.openrouter.key'));
        $base = (string) (Setting::get('ai.base_url') ?: config('services.openrouter.base_url'));
        $siteUrl = (string) (Setting::get('ai.site_url') ?: config('services.openrouter.site_url'));
        $siteName = (string) (Setting::get('ai.site_name') ?: config('app.name'));

        return new self($key, $base, $siteUrl, $siteName);
    }

    public function hasKey(): bool
    {
        return filled($this->apiKey);
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Send a chat completion request.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     * @param  array<string,mixed>  $options
     */
    public function chat(array $messages, string $model, array $options = []): AiResponse
    {
        if (! $this->hasKey()) {
            $this->lastError = 'No AI API key is configured. Add one in the admin panel under Settings.';

            return AiResponse::failed($this->lastError);
        }

        $payload = array_merge([
            'model' => $model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens' => $options['max_tokens'] ?? 8000,
        ], \Illuminate\Support\Arr::only($options['extra'] ?? [], ['top_p', 'frequency_penalty', 'presence_penalty', 'seed', 'stop']));

        if (! empty($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $attempts = (int) ($options['retries'] ?? 2);
        $response = null;

        for ($attempt = 0; $attempt <= $attempts; $attempt++) {
            try {
                /** @var Response $response */
                $response = $this->client($options['headers'] ?? [])
                    ->post($this->endpoint('chat/completions'), $payload);

                if ($response->successful()) {
                    return $this->parse($response, $model);
                }

                $status = $response->status();

                if (in_array($status, [408, 425, 429, 500, 502, 503, 504], true) && $attempt < $attempts) {
                    $this->sleepBackoff($attempt);

                    continue;
                }

                $this->lastError = $this->describeError($response);

                return AiResponse::failed($this->lastError);
            } catch (ConnectionException $exception) {
                $this->lastError = 'Could not reach the AI provider: '.$exception->getMessage();

                if ($attempt < $attempts) {
                    $this->sleepBackoff($attempt);

                    continue;
                }

                return AiResponse::failed($this->lastError);
            } catch (\Throwable $exception) {
                Log::error('AI request failed', ['message' => $exception->getMessage()]);
                $this->lastError = 'AI request failed: '.$exception->getMessage();

                return AiResponse::failed($this->lastError);
            }
        }

        $this->lastError = 'The AI provider did not answer in time.';

        return AiResponse::failed($this->lastError);
    }

    /**
     * Ask for a JSON object and decode it defensively.
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     * @param  array<string,mixed>  $options
     */
    public function chatJson(array $messages, string $model, array $options = []): AiResponse
    {
        $response = $this->chat($messages, $model, $options);

        if (! $response->successful()) {
            return $response;
        }

        $decoded = $this->decodeJson($response->content);

        if ($decoded === null) {
            $response->content = $response->content;

            return AiResponse::failed('The AI response was not valid JSON. Raw answer: '.Str::limit($response->content, 400));
        }

        $response->data = $decoded;

        return $response;
    }

    private function client(array $extraHeaders = []): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->withHeaders(array_merge([
                'HTTP-Referer' => $this->siteUrl,
                'X-Title' => $this->siteName,
                'Accept' => 'application/json',
            ], $extraHeaders))
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout(20);
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function parse(Response $response, string $requestedModel): AiResponse
    {
        $data = $response->json();

        $content = data_get($data, 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)->pluck('text')->filter()->implode("\n");
        }

        $content = (string) $content;

        $promptTokens = (int) (data_get($data, 'usage.prompt_tokens') ?? 0);
        $completionTokens = (int) (data_get($data, 'usage.completion_tokens') ?? 0);

        return new AiResponse(
            successful: true,
            content: $content,
            model: (string) (data_get($data, 'model') ?: $requestedModel),
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: (int) (data_get($data, 'usage.total_tokens') ?? ($promptTokens + $completionTokens)),
            raw: $data,
            error: null,
        );
    }

    private function describeError(Response $response): string
    {
        $body = $response->body();

        $error = data_get($response->json(), 'error');

        if (is_array($error)) {
            $message = $error['message'] ?? null;

            if (is_string($message) && $message !== '') {
                $code = $error['code'] ?? null;

                return $code ? "{$message} (code {$code})" : $message;
            }
        }

        if ($response->status() === 401) {
            return 'The AI provider rejected the API key. Check the key in the admin panel.';
        }

        if ($response->status() === 402) {
            return 'The AI provider account has no credit left.';
        }

        if ($response->status() === 429) {
            return 'The AI provider is rate limiting requests. Try again in a minute.';
        }

        return 'AI provider error (HTTP '.$response->status().'): '.Str::limit($body, 300);
    }

    private function decodeJson(string $content): ?array
    {
        $content = trim($content);

        if ($content === '') {
            return null;
        }

        // Strip markdown fences when a model wraps JSON in a code block.
        if (preg_match('/```(?:json)?\s*(.+?)```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        // Fall back to the first balanced JSON object in the answer.
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($content, $start, $end - $start + 1), true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function sleepBackoff(int $attempt): void
    {
        sleep(min(8, 2 ** $attempt));
    }
}

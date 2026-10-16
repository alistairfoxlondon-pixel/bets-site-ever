<?php

namespace App\Services\Audit;

class FetchResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly string $url,
        public readonly ?string $requestedUrl = null,
        public readonly int $status = 0,
        public readonly string $body = '',
        public readonly array $headers = [],
        public readonly int $durationMs = 0,
        public readonly int $bytes = 0,
        public readonly bool $truncated = false,
        public readonly ?string $error = null,
    ) {
    }

    public static function success(
        string $url,
        string $requestedUrl,
        int $status,
        string $body,
        array $headers,
        int $durationMs,
        int $bytes,
        bool $truncated = false,
    ): self {
        return new self(
            successful: true,
            url: $url,
            requestedUrl: $requestedUrl,
            status: $status,
            body: $body,
            headers: $headers,
            durationMs: $durationMs,
            bytes: $bytes,
            truncated: $truncated,
        );
    }

    public static function failed(string $url, string $error, int $status = 0): self
    {
        return new self(successful: false, url: $url, status: $status, error: $error);
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }

    public function contentType(): string
    {
        return (string) $this->header('Content-Type');
    }
}

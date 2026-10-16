<?php

namespace App\Services\Ai;

class AiResponse
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $content = '',
        public readonly string $model = '',
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly ?array $raw = null,
        public readonly ?string $error = null,
        public array $data = [],
    ) {
    }

    public static function failed(string $error): self
    {
        return new self(successful: false, error: $error);
    }

    public function cost(float $pricePerMillion = 0.0): float
    {
        if ($pricePerMillion <= 0) {
            return 0.0;
        }

        return round(($this->totalTokens / 1_000_000) * $pricePerMillion, 6);
    }
}

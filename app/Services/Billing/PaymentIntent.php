<?php

namespace App\Services\Billing;

class PaymentIntent
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $reference = null,
        public readonly array $instructions = [],
        public readonly ?string $error = null,
        public readonly array $payload = [],
    ) {
    }

    public static function redirect(string $url, ?string $reference = null, array $payload = []): self
    {
        return new self(successful: true, redirectUrl: $url, reference: $reference, payload: $payload);
    }

    public static function manual(array $instructions, ?string $reference = null): self
    {
        return new self(successful: true, instructions: $instructions, reference: $reference);
    }

    public static function failed(string $error): self
    {
        return new self(successful: false, error: $error);
    }
}

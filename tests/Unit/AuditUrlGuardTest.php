<?php

namespace Tests\Unit;

use App\Services\Audit\UnsafeUrlException;
use App\Services\Audit\UrlGuard;
use Tests\TestCase;

class AuditUrlGuardTest extends TestCase
{
    public function test_it_rejects_private_ip_addresses(): void
    {
        $this->expectException(UnsafeUrlException::class);

        (new UrlGuard)->assertSafe('http://127.0.0.1/');
    }

    public function test_it_rejects_non_standard_ports(): void
    {
        $this->expectException(UnsafeUrlException::class);

        (new UrlGuard)->assertSafe('https://publisher.example:8443/');
    }

    public function test_it_rejects_shared_and_translation_address_ranges(): void
    {
        $guard = new UrlGuard;

        $this->assertFalse($guard->isPublicIp('100.64.0.1'));
        $this->assertFalse($guard->isPublicIp('64:ff9b::7f00:1'));
        $this->assertTrue($guard->isPublicIp('93.184.216.34'));
        $this->assertTrue($guard->isPublicIp('2606:4700:4700::1111'));
    }

    public function test_it_rejects_non_standard_numeric_ip_forms(): void
    {
        $this->expectException(UnsafeUrlException::class);

        (new UrlGuard)->assertSafe('http://2130706433/');
    }

    public function test_it_rejects_embedded_credentials(): void
    {
        $this->expectException(UnsafeUrlException::class);

        (new UrlGuard)->normalize('https://user:password@example.com/');
    }

    public function test_it_returns_a_curl_resolution_for_the_validated_dns_address(): void
    {
        $guard = new class(['93.184.216.34']) extends UrlGuard
        {
            public function __construct(private array $addresses)
            {
            }

            protected function resolveAddresses(string $host): array
            {
                return $this->addresses;
            }
        };

        $target = $guard->requestTarget('https://publisher.example.com/articles/one');

        $this->assertSame('publisher.example.com:443:93.184.216.34', $target['resolve']);
        $this->assertSame('https://publisher.example.com/articles/one', $target['url']);
    }

    public function test_it_rejects_a_hostname_with_any_private_dns_answer(): void
    {
        $guard = new class(['93.184.216.34', '10.0.0.7']) extends UrlGuard
        {
            public function __construct(private array $addresses)
            {
            }

            protected function resolveAddresses(string $host): array
            {
                return $this->addresses;
            }
        };

        $this->expectException(UnsafeUrlException::class);

        $guard->requestTarget('https://publisher.example.com/');
    }

    public function test_it_canonicalises_scheme_case_and_drops_fragments(): void
    {
        $this->assertSame(
            'https://example.com/articles/one?view=full',
            (new UrlGuard)->normalize('HTTPS://example.com/articles/one?view=full#section')
        );
    }
}

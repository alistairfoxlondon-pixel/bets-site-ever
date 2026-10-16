<?php

namespace Tests\Unit;

use App\Services\Audit\SiteFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteFetcherTest extends TestCase
{
    public function test_response_body_is_bounded_and_marked_when_truncated(): void
    {
        Http::fake(fn () => Http::response(
            str_repeat('a', 4096),
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
        ));

        $result = (new SiteFetcher)->fetch('http://93.184.216.34/page', maxBytes: 1024);

        $this->assertTrue($result->successful);
        $this->assertTrue($result->truncated);
        $this->assertSame(1024, $result->bytes);
        $this->assertSame(1024, strlen($result->body));
    }
}

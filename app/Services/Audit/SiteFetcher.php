<?php

namespace App\Services\Audit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fetches a page for the audit. Redirects are followed manually so every hop
 * can be re validated against the SSRF rules.
 */
class SiteFetcher
{
    public function __construct(private readonly UrlGuard $guard = new UrlGuard)
    {
    }

    /**
     * @return FetchResult
     */
    public function fetch(string $url, ?string $userAgent = null, int $maxBytes = 3145728): FetchResult
    {
        $config = config('adready.crawler');
        $current = $url;
        $redirects = 0;
        $maxRedirects = (int) $config['max_redirects'];

        while (true) {
            try {
                $this->guard->assertSafe($current);
            } catch (UnsafeUrlException $exception) {
                return FetchResult::failed($current, $exception->getMessage());
            }

            try {
                /** @var Response $response */
                $response = Http::withHeaders([
                    'User-Agent' => $userAgent ?: $config['user_agent'],
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                    ->timeout((int) $config['timeout'])
                    ->connectTimeout((int) $config['connect_timeout'])
                    ->withOptions([
                        'allow_redirects' => false,
                        'stream' => true,
                        'version' => 1.1,
                    ])
                    ->get($current);
            } catch (ConnectionException $exception) {
                return FetchResult::failed($current, 'Could not connect to the site: '.$exception->getMessage());
            } catch (\Throwable $exception) {
                return FetchResult::failed($current, 'Request failed: '.$exception->getMessage());
            }

            if ($response->redirect()) {
                $location = $response->header('Location');

                if (blank($location) || $redirects >= $maxRedirects) {
                    return FetchResult::failed($current, 'The site redirected too many times.');
                }

                $current = $this->resolveRedirect($current, $location);
                $redirects++;

                continue;
            }

            $status = $response->status();

            if ($status >= 400) {
                return FetchResult::failed($current, 'The site returned HTTP '.$status.'.');
            }

            $contentType = (string) $response->header('Content-Type');

            if (filled($contentType) && ! Str::contains(strtolower($contentType), ['text/html', 'application/xhtml', 'text/plain', 'application/xml', 'text/xml'])) {
                return FetchResult::failed($current, 'That address does not return a web page (content type: '.$contentType.').');
            }

            $body = $response->body();

            if (strlen($body) > $maxBytes) {
                $body = substr($body, 0, $maxBytes);
            }

            return FetchResult::success(
                url: $current,
                requestedUrl: $url,
                status: $status,
                body: $body,
                headers: $response->headers(),
                durationMs: (int) round($response->transferStats()['total_time'] * 1000),
                bytes: strlen($body),
            );
        }
    }

    private function resolveRedirect(string $base, string $location): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);

        if ($parts === false || blank($parts['host'] ?? null)) {
            return $location;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$port.$location;
        }

        $path = $parts['path'] ?? '/';
        $directory = Str::beforeLast($path, '/');
        $directory = $directory === '' ? '/' : $directory;

        return $scheme.'://'.$host.$port.$directory.'/'.ltrim($location, './');
    }
}

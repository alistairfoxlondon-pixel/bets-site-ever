<?php

namespace App\Services\Audit;

use GuzzleHttp\Psr7\UriResolver;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fetches a page with bounded response reads, manual redirects and an IP-pinned
 * request target so each connection uses the address validated by UrlGuard.
 */
class SiteFetcher
{
    public function __construct(private readonly UrlGuard $guard = new UrlGuard)
    {
    }

    public function fetch(string $url, ?string $userAgent = null, ?int $maxBytes = null, ?int $timeoutLimit = null): FetchResult
    {
        $config = (array) config('adready.crawler', []);
        $current = $url;
        $redirects = 0;
        $maxRedirects = max(0, min(10, (int) ($config['max_redirects'] ?? 3)));
        $maxBytes = max(1024, min(5 * 1024 * 1024, $maxBytes ?? (int) ($config['max_bytes'] ?? 786432)));
        $timeout = max(1, min(30, (int) ($config['timeout'] ?? 20)));

        if ($timeoutLimit !== null) {
            $timeout = min($timeout, max(1, $timeoutLimit));
        }

        $connectTimeout = max(1, min(10, $timeout, (int) ($config['connect_timeout'] ?? 5)));

        while (true) {
            try {
                $target = $this->guard->requestTarget($current);
                $current = $target['url'];
            } catch (UnsafeUrlException $exception) {
                return FetchResult::failed($current, $exception->getMessage());
            }

            if ($target['resolve'] !== null && ! function_exists('curl_init')) {
                return FetchResult::failed($current, 'Secure hostname pinning requires the PHP cURL extension on this server.');
            }

            $curlOptions = [];

            if ($target['resolve'] !== null) {
                $curlOptions[CURLOPT_RESOLVE] = [$target['resolve']];
            }

            $requestStarted = microtime(true);

            try {
                /** @var Response $response */
                $response = Http::withHeaders([
                    'User-Agent' => $userAgent ?: (string) ($config['user_agent'] ?? 'AdReadyCrawler/1.0'),
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,text/plain;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                    ->timeout($timeout)
                    ->connectTimeout($connectTimeout)
                    ->withOptions([
                        'allow_redirects' => false,
                        'verify' => true,
                        'stream' => true,
                        'version' => 1.1,
                        'proxy' => '',
                        'cookies' => false,
                        'decode_content' => true,
                        'curl' => $curlOptions,
                    ])
                    ->get($current);
            } catch (ConnectionException $exception) {
                return FetchResult::failed($current, 'Could not connect to the site.');
            } catch (Throwable $exception) {
                report($exception);

                return FetchResult::failed($current, 'The request could not be completed safely.');
            }

            if ($response->redirect()) {
                $location = $response->header('Location');
                $response->close();

                if (blank($location) || $redirects >= $maxRedirects) {
                    return FetchResult::failed($current, 'The site redirected too many times.');
                }

                if (strlen($location) > 2048) {
                    return FetchResult::failed($current, 'The site returned an invalid redirect.');
                }

                try {
                    $current = $this->resolveRedirect($current, (string) $location);
                } catch (Throwable) {
                    return FetchResult::failed($current, 'The site returned an invalid redirect.');
                }

                $redirects++;

                continue;
            }

            $status = $response->status();

            if ($status >= 400) {
                $response->close();

                return FetchResult::failed($current, 'The site returned HTTP '.$status.'.', $status);
            }

            $contentType = (string) $response->header('Content-Type');

            if (filled($contentType) && ! Str::contains(strtolower($contentType), [
                'text/html', 'application/xhtml', 'text/plain', 'application/xml', 'text/xml',
            ])) {
                $response->close();

                return FetchResult::failed($current, 'That address does not return a supported text page.');
            }

            try {
                $stream = $response->toPsrResponse()->getBody();
                $body = '';
                $limit = $maxBytes + 1;

                while (! $stream->eof() && strlen($body) < $limit) {
                    $remaining = $limit - strlen($body);
                    $chunk = $stream->read(min(8192, $remaining));

                    if ($chunk === '') {
                        break;
                    }

                    $body .= $chunk;
                }

                $truncated = strlen($body) > $maxBytes;
                $body = substr($body, 0, $maxBytes);
                $durationMs = (int) round((microtime(true) - $requestStarted) * 1000);
                $responseHeaders = $response->headers();
                $response->close();

                return FetchResult::success(
                    url: $current,
                    requestedUrl: $url,
                    status: $status,
                    body: $body,
                    headers: $responseHeaders,
                    durationMs: $durationMs,
                    bytes: strlen($body),
                    truncated: $truncated,
                );
            } catch (Throwable $exception) {
                $response->close();
                report($exception);

                return FetchResult::failed($current, 'The response could not be read within the configured limits.');
            }
        }
    }

    private function resolveRedirect(string $base, string $location): string
    {
        return (string) UriResolver::resolve(
            Utils::uriFor($base),
            Utils::uriFor($location),
        );
    }
}

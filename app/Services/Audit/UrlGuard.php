<?php

namespace App\Services\Audit;

use Illuminate\Support\Str;

/**
 * Protects the crawler from being pointed at internal infrastructure.
 * Every outbound request (including redirects) is validated here first.
 */
class UrlGuard
{
    /**
     * @throws UnsafeUrlException
     */
    public function assertSafe(string $url): string
    {
        $url = $this->normalize($url);

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            throw new UnsafeUrlException('That does not look like a valid web address.');
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlException('Only http and https addresses can be audited.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new UnsafeUrlException('Addresses with embedded credentials are not allowed.');
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^\[|\]$/', '', $host) ?? $host;

        if ($this->isBlockedHost($host)) {
            throw new UnsafeUrlException('That address points to a private or reserved network and cannot be audited.');
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP);

        if ($ip !== false && ! $this->isPublicIp($ip)) {
            throw new UnsafeUrlException('That address points to a private or reserved network and cannot be audited.');
        }

        if ($ip === false && $this->resolvesToPrivateIp($host)) {
            throw new UnsafeUrlException('That address resolves to a private network and cannot be audited.');
        }

        return $url;
    }

    /**
     * Turn whatever the user typed into a canonical absolute URL.
     */
    public function normalize(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.$input;
        }

        $parts = parse_url($input);

        if ($parts === false || blank($parts['host'] ?? null)) {
            return $input;
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? null;
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        if ($path === '') {
            $path = '/';
        }

        $authority = $host;

        if ($port && ! (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $authority .= ':'.$port;
        }

        return $scheme.'://'.$authority.$path.$query;
    }

    public function host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return strtolower((string) $host);
    }

    public function isSameHost(string $a, string $b): bool
    {
        return $this->host($a) === $this->host($b) && $this->host($a) !== '';
    }

    public function isBlockedHost(string $host): bool
    {
        if ($host === '') {
            return true;
        }

        $blocked = ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback', 'broadcasthost'];

        if (in_array($host, $blocked, true)) {
            return true;
        }

        foreach (['.local', '.localhost', '.internal', '.localdomain', '.home.arpa'] as $suffix) {
            if (Str::endsWith($host, $suffix)) {
                return true;
            }
        }

        if (str_contains($host, 'metadata.google')) {
            return true;
        }

        return false;
    }

    public function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    public function resolvesToPrivateIp(string $host): bool
    {
        $addresses = @gethostbynamel($host);

        if (! is_array($addresses) || $addresses === []) {
            // A host that does not resolve will simply fail to connect later on.
            return false;
        }

        foreach ($addresses as $address) {
            if (! $this->isPublicIp($address)) {
                return true;
            }
        }

        return false;
    }
}

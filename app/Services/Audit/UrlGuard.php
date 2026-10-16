<?php

namespace App\Services\Audit;

use Illuminate\Support\Str;

/**
 * Validates public web addresses and returns an IP-pinned request target so a
 * DNS answer cannot change between validation and the outbound connection.
 */
class UrlGuard
{
    /**
     * Validate a user-submitted URL before it is stored.
     *
     * @throws UnsafeUrlException
     */
    public function assertSafe(string $url): string
    {
        return $this->requestTarget($url)['url'];
    }

    /**
     * Resolve and validate a URL for one outbound request.
     *
     * @return array{url:string,host:string,port:int,resolve:?string}
     * @throws UnsafeUrlException
     */
    public function requestTarget(string $input): array
    {
        $url = $this->normalize($input);
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            throw new UnsafeUrlException('That does not look like a valid web address.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlException('Only http and https addresses can be audited.');
        }

        $host = strtolower(rtrim(trim((string) $parts['host'], '[]'), '.'));

        if ($host === '' || $this->isBlockedHost($host)) {
            throw new UnsafeUrlException('That address points to a private or reserved network and cannot be audited.');
        }

        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $expectedPort = $scheme === 'https' ? 443 : 80;

        if ($port !== $expectedPort) {
            throw new UnsafeUrlException('Only standard HTTP and HTTPS ports can be audited.');
        }

        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if (! $isIp) {
            if ($this->looksLikeNonStandardIp($host) || ! str_contains($host, '.')) {
                throw new UnsafeUrlException('Enter a public website hostname or public IP address.');
            }

            if (! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i', $host)) {
                throw new UnsafeUrlException('That does not look like a valid public hostname.');
            }
        }

        $addresses = $isIp ? [$host] : $this->resolveAddresses($host);

        if ($addresses === []) {
            throw new UnsafeUrlException('That hostname did not resolve to a public address.');
        }

        if (count($addresses) > 64) {
            throw new UnsafeUrlException('That hostname returned too many addresses to validate safely.');
        }

        foreach ($addresses as $address) {
            if (! $this->isPublicIp($address)) {
                throw new UnsafeUrlException('That address resolves to a private or reserved network and cannot be audited.');
            }
        }

        $resolve = null;

        if (! $isIp) {
            // Pin one validated address for cURL while leaving the URL hostname
            // intact for Host headers, TLS SNI and certificate verification.
            $selected = $this->selectAddress($addresses);
            $curlAddress = str_contains($selected, ':') ? '['.$selected.']' : $selected;
            $resolve = $host.':'.$port.':'.$curlAddress;
        }

        return [
            'url' => $url,
            'host' => $host,
            'port' => $port,
            'resolve' => $resolve,
        ];
    }

    /** Canonicalise user input without retaining credentials or fragments. */
    public function normalize(string $input): string
    {
        $input = trim($input);

        if ($input === '' || strlen($input) > 2048 || preg_match('/[\x00-\x20\x7f]/', $input)) {
            throw new UnsafeUrlException('Enter a valid public web address.');
        }

        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.$input;
        }

        $parts = parse_url($input);

        if ($parts === false || blank($parts['host'] ?? null)) {
            throw new UnsafeUrlException('That does not look like a valid web address.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new UnsafeUrlException('Addresses with embedded credentials are not allowed.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlException('Only http and https addresses can be audited.');
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));

        if (preg_match('/[^\x20-\x7e]/', $host)) {
            if (! function_exists('idn_to_ascii')) {
                throw new UnsafeUrlException('Internationalised hostnames are not supported by this server.');
            }

            $asciiHost = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if (! is_string($asciiHost) || $asciiHost === '') {
                throw new UnsafeUrlException('That does not look like a valid public hostname.');
            }

            $host = strtolower(rtrim($asciiHost, '.'));
        }

        $isIpv6 = filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        $authorityHost = $isIpv6 ? '['.trim($host, '[]').']' : $host;
        $port = $parts['port'] ?? null;
        $path = (string) ($parts['path'] ?? '/');

        if ($path === '') {
            $path = '/';
        }

        $authority = $authorityHost;

        if ($port !== null) {
            $authority .= ':'.(int) $port;
        }

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$authority.$path.$query;
    }

    public function host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return strtolower(trim((string) $host, '[]'));
    }

    public function isSameHost(string $a, string $b): bool
    {
        return $this->host($a) === $this->host($b) && $this->host($a) !== '';
    }

    public function isBlockedHost(string $host): bool
    {
        $host = strtolower(rtrim(trim($host, '[]'), '.'));

        if ($host === '') {
            return true;
        }

        $blocked = [
            'localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback',
            'broadcasthost', 'metadata.google', 'metadata.google.internal',
            'metadata.goog', 'instance-data.ec2.internal',
        ];

        foreach ((array) config('adready.crawler.blocked_hosts', []) as $configuredHost) {
            if (! str_contains((string) $configuredHost, '/')) {
                $blocked[] = strtolower((string) $configuredHost);
            }
        }

        if (in_array($host, $blocked, true)) {
            return true;
        }

        foreach (['.local', '.localhost', '.internal', '.localdomain', '.home.arpa', '.arpa', '.test', '.invalid'] as $suffix) {
            if (Str::endsWith($host, $suffix)) {
                return true;
            }
        }

        foreach (['metadata.google', 'metadata.goog'] as $needle) {
            if (str_contains($host, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function isPublicIp(string $ip): bool
    {
        if (preg_match('/^::ffff:/i', $ip)
            || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $packedIp = @inet_pton($ip);

        if ($packedIp === false) {
            return false;
        }

        if (strlen($packedIp) === 4) {
            $blockedRanges = [
                '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8',
                '169.254.0.0/16', '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24',
                '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15', '198.51.100.0/24',
                '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
            ];
        } else {
            // Only global-unicast IPv6 is accepted. Explicitly exclude special-use
            // translation, benchmarking, documentation and transition prefixes.
            if ((ord($packedIp[0]) & 0xe0) !== 0x20) {
                return false;
            }

            $blockedRanges = [
                '2001::/23', '2001:db8::/32', '2002::/16', '3fff::/20',
                '64:ff9b::/96', '64:ff9b:1::/48',
            ];
        }

        foreach ($blockedRanges as $range) {
            if ($this->ipInCidr($packedIp, $range)) {
                return false;
            }
        }

        foreach ((array) config('adready.crawler.blocked_hosts', []) as $configuredRange) {
            if (str_contains((string) $configuredRange, '/') && $this->ipInCidr($packedIp, (string) $configuredRange)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int,string> */
    protected function resolveAddresses(string $host): array
    {
        $addresses = [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);

            if (is_array($records)) {
                foreach ($records as $record) {
                    if (! empty($record['ip'])) {
                        $addresses[] = (string) $record['ip'];
                    }

                    if (! empty($record['ipv6'])) {
                        $addresses[] = (string) $record['ipv6'];
                    }
                }
            }
        }

        // Keep IPv4-only DNS environments functional, but always validate the
        // complete address list returned by the resolver before connecting.
        if ($addresses === []) {
            $ipv4 = @gethostbynamel($host);

            if (is_array($ipv4)) {
                $addresses = $ipv4;
            }
        }

        return array_values(array_unique($addresses));
    }

    private function selectAddress(array $addresses): string
    {
        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return $address;
            }
        }

        return (string) ($addresses[0] ?? '');
    }

    private function ipInCidr(string $packedIp, string $cidr): bool
    {
        [$network, $prefixText] = array_pad(explode('/', $cidr, 2), 2, null);
        $packedNetwork = @inet_pton((string) $network);
        $prefix = filter_var($prefixText, FILTER_VALIDATE_INT);

        if ($packedNetwork === false || strlen($packedNetwork) !== strlen($packedIp)
            || $prefix === false || $prefix < 0 || $prefix > strlen($packedIp) * 8) {
            return false;
        }

        $wholeBytes = intdiv($prefix, 8);

        if ($wholeBytes > 0 && substr($packedIp, 0, $wholeBytes) !== substr($packedNetwork, 0, $wholeBytes)) {
            return false;
        }

        $remainingBits = $prefix % 8;

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($packedIp[$wholeBytes]) & $mask) === (ord($packedNetwork[$wholeBytes]) & $mask);
    }

    private function looksLikeNonStandardIp(string $host): bool
    {
        if (preg_match('/^[0-9.]+$/', $host)) {
            return true;
        }

        return (bool) preg_match('/^(?:0x[0-9a-f]+|0[0-7]+)(?:\.(?:0x[0-9a-f]+|0[0-7]*))*$/i', $host);
    }
}

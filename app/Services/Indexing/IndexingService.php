<?php

namespace App\Services\Indexing;

use App\Models\IndexingSubmission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pushes a URL to the Google Indexing API and to the IndexNow network
 * (Bing, Yandex and the other IndexNow partners).
 */
class IndexingService
{
    public const ENGINES = [
        'google' => 'Google Indexing API',
        'bing' => 'Bing via IndexNow',
        'indexnow' => 'IndexNow',
    ];

    /**
     * @return array<int,array{engine:string,status:string,message:string}>
     */
    public function submit(User $user, string $url, array $engines): array
    {
        $results = [];

        foreach ($engines as $engine) {
            $engine = (string) $engine;

            if (! isset(self::ENGINES[$engine])) {
                continue;
            }

            $result = match ($engine) {
                'google' => $this->submitToGoogle($user, $url),
                default => $this->submitToIndexNow($user, $url, $engine),
            };

            $results[] = $result;

            IndexingSubmission::create([
                'user_id' => $user->id,
                'url' => $url,
                'engine' => $engine,
                'status' => $result['status'],
                'message' => $result['message'],
                'response' => $result['response'] ?? null,
                'submitted_at' => now(),
            ]);
        }

        return $results;
    }

    /**
     * @return array{engine:string,status:string,message:string,response?:array}
     */
    private function submitToGoogle(User $user, string $url): array
    {
        $token = $this->googleAccessToken();

        if ($token === null) {
            return [
                'engine' => 'google',
                'status' => 'failed',
                'message' => 'The Google Indexing API is not configured. Add the service account JSON in the admin panel under Settings.',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(30)
                ->post('https://www.googleapis.com/indexing/v3/urlNotifications', [
                    'url' => $url,
                    'type' => 'URL_INDEXING',
                ]);
        } catch (\Throwable $exception) {
            Log::error('Indexing API request failed', ['message' => $exception->getMessage()]);

            return [
                'engine' => 'google',
                'status' => 'failed',
                'message' => 'The Google Indexing API could not be reached.',
            ];
        }

        if ($response->successful()) {
            return [
                'engine' => 'google',
                'status' => 'success',
                'message' => 'Google accepted the URL for indexing.',
                'response' => $response->json(),
            ];
        }

        $error = data_get($response->json(), 'error.message') ?: 'HTTP '.$response->status();

        return [
            'engine' => 'google',
            'status' => 'failed',
            'message' => 'Google rejected the request: '.$error,
            'response' => $response->json(),
        ];
    }

    /**
     * @return array{engine:string,status:string,message:string,response?:array}
     */
    private function submitToIndexNow(User $user, string $url, string $engine): array
    {
        $key = (string) (Setting::get('indexing.indexnow_key') ?: '');
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        if ($key === '') {
            $key = $this->hostKey($host);
        }

        if ($key === '' || $host === '') {
            return [
                'engine' => $engine,
                'status' => 'failed',
                'message' => 'The IndexNow key is not configured. Add it in the admin panel under Settings.',
            ];
        }

        $keyLocation = (string) (Setting::get('indexing.indexnow_key_location') ?: rtrim((string) config('app.url'), '/').'/'.$key.'.txt');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'X-Api-Key' => $key,
            ])
                ->timeout(30)
                ->post('https://api.indexnow.org/indexnow', [
                    'host' => $host,
                    'key' => $key,
                    'keyLocation' => $keyLocation,
                    'urlList' => [$url],
                ]);
        } catch (\Throwable $exception) {
            Log::error('IndexNow request failed', ['message' => $exception->getMessage()]);

            return [
                'engine' => $engine,
                'status' => 'failed',
                'message' => 'IndexNow could not be reached.',
            ];
        }

        if ($response->successful() || $response->status() === 202) {
            return [
                'engine' => $engine,
                'status' => 'success',
                'message' => 'IndexNow accepted the URL. The URL was shared with every participating engine.',
                'response' => $response->json(),
            ];
        }

        return [
            'engine' => $engine,
            'status' => 'failed',
            'message' => 'IndexNow returned HTTP '.$response->status().': '.Str::limit((string) $response->body(), 180),
            'response' => $response->json(),
        ];
    }

    /**
     * Build a signed access token for the Google Indexing API using the
     * service account JSON stored in the settings table.
     */
    private function googleAccessToken(): ?string
    {
        $raw = Setting::get('indexing.google_service_account');

        if (! is_string($raw) || blank($raw)) {
            return null;
        }

        $account = json_decode($raw, true);

        if (! is_array($account) || blank($account['client_email'] ?? null) || blank($account['private_key'] ?? null)) {
            return null;
        }

        $scope = 'https://www.googleapis.com/auth/indexing';
        $now = time();
        $assertion = $this->jwt($account, $scope, $now);

        try {
            $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Google token request failed', ['message' => $exception->getMessage()]);

            return null;
        }

        return $response->successful() ? $response->json('access_token') : null;
    }

    /**
     * @param  array<string,mixed>  $account
     */
    private function jwt(array $account, string $scope, int $now): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $account['client_email'],
            'scope' => $scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $signingInput = $this->base64Url(json_encode($header, JSON_UNESCAPED_SLASHES))
            .'.'.$this->base64Url(json_encode($claims, JSON_UNESCAPED_SLASHES));

        $key = openssl_pkey_get_private($account['private_key']);

        if ($key === false) {
            throw new \RuntimeException('The Google service account private key could not be read.');
        }

        $signature = '';

        openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);

        return $signingInput.'.'.$this->base64Url($signature);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function hostKey(string $host): string
    {
        if ($host === '') {
            return '';
        }

        return substr(hash('sha256', $host.'|'.config('app.key')), 0, 32);
    }

    /**
     * Public helper so the key file can be served from a controller.
     */
    public function indexNowKey(): string
    {
        return (string) (Setting::get('indexing.indexnow_key') ?: '');
    }
}

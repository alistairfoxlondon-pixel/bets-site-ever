<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'is_public',
        'label',
        'hint',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public static function cacheKey(string $key): string
    {
        return 'adready.setting.'.$key;
    }

    /**
     * Read a setting, decrypting values that contain credentials or private
     * keys. Older installations may have stored these as plain text; those
     * values are migrated the first time they are read.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember(self::cacheKey($key), 300, function () use ($key) {
            $setting = static::where('key', $key)->first();

            return $setting?->value;
        });

        if ($value === null) {
            return $default;
        }

        if (! self::isSensitiveKey($key) || ! is_string($value) || $value === '') {
            return $value;
        }

        try {
            $plain = Crypt::decryptString($value);
            $decoded = json_decode($plain, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $plain;
        } catch (DecryptException) {
            // Backward compatible lazy migration for keys saved before
            // encryption at rest was added.
            self::put($key, $value);

            return $value;
        }
    }

    public static function put(string $key, mixed $value, array $attributes = []): Setting
    {
        if (self::isSensitiveKey($key) && $value !== null && $value !== '') {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $value = Crypt::encryptString($encoded === false ? (string) $value : $encoded);
        }

        $setting = static::updateOrCreate(
            ['key' => $key],
            array_merge(['value' => $value], $attributes)
        );

        Cache::forget(self::cacheKey($key));

        return $setting;
    }

    /**
     * Match explicit application secrets and common secret suffixes used by
     * the admin panel's raw-value editor. Provider IDs and model names remain
     * ordinary settings; actual credentials are encrypted with APP_KEY.
     */
    public static function isSensitiveKey(string $key): bool
    {
        return in_array($key, [
            'ai.api_key',
            'auth.google_client_secret',
            'indexing.google_service_account',
            'indexing.indexnow_key',
        ], true) || (bool) preg_match('/(?:^|[._-])(secret|token|password|private[_-]?key|api[_-]?key)$/i', $key);
    }

    protected static function booted(): void
    {
        static::saved(function (self $setting) {
            Cache::forget(self::cacheKey($setting->key));
        });

        static::deleted(function (self $setting) {
            Cache::forget(self::cacheKey($setting->key));
        });
    }
}

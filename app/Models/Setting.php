<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember(self::cacheKey($key), 300, function () use ($key) {
            $setting = static::where('key', $key)->first();

            return $setting?->value;
        });

        return $value ?? $default;
    }

    public static function put(string $key, mixed $value, array $attributes = []): Setting
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            array_merge(['value' => $value], $attributes)
        );

        Cache::forget(self::cacheKey($key));

        return $setting;
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

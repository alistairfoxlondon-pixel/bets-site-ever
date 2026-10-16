<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Handles the two currencies the product is priced in. The primary currency
 * is US dollars and Bangladeshi taka is shown alongside it.
 */
class Currency
{
    public static function primary(): string
    {
        return strtoupper((string) (Setting::get('currency.primary') ?: config('adready.currency.primary')));
    }

    public static function secondary(): string
    {
        return strtoupper((string) config('adready.currency.secondary'));
    }

    public static function rate(): float
    {
        $stored = Setting::get('currency.bdt_rate');

        return (float) ($stored ?: config('adready.currency.bdt_rate'));
    }

    public static function toBdt(float $amount): float
    {
        return round($amount * self::rate(), 2);
    }

    public static function fromBdt(float $amount): float
    {
        $rate = self::rate();

        return $rate > 0 ? round($amount / $rate, 2) : $amount;
    }

    /**
     * @return array{code:string,symbol:string,amount:float,formatted:string}
     */
    public static function format(float $amount, ?string $currency = null): array
    {
        $code = strtoupper($currency ?: self::primary());

        if ($code === 'BDT') {
            return [
                'code' => 'BDT',
                'symbol' => '৳',
                'amount' => $amount,
                'formatted' => '৳'.number_format($amount, 2),
            ];
        }

        return [
            'code' => $code,
            'symbol' => '$',
            'amount' => $amount,
            'formatted' => '$'.number_format($amount, 2),
        ];
    }

    public static function formatUsd(float $amount): string
    {
        return '$'.number_format($amount, 2);
    }

    public static function formatBdt(float $amount): string
    {
        return '৳'.number_format(self::toBdt($amount), 2);
    }

    public static function both(float $usdAmount): string
    {
        return self::formatUsd($usdAmount).' / '.self::formatBdt($usdAmount);
    }
}

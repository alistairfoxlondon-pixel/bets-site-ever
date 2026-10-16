<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Time based one time password helpers (RFC 6238) so the product does not
 * need a third party package for two factor authentication.
 */
class TwoFactor
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 32): string
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $secret;
    }

    public static function base32Encode(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $bits = '';

        for ($i = 0, $length = strlen($value); $i < $length; $i++) {
            $bits .= str_pad(decbin(ord($value[$i])), 8, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $output;
    }

    public static function base32Decode(string $value): string
    {
        $value = strtoupper(rtrim($value, '='));
        $bits = '';

        for ($i = 0, $length = strlen($value); $i < $length; $i++) {
            $position = strpos(self::ALPHABET, $value[$i]);

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $output .= chr(bindec($chunk));
            }
        }

        return $output;
    }

    public static function codeAt(string $secret, ?int $timestamp = null, int $digits = 6): string
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, 30);
        $binaryCounter = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, self::base32Decode($secret), true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $value = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, time() + ($i * 30)), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function otpAuthUri(string $issuer, string $account, string $secret): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?secret='.$secret
            .'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    /**
     * @return array<int,string>
     */
    public static function recoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5));
        }

        return $codes;
    }
}

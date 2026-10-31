<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use InvalidArgumentException;

/**
 * Time-based one-time passwords for authenticator apps (RFC 6238 over the
 * HOTP of RFC 4226): HMAC-SHA1, a 30-second step, 6 digits, and one step
 * either side of now for a phone clock that drifts. Secrets are 160 random
 * bits, written in Base32 (RFC 4648) as authenticator apps expect.
 *
 * Pure functions only: who may use a code, and which steps are already used,
 * are PlatformTwoFactor's business. engineering/specs/platform/hq-two-factor.md.
 */
final class Totp
{
    public const DIGITS = 6;

    public const PERIOD = 30;

    /** Steps accepted either side of the current one. */
    public const WINDOW = 1;

    /** 160 bits, the HMAC-SHA1 block RFC 4226 recommends. */
    public const SECRET_BYTES = 20;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** A new random secret, Base32 without padding (32 characters). */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /** The time step a Unix time falls in. */
    public static function step(int $timestamp): int
    {
        return intdiv($timestamp, self::PERIOD);
    }

    /** The code for a Base32 secret at a time step. */
    public static function code(string $secret, int $step): string
    {
        return self::hotp(self::base32Decode($secret), $step, self::DIGITS);
    }

    /**
     * RFC 4226 HOTP over raw key bytes, HMAC-SHA1 with dynamic truncation.
     * TOTP is this with the time step as the counter.
     */
    public static function hotp(string $key, int $counter, int $digits = self::DIGITS): string
    {
        if ($counter < 0 || $digits < 6 || $digits > 8) {
            throw new InvalidArgumentException('A counter of zero or more and 6 to 8 digits.');
        }
        $hash = hash_hmac('sha1', pack('J', $counter), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * The step a code matches, within the window around $timestamp, or null.
     * A step at or before $lastUsedStep never matches, so a code works once.
     * Every candidate is compared in constant time (hash_equals).
     */
    public static function verify(string $secret, string $code, int $timestamp, ?int $lastUsedStep = null): ?int
    {
        if (preg_match('/^\d{'.self::DIGITS.'}$/D', $code) !== 1) {
            return null;
        }
        $key = self::base32Decode($secret);
        $now = self::step($timestamp);
        $matched = null;
        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            $step = $now + $offset;
            if ($step < 0 || ($lastUsedStep !== null && $step <= $lastUsedStep)) {
                continue;
            }
            if (hash_equals(self::hotp($key, $step), $code) && $matched === null) {
                $matched = $step;
            }
        }

        return $matched;
    }

    /** The otpauth:// address an authenticator app reads from the QR code. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account).'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $bytes === '' ? '' : $out;
    }

    public static function base32Decode(string $text): string
    {
        $text = strtoupper((string) preg_replace('/[\s=-]/', '', $text));
        if ($text === '' || strspn($text, self::ALPHABET) !== strlen($text)) {
            throw new InvalidArgumentException('Not a Base32 secret.');
        }
        $bits = '';
        foreach (str_split($text) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int) bindec($byte));
            }
        }

        return $out;
    }
}

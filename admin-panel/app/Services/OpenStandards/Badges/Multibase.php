<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use InvalidArgumentException;

/**
 * Multibase base58-btc ("z" prefix), as Data Integrity proofs and Multikey
 * public keys use it, and the did:key form of an Ed25519 public key.
 */
final class Multibase
{
    private const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /** The multicodec prefix of an Ed25519 public key (0xed, varint-encoded). */
    private const ED25519_PUBLIC = "\xed\x01";

    public static function encode(string $bytes): string
    {
        $digits = [0];
        foreach (str_split($bytes) as $char) {
            $carry = ord($char);
            foreach ($digits as $i => $digit) {
                $carry += $digit << 8;
                $digits[$i] = $carry % 58;
                $carry = intdiv($carry, 58);
            }
            while ($carry > 0) {
                $digits[] = $carry % 58;
                $carry = intdiv($carry, 58);
            }
        }
        $out = '';
        for ($i = 0, $n = strlen($bytes); $i < $n && $bytes[$i] === "\0"; $i++) {
            $out .= '1';
        }
        if ($bytes === '' || ltrim($bytes, "\0") !== '') {
            foreach (array_reverse($digits) as $digit) {
                $out .= self::ALPHABET[$digit];
            }
        }

        return 'z'.($bytes === '' ? '' : $out);
    }

    public static function decode(string $value): string
    {
        if (! str_starts_with($value, 'z') || strlen($value) > 256) {
            throw new InvalidArgumentException('Not a base58-btc multibase value.');
        }
        $text = substr($value, 1);
        $bytes = [0];
        foreach (str_split($text) as $char) {
            $carry = strpos(self::ALPHABET, $char);
            if ($carry === false) {
                throw new InvalidArgumentException('Not a base58-btc multibase value.');
            }
            foreach ($bytes as $i => $byte) {
                $carry += $byte * 58;
                $bytes[$i] = $carry & 0xFF;
                $carry >>= 8;
            }
            while ($carry > 0) {
                $bytes[] = $carry & 0xFF;
                $carry >>= 8;
            }
        }
        $leading = strspn($text, '1');
        $body = ltrim($text, '1') === '' ? '' : implode('', array_map(chr(...), array_reverse($bytes)));

        return str_repeat("\0", $leading).$body;
    }

    /** The Multikey publicKeyMultibase of an Ed25519 public key ("z6Mk..."). */
    public static function ed25519PublicKey(string $publicKey): string
    {
        return self::encode(self::ED25519_PUBLIC.$publicKey);
    }

    /** The did:key verification method of an Ed25519 public key. */
    public static function didKey(string $publicKey): string
    {
        $key = self::ed25519PublicKey($publicKey);

        return 'did:key:'.$key.'#'.$key;
    }
}

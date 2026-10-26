<?php

declare(strict_types=1);

namespace App\Services\Cards\Crypto;

use InvalidArgumentException;
use UnexpectedValueException;

/** NTAG 424 DNA: encrypted PICC metadata, seven-byte UID + 24-bit read counter. */
class SunVerifier
{
    public function __construct(private AesCmac $cmac) {}

    private function hex(string $value, int $bytes): string
    {
        if (strlen($value) !== $bytes * 2 || ! ctype_xdigit($value)) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }

        return hex2bin($value);
    }

    public function sessionKey(string $fileReadKey, string $uid, string $counter, bool $encryption = false): string
    {
        if (strlen($fileReadKey) !== 16 || strlen($uid) !== 7 || strlen($counter) !== 3) {
            throw new InvalidArgumentException('Invalid SUN key derivation inputs.');
        }

        return $this->cmac->mac($fileReadKey, hex2bin($encryption ? 'C33C00010080' : '3CC300010080').$uid.$counter);
    }

    /** macInput comes only from the selected encoding profile, never a scan request. */
    public function verify(string $piccHex, string $macHex, string $metadataKey, string $fileReadKey, string $expectedUidHex, string $macInput = ''): array
    {
        if (strlen($metadataKey) !== 16 || strlen($fileReadKey) !== 16 || strlen($macInput) > 2048) {
            throw new InvalidArgumentException('Invalid configured SUN profile.');
        }
        $encrypted = $this->hex($piccHex, 16);
        $mac = $this->hex($macHex, 8);
        $expected = $this->hex($expectedUidHex, 7);
        $plain = openssl_decrypt($encrypted, 'aes-128-cbc', $metadataKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, str_repeat("\0", 16));
        if ($plain === false || strlen($plain) !== 16 || ord($plain[0]) !== 0xC7) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }
        $uid = substr($plain, 1, 7);
        $counter = substr($plain, 8, 3);
        $session = $this->sessionKey($fileReadKey, $uid, $counter);
        if (! hash_equals($this->cmac->truncated($session, $macInput), $mac) || ! hash_equals($expected, $uid)) {
            throw new UnexpectedValueException('The card proof could not be verified.');
        }

        return ['uid' => strtoupper(bin2hex($uid)), 'counter' => ord($counter[0]) | (ord($counter[1]) << 8) | (ord($counter[2]) << 16)];
    }
}

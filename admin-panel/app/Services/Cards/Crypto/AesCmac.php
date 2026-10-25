<?php

declare(strict_types=1);

namespace App\Services\Cards\Crypto;

use InvalidArgumentException;
use RuntimeException;

/** NIST SP 800-38B / RFC 4493 AES-128 CMAC, binary input and output. */
class AesCmac
{
    public function encryptBlock(string $key, string $block): string
    {
        if (strlen($key) !== 16 || strlen($block) !== 16) {
            throw new InvalidArgumentException('AES-128 requires a 16-byte key and block.');
        }
        $result = openssl_encrypt($block, 'aes-128-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        if ($result === false || strlen($result) !== 16) {
            throw new RuntimeException('The card cryptographic operation failed.');
        }

        return $result;
    }

    private function double(string $block): string
    {
        $carry = 0;
        $result = '';
        for ($i = 15; $i >= 0; $i--) {
            $byte = ord($block[$i]);
            $result = chr((($byte << 1) & 255) | $carry).$result;
            $carry = ($byte >> 7) & 1;
        }
        if ($carry !== 0) {
            $result[15] = chr(ord($result[15]) ^ 0x87);
        }

        return $result;
    }

    public function mac(string $key, string $message): string
    {
        if (strlen($key) !== 16 || strlen($message) > 1048576) {
            throw new InvalidArgumentException('Invalid card MAC key or message size.');
        }
        $zero = str_repeat("\0", 16);
        $k1 = $this->double($this->encryptBlock($key, $zero));
        $k2 = $this->double($k1);
        $blocks = max(1, (int) ceil(strlen($message) / 16));
        $last = substr($message, ($blocks - 1) * 16);
        $last = strlen($last) === 16 ? ($last ^ $k1) : (str_pad($last."\x80", 16, "\0") ^ $k2);
        $state = $zero;
        for ($i = 0; $i < $blocks - 1; $i++) {
            $state = $this->encryptBlock($key, $state ^ substr($message, $i * 16, 16));
        }

        return $this->encryptBlock($key, $state ^ $last);
    }

    /** NXP MACt keeps byte indices 1,3,...,15, not the first eight bytes. */
    public function truncated(string $key, string $message): string
    {
        $full = $this->mac($key, $message);
        $result = '';
        for ($i = 1; $i < 16; $i += 2) {
            $result .= $full[$i];
        }

        return $result;
    }
}

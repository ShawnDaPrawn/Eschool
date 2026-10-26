<?php

declare(strict_types=1);

namespace App\Services\Cards\Encoding;

/** NXP AN12196: file 2, encrypted PICC metadata/key 2 and empty-input MAC/key 1. */
class NtagEncodingProfile
{
    public function create(string $base, int $school, string $card): array
    {
        $parsed = parse_url($base);
        abort_unless(is_array($parsed) && isset($parsed['scheme'], $parsed['host'])
            && ! isset($parsed['user']) && ! isset($parsed['pass']) && ! isset($parsed['query']) && ! isset($parsed['fragment'])
            && ($parsed['scheme'] === 'https' || (app()->environment(['testing', 'local']) && $parsed['scheme'] === 'http')), 503, 'Configure a secure public school URL before encoding cards.');
        $url = rtrim($base, '/').'/card-tap#s='.$school.'&c='.$card.'&e='.str_repeat('0', 32).'&m='.str_repeat('0', 16);
        abort_unless(preg_match('/^[\x21-\x7E]+$/D', $url) === 1, 503);
        // URI record, no abbreviated prefix. Offsets include the two-byte NLEN.
        $uri = "\x00".$url;
        abort_unless(strlen($uri) <= 245, 503, 'The school URL is too long for this card profile.');
        $message = "\xD1\x01".chr(strlen($uri))."\x55".$uri;
        $file = pack('n', strlen($message)).$message;
        $mac = strlen($file) - 16;
        $picc = $mac - 3 - 32;
        abort_unless($picc + 32 <= $mac && $mac + 16 <= strlen($file) && strlen($file) <= 256, 503);
        $offset = fn (int $value) => substr(pack('V', $value), 0, 3);
        $settings = hex2bin('4000E0C1F121').$offset($picc).$offset($mac).$offset($mac);

        return ['ndef' => base64_encode(str_pad($file, 256, "\0")), 'settings' => strtoupper(bin2hex($settings))];
    }
}

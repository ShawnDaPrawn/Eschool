<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** A private, approved derivative. Pixel content still needs feature privacy approval. */
final readonly class VisionImage
{
    public const MAXIMUM_BYTES = 1048576;

    public function __construct(public string $id, public string $sha256, public string $bytes)
    {
        if (! preg_match('/^[a-zA-Z0-9_-]{1,96}$/D', $id) || ! preg_match('/^[a-f0-9]{64}$/D', $sha256)
            || ! hash_equals($sha256, hash('sha256', $bytes))) {
            throw new AiUnavailable('vision_media_invalid');
        }
        self::validateBytes($bytes);
    }

    /** Only pixel PNG chunks: reject embedded metadata, text, profiles and animation. */
    public static function validateBytes(string $bytes): void
    {
        if (strlen($bytes) < 45 || strlen($bytes) > self::MAXIMUM_BYTES || substr($bytes, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            throw new AiUnavailable('vision_media_invalid');
        }
        $offset = 8;
        $seen = [];
        while ($offset < strlen($bytes)) {
            if ($offset + 12 > strlen($bytes)) {
                throw new AiUnavailable('vision_media_invalid');
            }
            $length = unpack('N', substr($bytes, $offset, 4))[1];
            $type = substr($bytes, $offset + 4, 4);
            if ($length > self::MAXIMUM_BYTES || $offset + 12 + $length > strlen($bytes)
                || ! in_array($type, ['IHDR', 'IDAT', 'IEND'], true)
                || ($offset === 8 && ($type !== 'IHDR' || $length !== 13))
                || ($type === 'IHDR' && $seen !== []) || ($type === 'IEND' && ($length !== 0 || $offset + 12 !== strlen($bytes)))
                || ! hash_equals(hash('crc32b', substr($bytes, $offset + 4, $length + 4), true), substr($bytes, $offset + 8 + $length, 4))) {
                throw new AiUnavailable('vision_media_invalid');
            }
            $seen[] = $type;
            $offset += 12 + $length;
        }
        $size = @getimagesizefromstring($bytes);
        if (end($seen) !== 'IEND' || ! in_array('IDAT', $seen, true) || $size === false
            || $size[0] < 1 || $size[1] < 1 || $size[0] > 1024 || $size[1] > 1024) {
            throw new AiUnavailable('vision_media_invalid');
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new AiUnavailable('vision_media_invalid');
        }
        imagedestroy($image);
    }

    public function reference(): array
    {
        return ['id' => $this->id, 'sha256' => $this->sha256, 'mime' => 'image/png'];
    }
}

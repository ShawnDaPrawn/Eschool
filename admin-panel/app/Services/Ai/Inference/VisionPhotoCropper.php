<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** Explicit approved working-area crop, not OCR or a claim of visible-PII detection. */
class VisionPhotoCropper
{
    public function crop(string $id, string $original, array $rectangle, bool $approved): VisionImage
    {
        if (! $approved || array_keys($rectangle) !== ['x', 'y', 'width', 'height'] || strlen($original) > 4194304) {
            throw new AiUnavailable('vision_crop_approval_required');
        }
        foreach ($rectangle as $value) {
            if (! is_int($value) || $value < 0) {
                throw new AiUnavailable('vision_crop_invalid');
            }
        }
        $size = @getimagesizefromstring($original);
        if ($size === false || ! in_array($size[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) || $size[0] < 1 || $size[1] < 1
            || $size[0] > 8192 || $size[1] > 8192 || $size[0] * $size[1] > 16777216
            || $rectangle['width'] < 1 || $rectangle['height'] < 1 || $size[0] < $rectangle['x'] + $rectangle['width']
            || $size[1] < $rectangle['y'] + $rectangle['height']) {
            throw new AiUnavailable('vision_crop_invalid');
        }
        $image = @imagecreatefromstring($original);
        if ($image === false) {
            throw new AiUnavailable('vision_crop_invalid');
        }
        $ratio = min(1, 768 / max($rectangle['width'], $rectangle['height']));
        $width = max(1, (int) round($rectangle['width'] * $ratio));
        $height = max(1, (int) round($rectangle['height'] * $ratio));
        $canvas = imagecreatetruecolor($width, $height);
        try {
            if ($canvas === false || ! imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255))
                || ! imagecopyresampled($canvas, $image, 0, 0, $rectangle['x'], $rectangle['y'], $width, $height, $rectangle['width'], $rectangle['height'])) {
                throw new AiUnavailable('vision_crop_invalid');
            }
            ob_start();
            try {
                if (! imagepng($canvas, null, 9)) {
                    throw new AiUnavailable('vision_crop_invalid');
                }
                $encoded = (string) ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($image);
            if ($canvas !== false) {
                imagedestroy($canvas);
            }
        }
        // GD may add resolution chunks; retain only its newly encoded pixels.
        $pixels = substr($encoded, 0, 8);
        for ($offset = 8; $offset < strlen($encoded);) {
            $length = unpack('N', substr($encoded, $offset, 4))[1];
            if (in_array(substr($encoded, $offset + 4, 4), ['IHDR', 'IDAT', 'IEND'], true)) {
                $pixels .= substr($encoded, $offset, $length + 12);
            }
            $offset += $length + 12;
        }

        return new VisionImage($id, hash('sha256', $pixels), $pixels);
    }
}

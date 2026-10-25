<?php

declare(strict_types=1);

namespace App\Services\Platform\Files;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Pixels only: no original filename, EXIF, XMP, device or location metadata. */
class SafeImages
{
    public const MAXIMUM_BYTES = 4194304;

    public static function rules(): array
    {
        return ['photo' => ['required', 'file', 'mimes:jpeg,png', 'extensions:jpg,jpeg,png', 'max:4096']];
    }

    private function refuse(): never
    {
        throw ValidationException::withMessages(['photo' => 'Choose a JPEG or PNG photo within its safe size and resolution limits.']);
    }

    private function jpeg(GdImage $source, int $edge): string
    {
        $ratio = min(1, $edge / max(imagesx($source), imagesy($source)));
        $width = max(1, (int) round(imagesx($source) * $ratio));
        $height = max(1, (int) round(imagesy($source) * $ratio));
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            $this->refuse();
        }
        try {
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            if (! imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source))) {
                $this->refuse();
            }
            ob_start();
            try {
                if (! imagejpeg($canvas, null, 82)) {
                    $this->refuse();
                }

                return (string) ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } finally {
            imagedestroy($canvas);
        }
    }

    /** @return array{bytes:string,thumbnail:string,width:int,height:int} */
    public function sanitize(UploadedFile $file): array
    {
        Validator::make(['photo' => $file], self::rules())->validate();
        $bytes = $file->getContent();
        $size = @getimagesizefromstring($bytes);
        if ($bytes === '' || strlen($bytes) > self::MAXIMUM_BYTES || $size === false || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) || $size[0] < 1 || $size[1] < 1 || $size[0] > 8192 || $size[1] > 8192 || $size[0] * $size[1] > 16777216) {
            $this->refuse();
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            $this->refuse();
        }
        try {
            $orientation = 1;
            if ($size[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getRealPath(), 'IFD0', true, false);
                $candidate = is_array($exif) ? ($exif['IFD0']['Orientation'] ?? null) : null;
                if (is_int($candidate) && $candidate >= 1 && $candidate <= 8) {
                    $orientation = $candidate;
                }
                unset($exif);
            }
            if (in_array($orientation, [3, 5, 6, 7, 8], true)) {
                $angle = match ($orientation) {
                    3 => 180, 8 => 90, default => -90
                };
                $rotated = imagerotate($image, $angle, 0);
                if ($rotated === false) {
                    $this->refuse();
                }
                imagedestroy($image);
                $image = $rotated;
            }
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($image, in_array($orientation, [2, 5], true) ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
            }
            $output = $this->jpeg($image, 2048);
            $dimensions = getimagesizefromstring($output);
            if ($dimensions === false) {
                $this->refuse();
            }

            return ['bytes' => $output, 'thumbnail' => $this->jpeg($image, 360), 'width' => $dimensions[0], 'height' => $dimensions[1]];
        } finally {
            imagedestroy($image);
        }
    }
}

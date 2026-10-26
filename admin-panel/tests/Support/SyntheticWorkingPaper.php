<?php

declare(strict_types=1);

namespace Tests\Support;

/** Deterministic camera-like paper shading/noise, never a real pupil image. */
final class SyntheticWorkingPaper
{
    public static function bytes(): string
    {
        static $bytes = null;
        if ($bytes !== null) {
            return $bytes;
        }
        $image = imagecreatetruecolor(1200, 1600);
        mt_srand(7309);
        for ($y = 0; $y < 1600; $y++) {
            for ($x = 0; $x < 1200; $x++) {
                $shade = 242 - (int) (14 * $x / 1200) + mt_rand(-9, 9);
                $red = max(0, min(255, $shade + mt_rand(-2, 2)));
                $green = max(0, min(255, $shade));
                $blue = max(0, min(255, $shade + mt_rand(-2, 2)));
                imagesetpixel($image, $x, $y, ($red << 16) | ($green << 8) | $blue);
            }
        }
        $ink = imagecolorallocate($image, 28, 36, 70);
        imagestring($image, 4, 20, 40, 'VISIBLE NAME CANARY OUTSIDE CROP', $ink);
        for ($y = 240; $y < 1450; $y += 90) {
            imagestring($image, 5, 180, $y, '1 / 2 = 0.5   Explain equal parts', $ink);
            imageline($image, 190, $y + 35, 750, $y + 35, $ink);
        }
        ob_start();
        imagepng($image, null, 9);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        mt_srand();

        return $bytes;
    }
}

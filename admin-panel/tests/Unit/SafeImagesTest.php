<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\Files\SafeImages;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeImagesTest extends TestCase
{
    public static function orientations(): array
    {
        return [
            'normal' => [1, [[0, 1, 2], [3, 4, 5]]],
            'mirror horizontal' => [2, [[2, 1, 0], [5, 4, 3]]],
            'half turn' => [3, [[5, 4, 3], [2, 1, 0]]],
            'mirror vertical' => [4, [[3, 4, 5], [0, 1, 2]]],
            'transpose' => [5, [[0, 3], [1, 4], [2, 5]]],
            'clockwise' => [6, [[3, 0], [4, 1], [5, 2]]],
            'transverse' => [7, [[5, 2], [4, 1], [3, 0]]],
            'anticlockwise' => [8, [[2, 5], [1, 4], [0, 3]]],
        ];
    }

    #[DataProvider('orientations')]
    public function test_all_exif_orientations_preserve_asymmetric_pixel_positions(int $orientation, array $expected): void
    {
        $palette = [[220, 20, 20], [20, 220, 20], [20, 20, 220], [220, 220, 20], [220, 20, 220], [20, 220, 220]];
        $source = imagecreatetruecolor(96, 64);
        foreach ($palette as $index => $rgb) {
            $x = ($index % 3) * 32;
            $y = intdiv($index, 3) * 32;
            imagefilledrectangle($source, $x, $y, $x + 31, $y + 31, imagecolorallocate($source, ...$rgb));
        }
        ob_start();
        imagejpeg($source, null, 95);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        $metadata = "Exif\0\0".hex2bin('49492a000800000001001201030001000000').pack('v', $orientation).hex2bin('000000000000');
        $bytes = substr($jpeg, 0, 2)."\xff\xe1".pack('n', strlen($metadata) + 2).$metadata.substr($jpeg, 2);
        $result = app(SafeImages::class)->sanitize(UploadedFile::fake()->createWithContent('synthetic-grid.jpg', $bytes));
        foreach (['bytes', 'thumbnail'] as $variant) {
            $image = imagecreatefromstring($result[$variant]);
            try {
                $this->assertSame(count($expected[0]) * 32, imagesx($image));
                $this->assertSame(count($expected) * 32, imagesy($image));
                foreach ($expected as $y => $row) {
                    foreach ($row as $x => $colour) {
                        $pixel = imagecolorsforindex($image, imagecolorat($image, $x * 32 + 16, $y * 32 + 16));
                        foreach (['red', 'green', 'blue'] as $channel => $name) {
                            $this->assertLessThan(20, abs($palette[$colour][$channel] - $pixel[$name]), "$variant orientation $orientation pixel $x,$y $name");
                        }
                    }
                }
            } finally {
                imagedestroy($image);
            }
        }
    }
}

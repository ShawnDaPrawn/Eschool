<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCompatibilityTest extends TestCase
{
    public function test_compression_stores_a_decodable_jpeg_with_a_matching_extension(): void
    {
        Storage::fake('public');

        $path = ImageService::compression(UploadedFile::fake()->image('photo.png', 12, 8), 'images');

        $this->assertStringEndsWith('.jpg', $path);
        $image = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame([12, 8, IMAGETYPE_JPEG], array_slice($image, 0, 3));
        $this->assertTrue(ImageService::delete($path));
        Storage::disk('public')->assertMissing($path);
    }

    public function test_resize_helper_reencodes_uploaded_images_in_place_and_preserves_their_format(): void
    {
        foreach (['photo.jpg' => IMAGETYPE_JPEG, 'photo.png' => IMAGETYPE_PNG] as $name => $type) {
            $upload = UploadedFile::fake()->image($name, 12, 8);

            $result = resizeImage($upload);

            $this->assertSame(12, $result->width());
            $this->assertSame(8, $result->height());
            $image = getimagesize($upload->getPathname());
            $this->assertSame([12, 8, $type], array_slice($image, 0, 3));
        }
    }
}

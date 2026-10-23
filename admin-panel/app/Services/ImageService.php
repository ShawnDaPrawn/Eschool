<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageService
{
    public static function compression($requestImage, $folder)
    {
        $file_name = uniqid('', true).time().'.jpg';
        $manager = new ImageManager(new Driver);
        $image = $manager->decodeSplFileInfo($requestImage);
        $encodedImage = $image->encodeUsingFormat(Format::JPEG, quality: 60);
        Storage::disk('public')->put($folder.'/'.$file_name, (string) $encodedImage);

        return $folder.'/'.$file_name;
    }

    public static function delete($image)
    {
        if (Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->delete($image);
        }

        // Image does not exist in server so feel free to upload new image
        return true;
    }
}

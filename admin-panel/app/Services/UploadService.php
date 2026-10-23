<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

/**
 * Stores an upload on the public disk under the school's own folder:
 * schools/{id}/{folder}/{random name}. It used to read a users.school_id that
 * does not exist and so put everything under super-admin/.
 *
 * Paths saved before (super-admin/... or a module folder) stay readable and
 * deletable: callers keep whatever path they stored, and delete() takes any
 * path on the disk.
 */
class UploadService
{
    /** Pictures re-encoded as JPEG (quality 60) to save space. */
    private const COMPRESSED = ['jpg', 'jpeg', 'png'];

    public static function upload(UploadedFile $requestFile, string $folder): string
    {
        $folder = app(CurrentSchool::class)->storagePath($folder);
        $extension = self::extension($requestFile);

        if (in_array($extension, self::COMPRESSED, true)) {
            $file_name = self::randomName('jpg');
            $image = (new ImageManager(new Driver))->decodeSplFileInfo($requestFile);
            Storage::disk('public')->put($folder.'/'.$file_name, (string) $image->encodeUsingFormat(Format::JPEG, quality: 60));
        } else {
            $file_name = self::randomName($extension);
            $requestFile->storeAs($folder, $file_name, 'public');
        }

        return $folder.'/'.$file_name;
    }

    /**
     * @param  string|null  $image  the stored path (rawOriginal)
     */
    public static function delete($image): bool
    {
        if ($image && Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->delete($image);
        }

        // Image does not exist in server so feel free to upload new image
        return true;
    }

    /** The extension of the sanitised client name, or of the content when that one is odd. */
    private static function extension(UploadedFile $file): string
    {
        $extension = strtolower(pathinfo(FileNames::forUpload($file), PATHINFO_EXTENSION));
        if (preg_match('/^[a-z0-9]{1,10}$/', $extension) !== 1) {
            $extension = strtolower((string) $file->guessExtension());
        }

        return $extension;
    }

    private static function randomName(string $extension): string
    {
        return Str::random(40).($extension === '' ? '' : '.'.$extension);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GalleryPhoto;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Throwable;

class GalleryPhotos
{
    public const DISK = 'gallery_private';

    public function __construct(private UploadScanner $scanner, private SafeImages $images) {}

    private function scan(string $bytes): string
    {
        try {
            $result = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Photo safety checks are temporarily unavailable. Keep your original privately and try again later.');
        }
        abort_unless(($result['clean'] ?? null) === true, 422, 'This photo did not pass its safety checks.');
        abort_unless(is_string($result['engine'] ?? null) && strlen($result['engine']) > 0 && strlen($result['engine']) <= 120, 503);

        return $result['engine'];
    }

    public function upload(User $actor, array $input, UploadedFile $file): GalleryPhoto
    {
        $a = app(GalleryAuthority::class);
        $a->actor($actor, true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'scope' => ['required', 'in:family,group'], 'learner_id' => ['required_if:scope,family', 'nullable', 'integer', 'min:1'],
            'activity_group_id' => ['required_if:scope,group', 'nullable', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'captured_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'caption' => ['nullable', 'string', 'max:2000']])->validate();
        $year = SessionYear::findOrFail($d['session_year_id']);
        abort_unless($year->start_date !== null && $year->end_date !== null && $d['captured_on'] >= substr((string) $year->start_date, 0, 10) && $d['captured_on'] <= substr((string) $year->end_date, 0, 10), 422, 'Choose the school year covering this photograph.');
        $authority = function () use ($actor, $d, $a): void {
            if ($d['scope'] === 'family') {
                $learner = $a->family($actor, (int) $d['learner_id'], true);
                abort_unless(app(GalleryQueries::class)->currentLearner($learner), 404);
            } else {
                $group = $a->group($actor, (int) $d['activity_group_id'], true);
                abort_unless((int) $group->session_year_id === (int) $d['session_year_id'], 422);
                app(CommunityAuthority::class)->groupStaff($actor, (int) $group->id, true, $d['captured_on']);
                abort_unless($d['captured_on'] >= $group->starts_on->toDateString() && $d['captured_on'] <= $group->ends_on->toDateString(), 422);
            }
        };
        $authority();
        Validator::make(['photo' => $file], SafeImages::rules())->validate();
        $source = $file->getContent();
        $d['kind'] = 'photo.upload';
        $d['source_hash'] = hash_hmac('sha256', $source, (string) config('app.key'));
        $this->scan($source);
        $image = $this->images->sanitize($file);
        $engine = $this->scan($image['bytes']);
        $this->scan($image['thumbnail']);
        unset($source);
        $paths = [];
        try {
            $result = app(GalleryOperations::class)->once($actor, $d, $authority, function () use ($actor, $d, $year, $image, $engine, &$paths): array {
                $uuid = (string) Str::uuid();
                $prefix = app(CurrentSchool::class)->storagePath('gallery/'.$uuid);
                $path = $prefix.'/photo.enc';
                $thumbnail = $prefix.'/thumbnail.enc';
                $paths = [$path, $thumbnail];
                $disk = Storage::disk(self::DISK);
                if (! $disk->put($path, Crypt::encryptString($image['bytes'])) || ! $disk->put($thumbnail, Crypt::encryptString($image['thumbnail']))) {
                    throw new RuntimeException('Private photo storage is temporarily unavailable.');
                }
                $row = GalleryPhoto::create(['public_id' => $uuid, 'session_year_id' => $year->id, 'activity_group_id' => $d['scope'] === 'group' ? $d['activity_group_id'] : null,
                    'owner_learner_id' => $d['scope'] === 'family' ? $d['learner_id'] : null, 'uploaded_by' => $actor->id, 'scope' => $d['scope'], 'caption' => $d['caption'] ?? null,
                    'captured_on' => $d['captured_on'], 'year_label' => $year->name, 'path' => $path, 'thumbnail_path' => $thumbnail, 'bytes' => strlen($image['bytes']), 'thumbnail_bytes' => strlen($image['thumbnail']),
                    'sha256' => hash('sha256', $image['bytes']), 'thumbnail_sha256' => hash('sha256', $image['thumbnail']), 'width' => $image['width'], 'height' => $image['height'], 'scanner' => $engine, 'scanned_at' => now(), 'active' => true, 'revision' => 1]);
                app(GalleryEvents::class)->record('photo_uploaded', $actor, $row);
                if ($row->scope === 'family') {
                    app(GalleryTags::class)->propose($actor, (int) $row->id, ['operation_id' => (string) Uuid::uuid5($d['operation_id'], 'family-tag'), 'learner_id' => (int) $row->owner_learner_id]);
                }

                return ['id' => (int) $row->id];
            });
        } catch (Throwable $error) {
            foreach ($paths as $path) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        }

        return GalleryPhoto::findOrFail($result['id']);
    }

    /** Immutable clean pixels; no original filenames or caller-supplied paths. */
    public function bytes(User $actor, int $id, bool $thumbnail = false): string
    {
        $a = app(GalleryAuthority::class);
        $photo = $a->photo($actor, $id);
        $bytes = $this->read($photo, $thumbnail);
        $fresh = $a->photo($actor, $id);
        abort_unless((int) $photo->revision === (int) $fresh->revision, 409, 'This photo changed while opening it.');

        return $bytes;
    }

    /** Internal explicit-consent transfer adapter; never an arbitrary public file reader. */
    public function read(GalleryPhoto $photo, bool $thumbnail = false): string
    {
        abort_unless((int) $photo->school_id === app(CurrentSchool::class)->id() && $photo->active, 404);
        $path = $thumbnail ? $photo->thumbnail_path : $photo->path;
        abort_unless(str_starts_with($path, rtrim(app(CurrentSchool::class)->storagePath('gallery'), '/').'/') && ! str_contains($path, '..'), 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($path));
        $expected = $thumbnail ? $photo->thumbnail_sha256 : $photo->sha256;
        $size = $thumbnail ? $photo->thumbnail_bytes : $photo->bytes;
        if (strlen($bytes) !== (int) $size || ! hash_equals($expected, hash('sha256', $bytes))) {
            throw new RuntimeException('This private photo is unavailable.');
        }

        return $bytes;
    }

    public function withdraw(User $actor, int $id, array $input): GalleryPhoto
    {
        $a = app(GalleryAuthority::class);
        $a->actor($actor, true);
        $photo = GalleryPhoto::select(['id', 'scope', 'activity_group_id', 'owner_learner_id'])->findOrFail($id);
        $authority = function () use ($actor, $photo, $a): void {
            if ($photo->scope === 'group') {
                $a->group($actor, (int) $photo->activity_group_id, true);
            } else {
                $a->family($actor, (int) $photo->owner_learner_id, true, removal: true);
            }
        };
        $authority();
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']])->validate() + ['kind' => 'photo.withdraw', 'photo_id' => $id];
        $result = app(GalleryOperations::class)->once($actor, $d, $authority, function () use ($actor, $id, $d): array {
            $row = GalleryPhoto::lockForUpdate()->findOrFail($id);
            abort_unless((int) $row->revision === (int) $d['revision'], 409, 'Compare the current photograph.');
            $row->update(['active' => false, 'revision' => $row->revision + 1]);
            app(GalleryEvents::class)->record('photo_withdrawn', $actor, $row);

            return ['id' => (int) $row->id];
        });

        return GalleryPhoto::findOrFail($result['id']);
    }
}

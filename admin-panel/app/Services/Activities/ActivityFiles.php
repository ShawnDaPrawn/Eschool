<?php

declare(strict_types=1);

namespace App\Services\Activities;

use App\Models\Activities\ActivityMaterial;
use App\Models\Activities\ActivityMaterialFile;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ActivityFiles
{
    public const DISK = 'activity_private';

    public function __construct(private CommunityAuthority $access, private UploadScanner $scanner, private SafeImages $images) {}

    public function authorize(User $actor, int $group, int $id, bool $edit = false, bool $write = false): ActivityMaterial
    {
        $fresh = $this->access->actor($actor);
        abort_if($fresh->password_change_required, 403, 'Complete your password change before opening private documents.');
        if ($edit) {
            $row = $this->access->groupStaff($actor, $group, $write);
            if ($write) {
                abort_unless($row->active && (int) $row->session_year_id === (int) schoolSetting('session_year'), 423, 'New attachments require an active current-year activity.');
            }
        } else {
            $this->access->read($actor, $group);
        }
        $material = ActivityMaterial::where('activity_group_id', $group)->findOrFail($id);
        if ($edit) {
            abort_unless((int) $material->author_id === (int) $actor->id || $this->access->manages($actor), 404);
        } else {
            abort_unless($material->published || $this->access->canStaff($actor, $group), 404);
        }

        return $material;
    }

    private function canEdit(User $actor, int $group, int $material, bool $write): bool
    {
        try {
            $this->authorize($actor, $group, $material, true, $write);

            return true;
        } catch (ModelNotFoundException) {
            return false;
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404, 423], true)) {
                throw $error;
            }

            return false;
        }
    }

    private function scan(string $bytes): string
    {
        try {
            $result = $this->scanner->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Document safety checks are temporarily unavailable.');
        }
        abort_unless(($result['clean'] ?? false) === true, 422, 'This document did not pass its safety checks.');
        abort_unless(is_string($result['engine'] ?? null) && strlen($result['engine']) > 0 && strlen($result['engine']) <= 120, 503);

        return $result['engine'];
    }

    private function metadata(ActivityMaterialFile $file): array
    {
        return ['id' => $file->id, 'activity_group_id' => (int) $file->activity_group_id, 'material_id' => (int) $file->material_id,
            'name' => $file->name, 'mime' => $file->mime, 'bytes' => $file->bytes, 'revision' => $file->revision, 'removed' => $file->removed_at !== null];
    }

    public function listing(User $actor, int $group, int $material): array
    {
        $row = $this->authorize($actor, $group, $material);
        $items = ActivityMaterialFile::where('activity_group_id', $group)->where('material_id', $material)->whereNull('removed_at')->orderBy('created_at')->orderBy('id')->limit(11)->get();
        abort_if($items->count() > 10, 409, 'This material needs a smaller attachment set.');
        $this->authorize($actor, $group, $material);

        return ['items' => $items->map(fn ($file) => $this->metadata($file))->all(), 'material_revision' => (int) $row->revision,
            'can_upload' => $this->canEdit($actor, $group, $material, true), 'can_remove' => $this->canEdit($actor, $group, $material, false)];
    }

    public function upload(User $actor, int $group, int $material, array $input, UploadedFile $document): array
    {
        $this->authorize($actor, $group, $material, true, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'material_revision' => ['required', 'integer', 'min:1']])->validate();
        Validator::make(['document' => $document], ['document' => ['required', 'file', 'mimes:pdf,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120']])->validate();
        $source = $document->getContent();
        $data += ['activity_group_id' => $group, 'material_id' => $material, 'source_hash' => hash_hmac('sha256', $source, (string) config('app.key')), 'name' => FileNames::forUpload($document)];
        $engine = $this->scan($source);
        $bytes = $document->getMimeType() === 'application/pdf' ? $source : $this->images->sanitize($document)['bytes'];
        $mime = $document->getMimeType() === 'application/pdf' ? 'application/pdf' : 'image/jpeg';
        $engine = $this->scan($bytes);
        $path = null;
        try {
            return app(ActivityOperations::class)->once($actor, 'material.file.upload:'.$material, $data,
                fn () => $this->authorize($actor, $group, $material, true, true),
                function () use ($actor, $group, $material, $data, $bytes, $mime, $engine, &$path): array {
                    $row = $this->authorize($actor, $group, $material, true, true);
                    abort_unless((int) $row->revision === (int) $data['material_revision'], 409, 'Refresh the material before attaching a document.');
                    abort_if(ActivityMaterialFile::where('material_id', $material)->whereNull('removed_at')->count() >= 10, 422, 'A material can have at most ten attachments.');
                    $uuid = (string) Str::uuid();
                    $path = app(CurrentSchool::class)->storagePath('activity-materials/'.$group).'/'.$uuid.'.enc';
                    if (! Storage::disk(self::DISK)->put($path, Crypt::encryptString($bytes))) {
                        throw new RuntimeException('Private document storage is unavailable.');
                    }
                    $name = $mime === 'image/jpeg' ? pathinfo($data['name'], PATHINFO_FILENAME).'.jpg' : $data['name'];
                    $file = ActivityMaterialFile::create(['id' => $uuid, 'activity_group_id' => $group, 'material_id' => $material, 'uploaded_by' => $actor->id,
                        'name' => FileNames::sanitize($name), 'mime' => $mime, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes), 'path' => $path,
                        'scanner' => $engine, 'scanned_at' => now()->utc(), 'revision' => 1]);
                    $row->increment('revision');
                    $this->authorize($actor, $group, $material, true, true);
                    Audit::record('activity.file_uploaded', $row, [], ['file_id' => $uuid, 'revision' => $row->revision]);

                    return $this->metadata($file) + ['material_revision' => (int) $row->revision];
                });
        } catch (Throwable $error) {
            if (is_string($path)) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        }
    }

    public function read(User $actor, int $group, int $material, string $id): array
    {
        $this->authorize($actor, $group, $material);
        $file = ActivityMaterialFile::where('activity_group_id', $group)->where('material_id', $material)->whereNull('removed_at')->findOrFail($id);
        $expected = app(CurrentSchool::class)->storagePath('activity-materials/'.$group).'/'.$file->id.'.enc';
        abort_unless(hash_equals($expected, $file->path), 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($expected));
        abort_unless(strlen($bytes) === $file->bytes && hash_equals($file->sha256, hash('sha256', $bytes)), 503, 'This private document is unavailable.');
        $this->authorize($actor, $group, $material);
        abort_unless(ActivityMaterialFile::where('activity_group_id', $group)->where('material_id', $material)->whereNull('removed_at')->whereKey($id)->where('revision', $file->revision)->exists(), 404);

        return ['bytes' => $bytes, 'mime' => $file->mime, 'name' => $file->name, 'id' => $file->id];
    }

    public function remove(User $actor, int $group, int $material, string $id, array $input): array
    {
        $this->authorize($actor, $group, $material, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'material_revision' => ['required', 'integer', 'min:1']])->validate()
            + ['activity_group_id' => $group, 'material_id' => $material, 'file_id' => $id];

        return app(ActivityOperations::class)->once($actor, 'material.file.remove:'.$id, $data,
            fn () => $this->authorize($actor, $group, $material, true), function () use ($actor, $group, $material, $id, $data): array {
                $row = $this->authorize($actor, $group, $material, true);
                $file = ActivityMaterialFile::where('activity_group_id', $group)->where('material_id', $material)->whereNull('removed_at')->findOrFail($id);
                abort_unless($file->revision === (int) $data['revision'] && (int) $row->revision === (int) $data['material_revision'], 409, 'Refresh the material before removing this document.');
                $file->forceFill(['removed_at' => now()->utc(), 'revision' => $file->revision + 1])->save();
                $row->increment('revision');
                DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($file->path));
                Audit::record('activity.file_removed', $row, [], ['file_id' => $id, 'revision' => $row->revision]);

                return $this->metadata($file) + ['material_revision' => (int) $row->revision];
            });
    }

    /** Trusted maintenance only: unreachable ciphertext after an outer rollback. */
    public function pruneOrphans(int $limit = 100): int
    {
        $prefix = app(CurrentSchool::class)->storagePath('activity-materials');
        $disk = Storage::disk(self::DISK);
        $removed = 0;
        foreach ($disk->allFiles($prefix) as $path) {
            if ($removed >= max(1, min(1000, $limit))) {
                break;
            }
            if (! str_ends_with($path, '.enc') || $disk->lastModified($path) > now()->utc()->subDay()->timestamp || ActivityMaterialFile::where('path', $path)->whereNull('removed_at')->exists()) {
                continue;
            }
            $removed += $disk->delete($path) ? 1 : 0;
        }

        return $removed;
    }
}

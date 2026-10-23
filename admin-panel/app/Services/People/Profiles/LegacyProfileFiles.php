<?php

declare(strict_types=1);

namespace App\Services\People\Profiles;

use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFile;
use App\Models\User;
use App\Services\Platform\Files\FileNames;
use App\Services\Platform\Tenancy\CurrentSchool;
use finfo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Operator-only source validation; no network fetches or source deletion. */
class LegacyProfileFiles
{
    /** Returns a fixed issue code or validated bytes and metadata. */
    public function prepare(mixed $path, string $profile, ?int $ownerId = null): array|string
    {
        $folder = ['learner' => 'students', 'parent' => 'parents', 'teacher' => 'teachers'][$profile] ?? null;
        if (! is_string($path) || $folder === null || $path === '' || strlen($path) > 1024
            || preg_match('/[\\\\:%?#\x00-\x1F\x7F]/', $path) || str_starts_with($path, '/')) {
            return 'invalid_legacy_file_path';
        }
        $parts = explode('/', $path);
        if (array_intersect($parts, ['', '.', '..']) !== []) {
            return 'invalid_legacy_file_path';
        }
        $schoolId = app(CurrentSchool::class)->id();
        // ParentsController historically stored parent documents in students/.
        // Permit that alias only with a verified source owner, never as a
        // general relaxation of the profile-folder boundary.
        $folders = $profile === 'parent' && $ownerId !== null ? [$folder, 'students'] : [$folder];
        $matched = false;
        foreach ($folders as $candidateFolder) {
            if (str_starts_with($path, 'schools/'.$schoolId.'/'.$candidateFolder.'/')
                || ($schoolId === 1 && str_starts_with($path, $candidateFolder.'/'))) {
                $matched = true;
            }
        }
        if (! $matched) {
            return 'legacy_file_school_mismatch';
        }
        if ($ownerId !== null && ! $this->unambiguousOwner($path, $profile, $ownerId, $schoolId)) {
            return 'legacy_file_owner_ambiguous';
        }
        if (config('filesystems.disks.public.driver') !== 'local') {
            return 'legacy_file_disk_unsupported';
        }
        $disk = Storage::disk('public');
        $root = rtrim($disk->path(''), DIRECTORY_SEPARATOR);
        $candidate = $root;
        if (is_link($root)) {
            return 'legacy_file_symlink';
        }
        foreach ($parts as $part) {
            $candidate .= DIRECTORY_SEPARATOR.$part;
            if (is_link($candidate)) {
                return 'legacy_file_symlink';
            }
        }
        $resolvedRoot = realpath($root);
        $resolved = realpath($candidate);
        if ($resolvedRoot === false || $resolved === false || ! is_file($resolved) || ! is_readable($resolved)
            || ! str_starts_with($resolved, $resolvedRoot.DIRECTORY_SEPARATOR)) {
            return 'legacy_file_unavailable';
        }
        $maximum = ProfileFiles::MAX_KB * 1024;
        $stream = @fopen($resolved, 'rb');
        if ($stream === false) {
            return 'legacy_file_unavailable';
        }
        try {
            $stat = fstat($stream);
            if ($stat === false || $stat['size'] < 1 || $stat['size'] > $maximum) {
                return 'legacy_file_size_invalid';
            }
            $bytes = stream_get_contents($stream, $maximum + 1);
            if ($bytes === false || strlen($bytes) !== $stat['size'] || strlen($bytes) > $maximum) {
                return 'legacy_file_size_invalid';
            }
        } finally {
            fclose($stream);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $allowed = ['application/pdf' => ['pdf'], 'image/png' => ['png'], 'image/jpeg' => ['jpg', 'jpeg']];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! isset($allowed[$mime]) || ! in_array($extension, $allowed[$mime], true)) {
            return 'legacy_file_type_invalid';
        }

        return ['bytes' => $bytes, 'mime' => $mime, 'extension' => $extension === 'jpeg' ? 'jpg' : $extension,
            'name' => FileNames::sanitize(basename($path)), 'size' => strlen($bytes)];
    }

    /** Confirm the exact source claim; refuse another person/school's claim. */
    private function unambiguousOwner(string $path, string $profile, int $ownerId, int $schoolId): bool
    {
        $table = ['learner' => 'students', 'parent' => 'parents', 'teacher' => 'teachers'][$profile];
        // JSON_SEARCH handles both flat and nested legacy maps and escaped
        // slashes. '=' escapes wildcard characters for an exact value match.
        $search = str_replace(['=', '_', '%'], ['==', '=_', '=%'], $path);
        $predicate = "JSON_SEARCH(IF(JSON_VALID(dynamic_fields), dynamic_fields, '[]'), 'one', ?, '=') IS NOT NULL";
        if (! DB::table($table)->where('school_id', $schoolId)->where('user_id', $ownerId)->whereRaw($predicate, [$search])->exists()) {
            return false;
        }
        // Deliberately inspect reference existence across schools, not values
        // or people. Archived claims count too: they must not be reassigned.
        foreach (['students', 'parents', 'teachers'] as $sourceTable) {
            if (DB::table($sourceTable)->where(fn ($query) => $query->where('school_id', '!=', $schoolId)->orWhere('user_id', '!=', $ownerId)->orWhereNull('user_id'))
                ->whereRaw($predicate, [$search])->exists()) {
                return false;
            }
        }

        return true;
    }

    /** Caller holds the person/definition locks and owns the outer transaction. */
    public function copy(User $person, ProfileFieldDefinition $field, array $prepared): ProfileFile
    {
        $school = app(CurrentSchool::class);
        if ((int) $field->school_id !== $school->id() || $field->type !== 'file' || ! $field->encrypted) {
            throw new RuntimeException('The legacy file mapping is unavailable.');
        }
        $id = (string) Str::uuid();
        $path = $school->storagePath('profile-files').'/'.$id.'.enc';
        try {
            if (! Storage::disk(ProfileFiles::DISK)->put($path, Crypt::encryptString($prepared['bytes']))) {
                throw new RuntimeException('The private copy could not be stored.');
            }

            return ProfileFile::query()->create(['id' => $id, 'user_id' => $person->id, 'definition_id' => $field->id,
                'path' => $path, 'name' => $prepared['name'], 'mime' => $prepared['mime'],
                'extension' => $prepared['extension'], 'size' => $prepared['size']]);
        } catch (Throwable $error) {
            Storage::disk(ProfileFiles::DISK)->delete($path);
            throw $error;
        }
    }
}

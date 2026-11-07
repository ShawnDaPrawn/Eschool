<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\PracticeAudioAnswer;
use App\Models\Learning\PracticeDiagram;
use App\Models\Library\LibraryDiagram;
use App\Models\User;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Private files of practice questions (Action Plan 3.11): diagram pictures
 * (a school's own, or the platform library's) and learners' recorded spoken
 * answers. Every file is virus-scanned, size- and type-limited, encrypted at
 * rest and stored under its owner's private path: a school's under its own
 * school folder, the library's in the library's own disk. Nothing here
 * decides who may read a file; callers authorize first.
 */
class PracticeMedia
{
    public const SCHOOL_DISK = 'academic_private';

    public const LIBRARY_DISK = 'library_private';

    /** About three minutes of compressed speech. */
    public const AUDIO_MAX_BYTES = 3145728;

    /** Recordings are deleted this many days after they are made, marked or not. */
    public const AUDIO_KEEP_DAYS = 90;

    /** ...or this many days after the teacher marks them, whichever comes first. */
    public const AUDIO_KEEP_AFTER_MARKING_DAYS = 30;

    /** Detected content type => the type a recording is stored and served as. */
    public const AUDIO_TYPES = ['audio/mp4' => 'audio/mp4', 'audio/x-m4a' => 'audio/mp4', 'video/mp4' => 'audio/mp4', 'audio/aac' => 'audio/aac', 'audio/x-hx-aac-adts' => 'audio/aac',
        'audio/webm' => 'audio/webm', 'video/webm' => 'audio/webm', 'audio/ogg' => 'audio/ogg', 'application/ogg' => 'audio/ogg', 'audio/mpeg' => 'audio/mpeg'];

    /** @return array<string, list<mixed>> */
    public static function audioRules(): array
    {
        return ['audio' => ['required', 'file', 'max:'.intdiv(self::AUDIO_MAX_BYTES, 1024)]];
    }

    /** @return array<string, list<mixed>> */
    public static function diagramRules(string $field = 'photo'): array
    {
        return [$field => SafeImages::rules()['photo']];
    }

    /** A school's diagram picture: pixels only (SafeImages), scanned, encrypted in the school's private folder. */
    public function storeSchoolDiagram(User $actor, UploadedFile $file): PracticeDiagram
    {
        $image = $this->image($file);
        $id = (string) Str::uuid();
        $path = app(CurrentSchool::class)->storagePath('practice-diagrams').'/'.$id.'.enc';
        $this->put(self::SCHOOL_DISK, $path, $image['bytes']);
        try {
            return PracticeDiagram::create(['id' => $id, 'uploaded_by' => $actor->id, 'path' => $path, 'sha256' => hash('sha256', $image['bytes']),
                'bytes' => strlen($image['bytes']), 'width' => $image['width'], 'height' => $image['height']]);
        } catch (Throwable $error) {
            Storage::disk(self::SCHOOL_DISK)->delete($path);
            throw $error;
        }
    }

    /** A library diagram picture, written from /saas only (LibraryDiagram refuses inside a school). */
    public function storeLibraryDiagram(User $actor, UploadedFile $file): LibraryDiagram
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403);
        $image = $this->image($file);
        $id = (string) Str::uuid();
        $path = 'diagrams/'.$id.'.enc';
        $this->put(self::LIBRARY_DISK, $path, $image['bytes']);
        try {
            return LibraryDiagram::create(['id' => $id, 'uploaded_by' => $actor->id, 'path' => $path, 'sha256' => hash('sha256', $image['bytes']),
                'bytes' => strlen($image['bytes']), 'width' => $image['width'], 'height' => $image['height']]);
        } catch (Throwable $error) {
            Storage::disk(self::LIBRARY_DISK)->delete($path);
            throw $error;
        }
    }

    /**
     * A labelling question names its school diagram picture by id; its size
     * comes from the stored picture, found only inside the current school.
     */
    public function withSchoolDiagram(array $definition): array
    {
        if (($definition['kind'] ?? null) !== 'diagram_label') {
            return $definition;
        }
        $id = is_array($definition['diagram'] ?? null) ? ($definition['diagram']['id'] ?? null) : null;
        $row = is_string($id) && Str::isUuid($id) ? PracticeDiagram::query()->find($id) : null;
        if ($row === null) {
            throw new \InvalidArgumentException('Upload the diagram picture to this school first.');
        }

        return ['diagram' => ['source' => 'school', 'id' => (string) $row->id, 'width' => (int) $row->width, 'height' => (int) $row->height]] + $definition;
    }

    /** The same for a library question's picture, from /saas. */
    public function withLibraryDiagram(array $definition): array
    {
        if (($definition['kind'] ?? null) !== 'diagram_label') {
            return $definition;
        }
        $id = is_array($definition['diagram'] ?? null) ? ($definition['diagram']['id'] ?? null) : null;
        $row = is_string($id) && Str::isUuid($id) ? LibraryDiagram::query()->find($id) : null;
        if ($row === null) {
            throw new \InvalidArgumentException('Add the diagram picture.');
        }

        return ['diagram' => ['source' => 'library', 'id' => (string) $row->id, 'width' => (int) $row->width, 'height' => (int) $row->height]] + $definition;
    }

    /**
     * The JPEG bytes of a definition's diagram. A school diagram is found only
     * inside the current school (BelongsToSchool), a library one only through
     * its fixed library path.
     */
    public function diagram(array $image): string
    {
        if ($image['source'] === 'library') {
            $row = LibraryDiagram::query()->findOrFail($image['id']);
            abort_unless($row->path === 'diagrams/'.$row->id.'.enc', 404);

            return $this->get(self::LIBRARY_DISK, $row->path);
        }
        $row = PracticeDiagram::query()->findOrFail($image['id']);
        abort_unless($row->path === app(CurrentSchool::class)->storagePath('practice-diagrams').'/'.$row->id.'.enc', 404);

        return $this->get(self::SCHOOL_DISK, $row->path);
    }

    /**
     * Check and keep a recorded answer's bytes: type from the content (never
     * the name), size, scanned. Returns what storeAudio needs.
     *
     * @return array{bytes: string, mime: string, sha256: string}
     */
    public function inspectAudio(UploadedFile $file): array
    {
        Validator::make(['audio' => $file], self::audioRules())->validate();
        $mime = self::AUDIO_TYPES[(string) $file->getMimeType()] ?? null;
        abort_if($mime === null, 422, __('Record the answer again: the recording type is not supported.'));
        $bytes = $file->getContent();
        abort_if($bytes === '' || strlen($bytes) > self::AUDIO_MAX_BYTES, 422, __('Record a shorter answer.'));
        $this->scan($bytes);

        return ['bytes' => $bytes, 'mime' => $mime, 'sha256' => hash('sha256', $bytes)];
    }

    /** @param  array{bytes: string, mime: string, sha256: string}  $audio */
    public function storeAudio(int $reviewId, int $learnerId, array $audio, string $today): PracticeAudioAnswer
    {
        $id = (string) Str::uuid();
        $path = app(CurrentSchool::class)->storagePath('practice-audio').'/'.$id.'.enc';
        $this->put(self::SCHOOL_DISK, $path, $audio['bytes']);
        try {
            return PracticeAudioAnswer::create(['id' => $id, 'practice_review_id' => $reviewId, 'learner_id' => $learnerId, 'path' => $path, 'mime' => $audio['mime'],
                'bytes' => strlen($audio['bytes']), 'sha256' => $audio['sha256'], 'purge_after' => CarbonImmutable::parse($today)->addDays(self::AUDIO_KEEP_DAYS)->toDateString()]);
        } catch (Throwable $error) {
            Storage::disk(self::SCHOOL_DISK)->delete($path);
            throw $error;
        }
    }

    public function audio(PracticeAudioAnswer $answer): ?string
    {
        if ($answer->purged_at !== null || $answer->path !== app(CurrentSchool::class)->storagePath('practice-audio').'/'.$answer->id.'.enc') {
            return null;
        }

        return $this->get(self::SCHOOL_DISK, $answer->path);
    }

    /** Delete the current school's recordings past their retention date. */
    public function pruneAudio(string $today, int $limit = 200): int
    {
        $count = 0;
        foreach (PracticeAudioAnswer::query()->whereNull('purged_at')->where('purge_after', '<', $today)->orderBy('purge_after')->limit($limit)->get() as $answer) {
            if ($answer->path === app(CurrentSchool::class)->storagePath('practice-audio').'/'.$answer->id.'.enc') {
                Storage::disk(self::SCHOOL_DISK)->delete($answer->path);
            }
            $answer->forceFill(['path' => null, 'purged_at' => now('UTC')])->save();
            $count++;
        }

        return $count;
    }

    /** @return array{bytes: string, width: int, height: int} */
    private function image(UploadedFile $file): array
    {
        $this->scan($file->getContent());
        $image = app(SafeImages::class)->sanitize($file);
        $this->scan($image['bytes']);

        return $image;
    }

    private function scan(string $bytes): void
    {
        try {
            $receipt = app(UploadScanner::class)->scan($bytes);
        } catch (Throwable) {
            abort(503, __('File safety checks are temporarily unavailable. Try again later.'));
        }
        abort_unless(($receipt['clean'] ?? null) === true && is_string($receipt['engine'] ?? null) && $receipt['engine'] !== '', 422, __('This file did not pass safety checks.'));
    }

    private function put(string $disk, string $path, string $bytes): void
    {
        abort_unless(Storage::disk($disk)->put($path, Crypt::encryptString($bytes)), 503);
    }

    private function get(string $disk, string $path): string
    {
        $store = Storage::disk($disk);
        abort_unless($store->exists($path), 404);

        return Crypt::decryptString($store->get($path));
    }
}

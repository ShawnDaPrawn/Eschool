<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\TeachingDraft;
use App\Models\Learning\TeachingDraftPhoto;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Ai\Inference\VisionImage;
use App\Services\Ai\Inference\VisionPhotoCropper;
use App\Services\Platform\Files\SafeImages;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class TeachingDraftPhotos
{
    public const DISK = 'academic_private';

    private function guard(User $actor, int $group, int $learner, int $school, bool $write = false): void
    {
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $access = app(TeachingDraftAccess::class);
        $row = $access->group($actor, $group, $write);
        $access->learner($actor, $row, $learner);
    }

    private function available(TeachingDraftPhoto $row): void
    {
        abort_if($row->purged_at !== null || $row->revoked_at !== null || $row->approved_at === null || $row->created_at->lte(now('UTC')->subDays(90)), 404);
        abort_if($row->created_at->lte(now('UTC')->subDays(7)) && ! TeachingDraft::where('author_id', $row->author_id)->where('operation_id', $row->operation_id)->exists(), 404);
        abort_if(AiUsage::where('feature', 'teaching_assistant')->where('user_id', $row->author_id)->where('operation_id', $row->operation_id)
            ->where(fn ($q) => $q->where('state', 'quarantined')->orWhereNotNull('quarantine_purged_at'))->exists(), 404);
    }

    private function scan(string $body): void
    {
        try {
            $result = app(UploadScanner::class)->scan($body);
        } catch (Throwable) {
            abort(503, 'Photo safety checks are temporarily unavailable.');
        }
        abort_unless(($result['clean'] ?? null) === true && is_string($result['engine'] ?? null) && $result['engine'] !== '', 422, 'This photograph did not pass the safety check.');
    }

    public function upload(User $actor, int $group, int $learner, string $operation, UploadedFile $file, array $crop, bool $approved): array
    {
        $school = app(CurrentSchool::class)->id();
        $this->guard($actor, $group, $learner, $school, true);
        Validator::make(['photo' => $file, 'operation_id' => $operation], SafeImages::rules() + ['operation_id' => ['required', 'uuid']])->validate();
        abort_if(TeachingDraft::where('operation_id', $operation)->exists(), 409, 'This teaching request has already started.');
        $original = $file->getContent();
        $digest = hash_hmac('sha256', $original.json_encode([$crop, $approved], JSON_THROW_ON_ERROR), config('app.key'));
        $find = fn () => TeachingDraftPhoto::where('author_id', $actor->id)->where('teaching_group_id', $group)->where('learner_id', $learner)
            ->where('operation_id', $operation)->where('source_digest', $digest)->first();
        $prior = $find();
        if ($prior !== null) {
            $this->available($prior);
            $this->guard($actor, $group, $learner, $school, true);

            return ['id' => $prior->id, 'sha256' => $prior->sha256, 'mime' => 'image/png', 'operation_id' => $operation];
        }
        $this->scan($original);
        $this->guard($actor, $group, $learner, $school, true);
        $image = app(VisionPhotoCropper::class)->crop((string) Str::uuid(), $original, $crop, $approved);
        $this->scan($image->bytes);
        $this->guard($actor, $group, $learner, $school, true);
        $path = null;
        try {
            return DB::transaction(function () use ($actor, $group, $learner, $operation, $crop, $digest, $image, $school, $find, &$path) {
                School::lockForUpdate()->findOrFail($school);
                $this->guard($actor, $group, $learner, $school, true);
                abort_if(TeachingDraft::where('operation_id', $operation)->exists(), 409, 'This teaching request has already started.');
                $prior = $find();
                if ($prior !== null) {
                    $this->available($prior);

                    return ['id' => $prior->id, 'sha256' => $prior->sha256, 'mime' => 'image/png', 'operation_id' => $operation];
                }
                abort_if(TeachingDraftPhoto::where('author_id', $actor->id)->where('operation_id', $operation)->count() >= 3, 422);
                abort_if(TeachingDraftPhoto::where('author_id', $actor->id)->whereNull('revoked_at')->whereNull('purged_at')->count() >= 100, 422, 'Remove an unused script crop before uploading another.');
                $path = app(CurrentSchool::class)->storagePath('teaching-draft-photos').'/'.$image->id.'.enc';
                abort_unless(Storage::disk(self::DISK)->put($path, Crypt::encryptString($image->bytes)), 503);
                $this->guard($actor, $group, $learner, $school, true);
                TeachingDraftPhoto::create(['id' => $image->id, 'author_id' => $actor->id, 'teaching_group_id' => $group, 'learner_id' => $learner,
                    'operation_id' => $operation, 'source_digest' => $digest, 'sha256' => $image->sha256, 'crop' => $crop, 'path' => $path,
                    'bytes' => strlen($image->bytes), 'approved_at' => now('UTC')]);

                return $image->reference() + ['operation_id' => $operation];
            });
        } catch (Throwable $error) {
            // Never erase a derivative whose database commit may have succeeded.
            if ($path !== null) {
                try {
                    if (! TeachingDraftPhoto::where('path', $path)->exists()) {
                        Storage::disk(self::DISK)->delete($path);
                    }
                } catch (Throwable) { /* Retain an encrypted orphan until safe reconciliation. */
                }
            }
            throw $error;
        }
    }

    public function references(User $actor, TeachingDraft $draft): array
    {
        $school = app(CurrentSchool::class)->id();
        $this->guard($actor, (int) $draft->teaching_group_id, (int) $draft->learner_id, $school);
        abort_unless((int) $draft->author_id === (int) $actor->id && $draft->purged_at === null, 404);
        $refs = [];
        foreach ($draft->request['photo_ids'] as $id) {
            $row = TeachingDraftPhoto::where('author_id', $actor->id)->where('teaching_group_id', $draft->teaching_group_id)->where('learner_id', $draft->learner_id)
                ->where('operation_id', $draft->operation_id)->findOrFail($id);
            $this->available($row);
            $refs[] = ['id' => $row->id, 'sha256' => $row->sha256, 'mime' => 'image/png'];
        }

        return $refs;
    }

    public function image(User $actor, TeachingDraft $draft, array $reference): VisionImage
    {
        abort_unless(in_array($reference, $this->references($actor, $draft), true), 404);
        $row = TeachingDraftPhoto::findOrFail($reference['id']);

        $image = $this->read($actor, $row);
        abort_unless(hash_equals($reference['sha256'], $image->sha256) && in_array($reference, $this->references($actor, $draft), true), 409);

        return $image;
    }

    private function read(User $actor, TeachingDraftPhoto $row): VisionImage
    {
        $school = app(CurrentSchool::class)->id();
        $this->guard($actor, (int) $row->teaching_group_id, (int) $row->learner_id, $school);
        abort_unless((int) $row->author_id === (int) $actor->id, 404);
        $this->available($row);
        abort_unless($row->path === app(CurrentSchool::class)->storagePath('teaching-draft-photos').'/'.$row->id.'.enc', 404);
        $body = Crypt::decryptString(Storage::disk(self::DISK)->get($row->path));
        $this->guard($actor, (int) $row->teaching_group_id, (int) $row->learner_id, $school);
        $fresh = TeachingDraftPhoto::findOrFail($row->id);
        $this->available($fresh);

        return new VisionImage($fresh->id, $fresh->sha256, $body);
    }

    public function preview(User $actor, string $id): array
    {
        $row = TeachingDraftPhoto::select('id', 'author_id', 'teaching_group_id', 'learner_id')->findOrFail($id);
        $this->guard($actor, (int) $row->teaching_group_id, (int) $row->learner_id, app(CurrentSchool::class)->id());
        abort_unless((int) $row->author_id === (int) $actor->id, 404);
        $image = $this->read($actor, TeachingDraftPhoto::findOrFail($id));

        return ['bytes' => $image->bytes, 'mime' => 'image/png'];
    }

    public function authorizeOwner(User $actor, string $id, bool $write = false): TeachingDraftPhoto
    {
        $row = TeachingDraftPhoto::select('id', 'school_id', 'author_id', 'teaching_group_id', 'learner_id')->findOrFail($id);
        $this->guard($actor, (int) $row->teaching_group_id, (int) $row->learner_id, app(CurrentSchool::class)->id(), $write);
        abort_unless((int) $row->author_id === (int) $actor->id, 404);

        return $row;
    }

    public function revoke(User $actor, string $id): void
    {
        $meta = TeachingDraftPhoto::select('id', 'author_id', 'teaching_group_id', 'learner_id')->findOrFail($id);
        $this->guard($actor, (int) $meta->teaching_group_id, (int) $meta->learner_id, app(CurrentSchool::class)->id(), true);
        abort_unless((int) $meta->author_id === (int) $actor->id, 404);
        $row = TeachingDraftPhoto::findOrFail($id);
        $row->update(['revoked_at' => now('UTC')]);
        $expected = app(CurrentSchool::class)->storagePath('teaching-draft-photos').'/'.$id.'.enc';
        if ($row->path === $expected) {
            Storage::disk(self::DISK)->delete($expected);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorPhoto;
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

class TutorPhotos
{
    public const DISK = 'academic_private';

    private function unquarantined(TutorPhoto $photo): void
    {
        abort_if(AiUsage::where('feature', 'tutor')->where('operation_id', $photo->operation_id)->where('user_id', $photo->user_id)
            ->where(fn ($query) => $query->where('state', 'quarantined')->orWhereNotNull('quarantine_purged_at'))->exists(), 404);
    }

    private function capacity(User $actor): void
    {
        $active = TutorPhoto::where('user_id', $actor->id)->whereNull('revoked_at')->whereNull('purged_at');
        abort_if((clone $active)->count() >= 100 || (clone $active)->whereNotIn('operation_id', TutorMessage::where('user_id', $actor->id)->select('operation_id'))->count() >= 20,
            422, 'Remove an unused working crop before uploading another.');
    }

    private function guard(User $actor, string $session, int $school): void
    {
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        app(TutorAccess::class)->session($actor, $session, true);
    }

    private function scan(string $bytes): void
    {
        try {
            $receipt = app(UploadScanner::class)->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Photo safety checks are temporarily unavailable.');
        }
        abort_unless(($receipt['clean'] ?? null) === true && is_string($receipt['engine'] ?? null) && strlen($receipt['engine']) > 0, 422, 'This photo did not pass safety checks.');
    }

    public function upload(User $actor, string $sessionId, string $operation, UploadedFile $file, array $crop, bool $approved): array
    {
        $school = app(CurrentSchool::class)->id();
        $this->guard($actor, $sessionId, $school);
        Validator::make(['photo' => $file, 'operation_id' => $operation], SafeImages::rules() + ['operation_id' => ['required', 'uuid']])->validate();
        abort_if(TutorMessage::where('operation_id', $operation)->exists(), 409, 'This tutor turn already started.');
        $original = $file->getContent();
        $digest = hash_hmac('sha256', $original.json_encode([$crop, $approved], JSON_THROW_ON_ERROR), config('app.key'));
        $prior = TutorPhoto::where('tutor_session_id', $sessionId)->where('user_id', $actor->id)->where('operation_id', $operation)->where('source_digest', $digest)->first();
        if ($prior !== null) {
            abort_if($prior->revoked_at !== null || $prior->purged_at !== null, 409);
            $this->guard($actor, $sessionId, $school);

            return ['id' => $prior->id, 'sha256' => $prior->sha256, 'mime' => 'image/png', 'operation_id' => $operation];
        }
        $this->capacity($actor);
        $this->scan($original);
        $this->guard($actor, $sessionId, $school);
        $image = app(VisionPhotoCropper::class)->crop((string) Str::uuid(), $original, $crop, $approved);
        $this->scan($image->bytes);
        $this->guard($actor, $sessionId, $school);
        $path = null;
        try {
            return DB::transaction(function () use ($actor, $sessionId, $operation, $crop, $digest, $image, $school, &$path) {
                School::lockForUpdate()->findOrFail($school);
                $this->guard($actor, $sessionId, $school);
                abort_if(TutorMessage::where('operation_id', $operation)->exists(), 409, 'This tutor turn already started.');
                $prior = TutorPhoto::where('tutor_session_id', $sessionId)->where('user_id', $actor->id)->where('operation_id', $operation)->where('source_digest', $digest)->first();
                if ($prior !== null) {
                    abort_if($prior->revoked_at !== null || $prior->purged_at !== null, 409);

                    return $prior->only('id', 'sha256') + ['mime' => 'image/png', 'operation_id' => $operation];
                }
                abort_if(TutorPhoto::where('tutor_session_id', $sessionId)->where('operation_id', $operation)->count() >= 3, 422, 'Use at most three approved working crops per turn.');
                $this->capacity($actor);
                $path = app(CurrentSchool::class)->storagePath('tutor-photos').'/'.$image->id.'.enc';
                abort_unless(Storage::disk(self::DISK)->put($path, Crypt::encryptString($image->bytes)), 503);
                $this->guard($actor, $sessionId, $school);
                TutorPhoto::create(['id' => $image->id, 'tutor_session_id' => $sessionId, 'user_id' => $actor->id, 'operation_id' => $operation,
                    'source_digest' => $digest, 'sha256' => $image->sha256, 'crop' => $crop, 'path' => $path, 'bytes' => strlen($image->bytes), 'approved_at' => now('UTC')]);

                return $image->reference() + ['operation_id' => $operation];
            });
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        }
    }

    public function references(User $actor, TutorMessage $message): array
    {
        app(TutorAccess::class)->session($actor, $message->tutor_session_id);
        abort_unless((int) $message->user_id === (int) $actor->id && $message->purged_at === null, 404);
        $refs = [];
        foreach ($message->media_ids as $id) {
            $photo = TutorPhoto::where('tutor_session_id', $message->tutor_session_id)->where('user_id', $actor->id)->where('operation_id', $message->operation_id)
                ->whereNotNull('approved_at')->whereNull('revoked_at')->whereNull('purged_at')->findOrFail($id);
            $refs[] = ['id' => $photo->id, 'sha256' => $photo->sha256, 'mime' => 'image/png'];
        }

        return $refs;
    }

    public function image(User $actor, TutorMessage $message, array $reference): VisionImage
    {
        $school = app(CurrentSchool::class)->id();
        abort_unless(in_array($reference, $this->references($actor, $message), true), 404);
        $photo = TutorPhoto::findOrFail($reference['id']);
        abort_unless($photo->path === app(CurrentSchool::class)->storagePath('tutor-photos').'/'.$photo->id.'.enc', 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($photo->path));
        $this->guard($actor, $message->tutor_session_id, $school);
        abort_unless(in_array($reference, $this->references($actor, $message), true), 404);

        return new VisionImage($photo->id, $reference['sha256'], $bytes);
    }

    public function revoke(User $actor, string $session, string $id): void
    {
        $school = app(CurrentSchool::class)->id();
        DB::transaction(function () use ($actor, $session, $id, $school) {
            School::lockForUpdate()->findOrFail($school);
            $this->guard($actor, $session, $school);
            $photo = TutorPhoto::where('tutor_session_id', $session)->where('user_id', $actor->id)->findOrFail($id);
            $photo->update(['revoked_at' => now('UTC')]);
            if ($photo->path === app(CurrentSchool::class)->storagePath('tutor-photos').'/'.$photo->id.'.enc') {
                Storage::disk(self::DISK)->delete($photo->path);
            }
        });
    }

    public function preview(User $actor, string $session, string $id): array
    {
        $school = app(CurrentSchool::class)->id();
        app(TutorAccess::class)->session($actor, $session);
        $photo = TutorPhoto::where('tutor_session_id', $session)->where('user_id', $actor->id)->whereNotNull('approved_at')->whereNull('revoked_at')->whereNull('purged_at')->findOrFail($id);
        $this->unquarantined($photo);
        abort_unless($photo->path === app(CurrentSchool::class)->storagePath('tutor-photos').'/'.$photo->id.'.enc', 404);
        $bytes = Crypt::decryptString(Storage::disk(self::DISK)->get($photo->path));
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        app(TutorAccess::class)->session($actor, $session);
        $fresh = TutorPhoto::where('tutor_session_id', $session)->where('user_id', $actor->id)->whereNull('revoked_at')->whereNull('purged_at')->findOrFail($id);
        $this->unquarantined($fresh);
        $image = new VisionImage($fresh->id, $fresh->sha256, $bytes);

        return ['bytes' => $image->bytes, 'mime' => 'image/png'];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\ExamAnswer;
use App\Models\Learning\ExamAnswerReview;
use App\Models\Platform\School;
use App\Models\User;
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

class ExamWorkingPhotos
{
    public const DISK = 'academic_private';

    private function scan(string $bytes): void
    {
        try {
            $receipt = app(UploadScanner::class)->scan($bytes);
        } catch (Throwable) {
            abort(503, 'Photo safety checks are temporarily unavailable.');
        }
        abort_unless(($receipt['clean'] ?? null) === true && is_string($receipt['engine'] ?? null) && $receipt['engine'] !== '', 422);
    }

    public function upload(User $actor, int $groupId, string $versionId, string $question, string $operation, UploadedFile $file, array $crop, bool $approved, bool $shareWithTeacher): array
    {
        $school = app(CurrentSchool::class)->id();
        $guard = function () use ($actor, $groupId, $versionId, $question, $school) {
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            app(ExamCoachAccess::class)->learnerGroup($actor, $groupId, true);
            $version = app(PastPaperLibrary::class)->version($actor, $groupId, $versionId);
            abort_unless(in_array($question, array_column($version->definition['questions'], 'number'), true), 422);

            return $version;
        };
        $version = $guard();
        Validator::make(['photo' => $file, 'operation_id' => $operation], SafeImages::rules() + ['operation_id' => ['required', 'uuid']])->validate();
        abort_unless($shareWithTeacher, 422, 'Confirm sharing this working with the allocated teacher.');
        $original = $file->getContent();
        $hash = hash_hmac('sha256', json_encode([$groupId, $versionId, $question, $crop, $approved, $shareWithTeacher, hash('sha256', $original)], JSON_THROW_ON_ERROR), config('app.key'));
        $old = ExamAnswer::where('user_id', $actor->id)->where('operation_id', $operation)->first();
        if ($old !== null) {
            abort_unless(hash_equals($old->upload_hash, $hash), 409);
            $old = app(ExamAnswerAccess::class)->answer($actor, $old->id);

            return ['id' => $old->id, 'operation_id' => $operation, 'state' => 'prepared'];
        }
        $this->scan($original);
        $guard();
        $image = app(VisionPhotoCropper::class)->crop((string) Str::uuid(), $original, $crop, $approved);
        $this->scan($image->bytes);
        $guard();
        $path = null;
        try {
            return DB::transaction(function () use ($actor, $groupId, $operation, $question, $version, $hash, $image, $crop, $guard, $school, &$path) {
                School::lockForUpdate()->findOrFail($school);
                $guard();
                $old = ExamAnswer::where('user_id', $actor->id)->where('operation_id', $operation)->first();
                if ($old !== null) {
                    abort_unless(hash_equals($old->upload_hash, $hash), 409);
                    app(ExamAnswerAccess::class)->answer($actor, $old->id);

                    return ['id' => $old->id, 'operation_id' => $operation, 'state' => 'prepared'];
                }
                abort_if(ExamAnswer::where('user_id', $actor->id)->whereNull('revoked_at')->whereNull('purged_at')->where('expires_at', '>', now('UTC'))->count() >= 50, 422, 'Remove an older working photo first.');
                $path = app(CurrentSchool::class)->storagePath('exam-working').'/'.$image->id.'.enc';
                abort_unless(Storage::disk(self::DISK)->put($path, Crypt::encryptString($image->bytes)), 503);
                $group = app(ExamCoachAccess::class)->learnerGroup($actor, $groupId, true);
                $learner = app(ExamCoachAccess::class)->learner($actor);
                $answer = ExamAnswer::create(['id' => $image->id, 'user_id' => $actor->id, 'learner_id' => $learner->id, 'teaching_group_id' => $groupId,
                    'subject_id' => $group->subject_id, 'session_year_id' => $group->session_year_id, 'paper_version_id' => $version->id, 'paper_fingerprint' => $version->fingerprint,
                    'question_number' => $question, 'operation_id' => $operation, 'upload_hash' => $hash, 'path' => $path, 'sha256' => $image->sha256,
                    'bytes' => strlen($image->bytes), 'crop' => $crop, 'share_with_teacher' => true, 'expires_at' => now('UTC')->addDays(7)]);
                $guard();

                return ['id' => $answer->id, 'operation_id' => $operation, 'state' => 'prepared'];
            });
        } catch (Throwable $error) {
            if ($path !== null) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $error;
        }
    }

    public function revoke(User $actor, string $id, string $operation): array
    {
        $guard = function () use ($actor, $id) {
            $person = app(ExamCoachAccess::class)->learner($actor);
            $row = ExamAnswer::where('user_id', $actor->id)->where('learner_id', $person->id)->findOrFail($id);
            // Own revocation remains possible after paper rights/source withdrawal.
            app(ExamCoachAccess::class)->learnerGroup($actor, (int) $row->teaching_group_id, true);
        };

        return app(ExamCoachOperations::class)->run($actor, 'answer.revoke', ['operation_id' => $operation, 'answer_id' => $id], $guard, function () use ($actor, $id) {
            $row = ExamAnswer::where('user_id', $actor->id)->findOrFail($id);
            $row->update(['revoked_at' => $row->revoked_at ?? now('UTC')]);
            $this->purge($row);

            return ['id' => $id, 'state' => 'purged'];
        });
    }

    public function purge(ExamAnswer $row): void
    {
        $school = app(CurrentSchool::class)->id();
        abort_unless((int) $row->getRawOriginal('school_id') === $school, 403);
        $path = $row->path;
        if ($path !== null) {
            abort_unless($path === app(CurrentSchool::class)->storagePath('exam-working').'/'.$row->id.'.enc', 404);
            $disk = Storage::disk(self::DISK);
            abort_if($disk->exists($path) && ! $disk->delete($path), 503, 'Private working deletion needs a retry.');
        }
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $row->update(['state' => 'purged', 'purged_at' => $row->purged_at ?? now('UTC'), 'path' => null, 'working' => null, 'response' => null, 'crop' => null]);
        foreach (ExamAnswerReview::where('exam_answer_id', $row->id)->get() as $review) {
            $review->update(['reason' => 'Expired private working review']);
        }
    }

    public function prune(): int
    {
        $school = app(CurrentSchool::class)->id();
        $ids = ExamAnswer::whereNull('purged_at')->where('expires_at', '<=', now('UTC'))->orderBy('id')->limit(500)->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $school) {
                School::lockForUpdate()->findOrFail($school);
                abort_unless(app(CurrentSchool::class)->id() === $school, 403);
                $row = ExamAnswer::whereNull('purged_at')->where('expires_at', '<=', now('UTC'))->find($id);
                if ($row !== null) {
                    $this->purge($row);
                }
            });
        }

        $prefix = app(CurrentSchool::class)->storagePath('exam-working');
        $disk = Storage::disk(self::DISK);
        $deleted = 0;
        $cutoff = now('UTC')->subDay()->timestamp;
        foreach (array_chunk($disk->files($prefix), 200) as $paths) {
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $candidates = [];
            foreach ($paths as $path) {
                $name = basename($path);
                if ($path === $prefix.'/'.$name && preg_match('/^([a-f0-9-]{36})\\.enc$/D', $name, $match)
                    && Str::isUuid($match[1]) && $disk->lastModified($path) < $cutoff) {
                    $candidates[$match[1]] = $path;
                }
            }
            if ($candidates === []) {
                continue;
            }
            $referenced = ExamAnswer::whereIn('id', array_keys($candidates))->whereNull('purged_at')->get(['id', 'path'])->keyBy('id');
            foreach ($candidates as $id => $path) {
                if ($referenced->get($id)?->path === $path) {
                    continue;
                }
                abort_unless(app(CurrentSchool::class)->id() === $school, 403);
                abort_unless($disk->delete($path), 503, 'Private orphan deletion needs a retry.');
                abort_unless(app(CurrentSchool::class)->id() === $school, 403);
                if (++$deleted >= 200) {
                    break 2;
                }
            }
        }

        return $ids->count();
    }
}

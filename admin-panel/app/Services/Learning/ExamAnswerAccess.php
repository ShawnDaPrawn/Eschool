<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\ExamAnswer;
use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperVersion;
use App\Models\User;
use App\Services\Ai\Inference\VisionImage;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/** A submitted working crop is distinct from school curriculum and official marks. */
class ExamAnswerAccess
{
    public function answer(User $actor, string $id, bool $staff = false, bool $write = false): ExamAnswer
    {
        $access = app(ExamCoachAccess::class);
        $access->actor($actor, $write);
        $meta = ExamAnswer::select('id', 'school_id', 'user_id', 'learner_id', 'teaching_group_id', 'share_with_teacher', 'revoked_at', 'purged_at', 'expires_at')->findOrFail($id);
        abort_unless($meta->purged_at === null && $meta->revoked_at === null && $meta->expires_at->isFuture(), 404);
        if ($staff) {
            $access->staff($actor, (int) $meta->teaching_group_id, $write);
            abort_unless($meta->share_with_teacher, 404);
            $owner = User::findOrFail($meta->user_id);
            $access->learnerGroup($owner, (int) $meta->teaching_group_id);
        } else {
            $person = $access->learner($actor);
            abort_unless((int) $meta->user_id === (int) $actor->id && (int) $meta->learner_id === (int) $person->id, 404);
            $access->learnerGroup($actor, (int) $meta->teaching_group_id, $write);
        }
        $answer = ExamAnswer::findOrFail($id);
        abort_if(AiUsage::where('feature', 'exam_coach')->where('operation_id', $answer->operation_id)->where('user_id', $answer->user_id)
            ->where(fn ($q) => $q->where('state', 'quarantined')->orWhereNotNull('quarantine_purged_at'))->exists(), 404);
        $this->question($actor, $answer, $staff);

        return $answer;
    }

    public function question(User $actor, ExamAnswer $answer, bool $staff = false): array
    {
        $version = app(PastPaperLibrary::class)->version($actor, (int) $answer->teaching_group_id, $answer->paper_version_id, $staff);
        abort_unless(hash_equals($answer->paper_fingerprint, $version->fingerprint), 409);
        // Even staff reviews stay bound to the currently published learner paper.
        $this->published($version);
        foreach ($version->definition['questions'] as $question) {
            if ($question['number'] === $answer->question_number) {
                return $question;
            }
        }
        abort(409, 'This question is no longer available.');
    }

    public function published(PastPaperVersion $version): void
    {
        abort_unless($version->state === 'published' && PastPaper::where('active', true)->where('published_version_id', $version->id)->exists(), 409);
    }

    public function reference(ExamAnswer $answer): array
    {
        return ['id' => $answer->id, 'sha256' => $answer->sha256, 'mime' => 'image/png'];
    }

    public function image(User $actor, string $id, bool $staff = false): VisionImage
    {
        $school = app(CurrentSchool::class)->id();
        $answer = $this->answer($actor, $id, $staff);
        abort_unless($answer->path === app(CurrentSchool::class)->storagePath('exam-working').'/'.$answer->id.'.enc', 404);
        $bytes = Crypt::decryptString(Storage::disk(ExamWorkingPhotos::DISK)->get($answer->path));
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $answer = $this->answer($actor, $id, $staff);

        return new VisionImage($answer->id, $answer->sha256, $bytes);
    }
}

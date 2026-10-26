<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Learning\ExamAnswer;
use App\Models\User;
use App\Services\Ai\Inference\VisionImage;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Ai\Quarantine\AiReviewAuthority;
use App\Services\Ai\Quarantine\QuarantineMediaResolver;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/** Retained approved derivative only, independent of Student/quarantined read grants. */
class ExamAnswerQuarantineMedia implements QuarantineMediaResolver
{
    private function row(User $actor, AiUsage $usage, array $ref, int $school): ExamAnswer
    {
        app(AiReviewAuthority::class)->actor($actor);
        abort_unless(app(CurrentSchool::class)->id() === $school && (int) $usage->getRawOriginal('school_id') === $school, 403);
        $usage = AiUsage::where('feature', 'exam_coach')->where('state', 'quarantined')->whereNull('quarantine_purged_at')->findOrFail($usage->id);
        abort_unless(array_keys($ref) === ['id', 'sha256', 'mime'] && $ref['mime'] === 'image/png', 404);
        $snapshots = AiQuarantineSnapshot::where('ai_usage_id', $usage->id)->where('expires_at', '>', now())->where('created_at', '>', now()->subDays(app(AiQuarantine::class)->retentionDays()))->get();
        abort_unless($snapshots->contains(fn ($snapshot) => in_array($ref, $snapshot->private_content['media'] ?? [], true)), 404);
        $row = ExamAnswer::where('operation_id', $usage->operation_id)->where('user_id', $usage->user_id)->where('learner_id', $usage->learner_id)
            ->where('expires_at', '>', now('UTC'))->whereNull('revoked_at')->whereNull('purged_at')->findOrFail($ref['id']);
        abort_unless(hash_equals($row->sha256, $ref['sha256']) && $row->path === app(CurrentSchool::class)->storagePath('exam-working').'/'.$row->id.'.enc', 404);

        return $row;
    }

    public function read(User $reviewer, AiUsage $usage, array $reference): array
    {
        $school = app(CurrentSchool::class)->id();
        $row = $this->row($reviewer, $usage, $reference, $school);
        $bytes = Crypt::decryptString(Storage::disk(ExamWorkingPhotos::DISK)->get($row->path));
        $this->row($reviewer, $usage, $reference, $school);
        $image = new VisionImage($row->id, $reference['sha256'], $bytes);

        return ['bytes' => $image->bytes, 'mime' => 'image/png'];
    }
}

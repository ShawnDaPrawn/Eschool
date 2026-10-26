<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Learning\TeachingDraft;
use App\Models\Learning\TeachingDraftPhoto;
use App\Models\User;
use App\Services\Ai\Inference\VisionImage;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Ai\Quarantine\AiReviewAuthority;
use App\Services\Ai\Quarantine\QuarantineMediaResolver;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/** Safeguarding-only retained approved crop; never the original upload. */
class TeachingDraftQuarantineMedia implements QuarantineMediaResolver
{
    private function photo(User $reviewer, AiUsage $usage, array $reference, int $school): TeachingDraftPhoto
    {
        app(AiReviewAuthority::class)->actor($reviewer);
        abort_unless(app(CurrentSchool::class)->id() === $school && (int) $usage->getRawOriginal('school_id') === $school, 403);
        $fresh = AiUsage::where('feature', 'teaching_assistant')->where('state', 'quarantined')->whereNull('quarantine_purged_at')->findOrFail($usage->id);
        abort_unless($fresh->learner_id !== null && array_keys($reference) === ['id', 'sha256', 'mime'] && $reference['mime'] === 'image/png', 404);
        $cutoff = now()->subDays(app(AiQuarantine::class)->retentionDays());
        $snapshots = AiQuarantineSnapshot::where('ai_usage_id', $fresh->id)->where('expires_at', '>', now())->where('created_at', '>', $cutoff)->get();
        abort_unless($snapshots->contains(fn ($snapshot) => in_array($reference, $snapshot->private_content['media'] ?? [], true)), 404);
        $draft = TeachingDraft::where('learner_id', $fresh->learner_id)->where('author_id', $fresh->user_id)->where('operation_id', $fresh->operation_id)->whereNull('purged_at')->firstOrFail();
        $photo = TeachingDraftPhoto::where('teaching_group_id', $draft->teaching_group_id)->where('learner_id', $fresh->learner_id)->where('author_id', $fresh->user_id)->where('operation_id', $fresh->operation_id)
            ->whereNotNull('approved_at')->whereNull('revoked_at')->whereNull('purged_at')->findOrFail($reference['id']);
        abort_unless(hash_equals($photo->sha256, $reference['sha256'])
            && $photo->path === app(CurrentSchool::class)->storagePath('teaching-draft-photos').'/'.$photo->id.'.enc', 404);

        return $photo;
    }

    public function read(User $reviewer, AiUsage $usage, array $reference): array
    {
        $school = app(CurrentSchool::class)->id();
        $photo = $this->photo($reviewer, $usage, $reference, $school);
        $bytes = Crypt::decryptString(Storage::disk(TeachingDraftPhotos::DISK)->get($photo->path));
        $this->photo($reviewer, $usage, $reference, $school);
        $image = new VisionImage($photo->id, $reference['sha256'], $bytes);

        return ['bytes' => $image->bytes, 'mime' => 'image/png'];
    }
}

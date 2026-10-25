<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorPhoto;
use App\Models\Learning\TutorSession;
use App\Services\Ai\Quarantine\QuarantineParticipant;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Storage;

class TutorQuarantineParticipant implements QuarantineParticipant
{
    public function purgeForUsage(AiUsage $usage): void
    {
        abort_unless($usage->feature === 'tutor' && (int) $usage->school_id === app(CurrentSchool::class)->id(), 403);
        $sessions = TutorSession::where('learner_id', $usage->learner_id)->where('user_id', $usage->user_id)->select('id');
        $messages = TutorMessage::whereIn('tutor_session_id', $sessions)->where('user_id', $usage->user_id)->where('operation_id', $usage->operation_id)->get();
        foreach ($messages as $message) {
            $photos = TutorPhoto::where('tutor_session_id', $message->tutor_session_id)->where('user_id', $usage->user_id)->where('operation_id', $usage->operation_id)->get();
            foreach ($photos as $photo) {
                $expected = app(CurrentSchool::class)->storagePath('tutor-photos').'/'.$photo->id.'.enc';
                abort_unless($photo->path === $expected, 404);
                Storage::disk(TutorPhotos::DISK)->delete($expected);
                $photo->update(['purged_at' => now('UTC'), 'revoked_at' => $photo->revoked_at ?? now('UTC')]);
            }
            $message->update(['learner_text' => null, 'working' => null, 'media_ids' => null, 'source_manifest' => null, 'context_text' => null,
                'response' => null, 'purged_at' => now('UTC'), 'state' => 'purged']);
        }
    }
}

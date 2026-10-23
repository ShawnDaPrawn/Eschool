<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use App\Models\Platform\LearnerInvitation;
use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Identity\LearnerInvitations;

class LearnerInvitationMail extends ViewMail implements ChecksRecipient
{
    public function __construct(public int $invitationId, array $payload, ?string $from, string $schoolName)
    {
        $recorded = new MessageRecorder;
        $recorded->to($payload['email'])->subject(html_entity_decode($payload['subject'], ENT_QUOTES));
        if ($from) {
            $recorded->from($from, $schoolName);
        }
        parent::__construct('emails.learner-invitation', $payload, $recorded);
    }

    public function mayDeliver(?NotificationDelivery $delivery): bool
    {
        if (! $delivery?->school_id || ! $delivery->recipient_user_id) {
            return false;
        }
        $invitation = LearnerInvitation::query()->where('school_id', $delivery->school_id)->where('guardian_user_id', $delivery->recipient_user_id)->find($this->invitationId);
        $address = $this->to[0]['address'] ?? '';

        return $invitation !== null && is_string($invitation->email_hash)
            && hash_equals($invitation->email_hash, hash('sha256', strtolower(trim($address))))
            && app(LearnerInvitations::class)->canDeliver($invitation);
    }
}

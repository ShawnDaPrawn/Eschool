<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use App\Models\Platform\GuardianInvitation;
use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Identity\GuardianInvitations;
use Illuminate\Mail\Mailable;

class GuardianInvitationMail extends Mailable implements ChecksRecipient
{
    public ?int $invitationId = null;

    public function __construct(string $email, public string $invitationUrl, public string $schoolName, ?int $invitationId = null)
    {
        $this->to($email);
        $this->invitationId = $invitationId;
    }

    public function build(): static
    {
        return $this->subject('Your school invitation')->view('emails.guardian-invitation');
    }

    public function mayDeliver(?NotificationDelivery $delivery): bool
    {
        if (! $this->invitationId || ! $delivery?->school_id || ! $delivery->recipient_user_id) {
            return false;
        }
        $invitation = GuardianInvitation::acrossSchools()->where('school_id', $delivery->school_id)
            ->where('guardian_user_id', $delivery->recipient_user_id)->find($this->invitationId);
        $address = $this->to[0]['address'] ?? '';

        return $invitation !== null && is_string($invitation->email_hash)
            && hash_equals($invitation->email_hash, hash('sha256', strtolower(trim($address))))
            && app(GuardianInvitations::class)->canDeliver($invitation);
    }
}

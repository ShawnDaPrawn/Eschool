<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\StaffInvitation;
use App\Services\Platform\Identity\StaffInvitations;
use Illuminate\Mail\Mailable;

/** The emailed link a staff member uses to choose their password. */
class StaffInvitationMail extends Mailable implements ChecksRecipient
{
    public ?int $invitationId = null;

    public function __construct(string $email, public string $invitationUrl, public string $schoolName, public bool $firstAdministrator = false, ?int $invitationId = null)
    {
        $this->to($email);
        $this->invitationId = $invitationId;
    }

    public function build(): static
    {
        return $this->subject(__('Your NavigateEd staff invitation'))->view('emails.staff-invitation');
    }

    public function mayDeliver(?NotificationDelivery $delivery): bool
    {
        if (! $this->invitationId || ! $delivery?->school_id || ! $delivery->recipient_user_id) {
            return false;
        }
        $invitation = StaffInvitation::acrossSchools()->where('school_id', $delivery->school_id)
            ->where('user_id', $delivery->recipient_user_id)->find($this->invitationId);
        $address = $this->to[0]['address'] ?? '';

        return $invitation !== null && hash_equals($invitation->email_hash, hash('sha256', strtolower(trim($address))))
            && app(StaffInvitations::class)->canDeliver($invitation);
    }
}

<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Saas\PlatformStaff;
use Illuminate\Mail\Mailable;

/** The emailed link an invited platform staff member uses to choose a password (SA.1). */
class PlatformStaffInvitationMail extends Mailable implements ChecksRecipient
{
    public function __construct(string $email, public string $invitationUrl, public int $invitationId)
    {
        $this->to($email);
    }

    public function build(): static
    {
        return $this->subject(__('Your NavigateEd platform invitation'))->view('emails.platform-staff-invitation');
    }

    /** Still unused, unexpired, sent to the address on the account, and the person is still staff. */
    public function mayDeliver(?NotificationDelivery $delivery): bool
    {
        return app(PlatformStaff::class)->canDeliver($this->invitationId, (string) ($this->to[0]['address'] ?? ''));
    }
}

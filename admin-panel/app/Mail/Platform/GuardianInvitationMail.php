<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use Illuminate\Mail\Mailable;

class GuardianInvitationMail extends Mailable
{
    public function __construct(string $email, public string $invitationUrl, public string $schoolName)
    {
        $this->to($email);
    }

    public function build(): static
    {
        return $this->subject('Your school invitation')->view('emails.guardian-invitation');
    }
}

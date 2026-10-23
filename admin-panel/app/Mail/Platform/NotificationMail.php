<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use Illuminate\Mail\Mailable;

class NotificationMail extends Mailable
{
    public function __construct(string $email, public string $notificationType, public string $noticeTitle, public string $noticeBody, public string $noticeUrl, public string $schoolName, public array $deliveryContext = [])
    {
        $this->to($email);
    }

    public function build(): static
    {
        return $this->subject($this->noticeTitle)->view('emails.school-notification');
    }
}

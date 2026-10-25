<?php

declare(strict_types=1);

namespace App\Mail\Admissions;

use App\Mail\Platform\ChecksRecipient;
use App\Models\Platform\NotificationDelivery;
use App\Services\Admissions\ApplicationLinks;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Mail\Mailable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ResumeApplicationMail extends Mailable implements ChecksRecipient
{
    public function __construct(string $address, public string $resumeUrl, private int $linkId, private int $schoolId)
    {
        $this->to($address);
    }

    public function build(): static
    {
        return $this->subject('Continue your school application')->view('emails.admissions.resume');
    }

    public function mayDeliver(?NotificationDelivery $delivery): bool
    {
        if ($delivery === null || (int) $delivery->school_id !== $this->schoolId) {
            return false;
        }
        try {
            return app(ApplicationLinks::class)->deliverable($this->linkId, $this->schoolId, (string) ($this->to[0]['address'] ?? ''));
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}

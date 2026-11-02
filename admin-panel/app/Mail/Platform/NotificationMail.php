<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use Illuminate\Mail\Mailable;
use Symfony\Component\Mime\Email;

class NotificationMail extends Mailable
{
    /** Set at delivery for a notice the recipient may switch off (RFC 8058 one-click unsubscribe). */
    public ?string $unsubscribeUrl = null;

    /**
     * The school's name, crest and colour, set at delivery (Action Plan 2.1).
     *
     * @var array{name?: string, crest_url?: ?string, colour?: string}
     */
    public array $brand = [];

    /** The full content (sanitised HTML and its plain text), set at delivery only for types that allow it. */
    public ?string $contentHtml = null;

    public ?string $contentText = null;

    public function __construct(string $email, public string $notificationType, public string $noticeTitle, public string $noticeBody, public string $noticeUrl, public string $schoolName, public array $deliveryContext = [])
    {
        $this->to($email);
    }

    public function build(): static
    {
        if ($this->unsubscribeUrl !== null) {
            $url = $this->unsubscribeUrl;
            $this->withSymfonyMessage(function (Email $message) use ($url): void {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$url.'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
        }

        return $this->subject($this->noticeTitle)->view('emails.school-notification')->text('emails.school-notification-text');
    }
}

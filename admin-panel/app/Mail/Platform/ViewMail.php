<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use Illuminate\Mail\Mailable;

/**
 * A Blade view sent as mail, with the addresses and subject a legacy
 * MailService::send() callback set (see MessageRecorder). Used for the mails
 * that have no editable template (contact-form replies, for example).
 */
class ViewMail extends Mailable
{
    /** @var list<array{data: string, name: string, mime: string|null}> base64 contents */
    public array $files = [];

    /**
     * @param  array<string, mixed>  $mailData
     */
    public function __construct(public string $viewName, public array $mailData, MessageRecorder $recorded)
    {
        foreach ($recorded->calls as [$method, $arguments]) {
            $this->{$method}(...$arguments);
        }
        $this->files = $recorded->attachments;
    }

    public function build(): static
    {
        $this->view($this->viewName, $this->mailData);
        // build() also runs for the check before queueing; attach only once.
        $this->rawAttachments = [];
        foreach ($this->files as $file) {
            $this->attachData(base64_decode($file['data']), $file['name'], array_filter(['mime' => $file['mime']]));
        }

        return $this;
    }

    public function deliveryType(): string
    {
        return 'mail.'.$this->viewName;
    }
}

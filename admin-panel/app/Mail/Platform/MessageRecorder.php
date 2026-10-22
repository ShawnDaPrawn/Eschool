<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use BadMethodCallException;

/**
 * Stands in for Illuminate\Mail\Message while a legacy MailService callback
 * runs, so the callback's addresses, subject and attachments can be queued
 * with the mail instead of the callback itself (closures are not queued).
 *
 * Only what the callers use is supported; anything else throws
 * BadMethodCallException and MailService sends that mail as before, at once.
 */
class MessageRecorder
{
    /** @var list<array{0: string, 1: array<int, mixed>}> */
    public array $calls = [];

    /** @var list<array{data: string, name: string, mime: string|null}> */
    public array $attachments = [];

    public function to($address, $name = null): static
    {
        return $this->record('to', [$address, $name]);
    }

    public function cc($address, $name = null): static
    {
        return $this->record('cc', [$address, $name]);
    }

    public function bcc($address, $name = null): static
    {
        return $this->record('bcc', [$address, $name]);
    }

    public function replyTo($address, $name = null): static
    {
        return $this->record('replyTo', [$address, $name]);
    }

    public function from($address, $name = null): static
    {
        return $this->record('from', [$address, $name]);
    }

    public function subject($subject): static
    {
        return $this->record('subject', [(string) $subject]);
    }

    public function priority($level): static
    {
        return $this->record('priority', [(int) $level]);
    }

    /**
     * Uploaded files are temporary and gone once the request ends, so their
     * contents travel with the queued mail.
     *
     * @param  array{as?: string, mime?: string}  $options
     */
    public function attach($file, array $options = []): static
    {
        $path = (string) $file;
        $contents = is_readable($path) ? file_get_contents($path) : false;
        if ($contents === false) {
            throw new BadMethodCallException('Attachment is not readable');
        }
        $this->attachments[] = [
            'data' => base64_encode($contents),
            'name' => (string) ($options['as'] ?? basename($path)),
            'mime' => isset($options['mime']) ? (string) $options['mime'] : null,
        ];

        return $this;
    }

    public function attachData($data, $name, array $options = []): static
    {
        $this->attachments[] = [
            'data' => base64_encode((string) $data),
            'name' => (string) $name,
            'mime' => isset($options['mime']) ? (string) $options['mime'] : null,
        ];

        return $this;
    }

    public function __call(string $method, array $parameters): never
    {
        throw new BadMethodCallException("Mail callback method {$method}() cannot be queued");
    }

    private function record(string $method, array $arguments): static
    {
        $this->calls[] = [$method, $arguments];

        return $this;
    }
}

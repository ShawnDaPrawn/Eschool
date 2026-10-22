<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Mail\Mailable;

/**
 * A mail built from a template schools edit in the panel (email_templates,
 * System Settings > Email Template). The template is read and filled through
 * EmailTemplateService when the mail is built, and the result is printed by
 * the same thin Blade wrapper as before (students.email, staff.email,
 * teacher.email), so subjects and bodies are unchanged.
 *
 * $data holds the placeholder values plus 'email' (the recipient) and
 * 'subject'; it is also the view's data, as it was.
 */
class TemplateMail extends Mailable
{
    /**
     * @param  array<string, mixed>  $data
     * @param  string|null  $fallback  template text to use when the stored template is empty
     */
    public function __construct(
        public string $templateType,
        public string $viewName,
        public array $data,
        public ?string $fallback = null,
    ) {
        $this->to((string) ($data['email'] ?? ''));
        $this->subject((string) ($data['subject'] ?? ''));
    }

    /** The parent welcome mail with logins (admission, accepted registration, import). */
    public static function parentWelcome(array $data, ?string $fallback = null): self
    {
        return new self('student_registration', 'students.email', $data, $fallback);
    }

    /** The reply to a rejected online registration. */
    public static function registrationRejected(array $data): self
    {
        return new self('student_application_regect', 'students.email', $data);
    }

    /** The staff or teacher welcome mail with logins. */
    public static function staffWelcome(array $data, string $view = 'staff.email'): self
    {
        return new self('staff_registration', $view, $data);
    }

    public function build(): static
    {
        $template = (string) EmailTemplate::where('type', $this->templateType)->value('content');
        if ($template === '' && $this->fallback !== null) {
            $template = $this->fallback;
        }

        return $this->view($this->viewName, ['body' => EmailTemplateService::render($template, $this->data)] + $this->data);
    }

    public function deliveryType(): string
    {
        return 'mail.'.$this->templateType;
    }
}

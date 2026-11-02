<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Communication\Message;
use App\Models\User;
use App\Policies\Communication\MessagePolicy;
use App\Services\Academics\Content\ContentMarkdown;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * What a school notice email shows beyond its notice (Action Plan 2.1): the
 * school's branding, and for the few types that allow it, the full content.
 *
 * Full content is opt-in per type (`full_content` in
 * config/notification_types.php; today only school messages) and never for a
 * `notice_only` type (safeguarding, wellbeing, discipline, progress and the
 * other private notices). It is read at sending, for that recipient, through
 * the same access check the app's reader uses, so a withdrawn message or a
 * recipient who lost access gets the notice only. The content never travels
 * in the queued job. engineering/specs/notifications/branded-email.md.
 */
class EmailContent
{
    /** The colour used when a school has not chosen one (or chose something that is not a colour). */
    public const DEFAULT_COLOUR = '#1F4E79';

    /**
     * @return array{name: string, crest_url: ?string, colour: string}
     */
    public function branding(): array
    {
        $school = app(CurrentSchool::class)->isSet() ? app(CurrentSchool::class)->school() : null;

        return [
            'name' => (string) ($school?->name ?? config('app.name')),
            'crest_url' => $school?->crestUrl(),
            'colour' => self::colour($school?->theme_color),
        ];
    }

    public static function colour(?string $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^#[0-9A-Fa-f]{6}$/D', $value) === 1 ? strtoupper($value) : self::DEFAULT_COLOUR;
    }

    public static function noticeOnly(string $type): bool
    {
        return (bool) (self::definition($type)['notice_only'] ?? false);
    }

    public static function allowsFullContent(string $type): bool
    {
        return ! self::noticeOnly($type) && (bool) (self::definition($type)['full_content'] ?? false);
    }

    /**
     * The full content of this notice for this recipient, or null for the
     * notice only. Call it in the notice's school, just before sending.
     *
     * @return array{title: string, html: string, text: string}|null
     */
    public function full(User $recipient, string $type, array $context): ?array
    {
        if ($recipient->email_verified_at === null || ! self::allowsFullContent($type) || $type !== 'communication.message') {
            return null;
        }
        try {
            $message = app(MessagePolicy::class)->reader($recipient, (int) ($context['communication_message_id'] ?? 0));
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }

            return null;
        }
        // Only the publication this notice announced: a later edit or withdrawal is not sent.
        if (! $message instanceof Message || ! is_string($context['communication_publication_id'] ?? null) || ! hash_equals((string) $message->publication_id, $context['communication_publication_id'])) {
            return null;
        }
        $html = app(ContentMarkdown::class)->html((string) $message->markdown);

        // The sender's subject carries meaning too ("School closed tomorrow"): it becomes the email's subject and heading.
        return ['title' => (string) $message->title, 'html' => $html, 'text' => self::plain($html)];
    }

    /** The plain-text alternative of sanitised message HTML: paragraphs, list items and link addresses kept. */
    public static function plain(string $html): string
    {
        $text = (string) preg_replace('/<a\s[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is', '$2 ($1)', $html);
        $text = (string) preg_replace('/<li[^>]*>/i', '- ', $text);
        $text = (string) preg_replace('/<br\s*\/?>|<\/(p|h[1-6]|li|tr|blockquote|pre|ul|ol|table)>/i', "\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace("/[ \t]+\n/", "\n", $text);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /** Type names contain dots, so they are looked up in the array, not as config paths. */
    private static function definition(string $type): array
    {
        $definition = config('notification_types', [])[$type] ?? null;

        return is_array($definition) ? $definition : [];
    }
}

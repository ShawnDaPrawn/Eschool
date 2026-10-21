<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PolicyAcceptance;
use App\Models\User;

/**
 * What a parent or teacher accepts once, on their first sign-in.
 *
 * NavigateEd's own terms come first. The school's privacy notice and terms,
 * edited under General Settings with the branding, come after, and only when
 * the school has already written at least one of them. Opening the app later
 * does not ask again, and editing the pages does not ask again.
 */
class ParentPolicyService
{
    public const NAVIGATEED = 'navigateed';

    public const SCHOOL = 'school';

    public const NAVIGATEED_BODY = <<<'TEXT'
NavigateEd is the app Tokensoft provides to your school. Accepting this means:

You will keep your login private.
The school chooses which records, messages, photos and fees you can see.
NavigateEd stores that information so the app can show it to you.
You can stop using the app at any time by signing out.

Your school's own privacy notice and terms, if the school has written them, come next. Those are the school's words, not NavigateEd's.
TEXT;

    public function status(User $user): array
    {
        [$privacy, $terms] = $this->schoolDocuments();
        $schoolVersion = $this->schoolVersion($privacy, $terms);
        $navigateedVersion = $this->navigateEdVersion();

        return [
            'school_name' => (string) (getSettings('school_name')['school_name'] ?? ''),
            'navigateed' => [
                'required' => true,
                'accepted' => $this->accepted($user, self::NAVIGATEED),
                'version' => $navigateedVersion,
                'title' => 'NavigateEd',
                'body' => self::NAVIGATEED_BODY,
            ],
            'school' => [
                'required' => $schoolVersion !== null,
                'accepted' => $schoolVersion === null || $this->accepted($user, self::SCHOOL),
                'version' => $schoolVersion,
                'privacy_policy' => $this->hasContent($privacy) ? $privacy : '',
                'terms' => $this->hasContent($terms) ? $terms : '',
            ],
        ];
    }

    public function needsAcceptance(User $user): bool
    {
        $status = $this->status($user);

        return ! $status['navigateed']['accepted']
            || ($status['school']['required'] && ! $status['school']['accepted']);
    }

    /**
     * @return array{ok: bool, message: string, status: array}
     */
    public function accept(User $user, string $document, string $version): array
    {
        $status = $this->status($user);

        if (! in_array($document, [self::NAVIGATEED, self::SCHOOL], true)) {
            return ['ok' => false, 'message' => 'Choose NavigateEd or the school.', 'status' => $status];
        }

        if ($document === self::SCHOOL && ! $status['navigateed']['accepted']) {
            return ['ok' => false, 'message' => 'Accept NavigateEd before the school terms.', 'status' => $status];
        }

        if ($document === self::SCHOOL && ! $status['school']['required']) {
            return ['ok' => false, 'message' => 'The school has not published a privacy notice or terms.', 'status' => $status];
        }

        $current = $document === self::NAVIGATEED
            ? $status['navigateed']['version']
            : $status['school']['version'];

        if (! is_string($current) || ! hash_equals($current, $version)) {
            return ['ok' => false, 'message' => 'These terms have changed. Please read them again.', 'status' => $status];
        }

        $this->record($user, $document, $version);
        // The school had nothing to show on this first sign-in. Remember that,
        // so a page written later does not stop them on the next visit.
        if ($document === self::NAVIGATEED && ! $status['school']['required']) {
            $this->record($user, self::SCHOOL, hash('sha256', ''));
        }

        return [
            'ok' => true,
            'message' => 'Accepted.',
            'status' => $this->status($user),
        ];
    }

    /** Record acceptance of whatever is required right now. Used by the test school. */
    public static function acceptCurrent(User $user): void
    {
        $service = app(self::class);
        $status = $service->status($user);
        $service->accept($user, self::NAVIGATEED, $status['navigateed']['version']);
        if ($status['school']['required']) {
            $service->accept($user, self::SCHOOL, (string) $status['school']['version']);
        }
    }

    public function navigateEdVersion(): string
    {
        return hash('sha256', self::NAVIGATEED_BODY);
    }

    private function accepted(User $user, string $document): bool
    {
        return PolicyAcceptance::query()
            ->where('user_id', $user->id)
            ->where('document', $document)
            ->exists();
    }

    private function record(User $user, string $document, string $version): void
    {
        PolicyAcceptance::query()->updateOrCreate(
            ['user_id' => $user->id, 'document' => $document],
            ['version' => $version, 'accepted_at' => now()],
        );
    }

    /** @return array{0: string, 1: string} */
    private function schoolDocuments(): array
    {
        $settings = getSettings();

        return [
            (string) ($settings['privacy_policy'] ?? ''),
            (string) ($settings['terms_condition'] ?? ''),
        ];
    }

    private function schoolVersion(string $privacy, string $terms): ?string
    {
        if (! $this->hasContent($privacy) && ! $this->hasContent($terms)) {
            return null;
        }

        return hash('sha256', $privacy."\n---\n".$terms);
    }

    private function hasContent(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        if (trim(preg_replace('/\s+/', ' ', $text) ?? '') !== '') {
            return true;
        }

        return (bool) preg_match('/<img\b/i', $html);
    }
}

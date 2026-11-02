<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

/**
 * How a push looks on the device (Action Plan 2.9): its buttons, the sender
 * of a message, and the generic text private notices show on a lock screen.
 * Specification: engineering/specs/platform/rich-push.md.
 *
 * Every button only opens the app, where the person sees the notice and
 * confirms. No button carries a URL or performs a write from the
 * notification, so a tap on a lock screen can never consent, reply or RSVP
 * on someone's behalf.
 */
class PushPresentation
{
    /** The button ids a push may carry. Ids only, never URLs. */
    public const ACTIONS = ['reply', 'acknowledge', 'consent_yes', 'consent_no', 'rsvp_yes', 'rsvp_no'];

    /** iOS notification category => its buttons, in order. The apps register these. */
    public const CATEGORIES = [
        'reply' => ['reply'],
        'acknowledge' => ['acknowledge'],
        'consent' => ['consent_yes', 'consent_no'],
        'rsvp' => ['rsvp_yes', 'rsvp_no'],
    ];

    /** Message types whose push names its sender. */
    public const SENDER_TYPES = ['comms.message', 'communication.message'];

    /**
     * The type's buttons from config/notification_types.php push_actions,
     * keeping only known ids. A list with no matching category gets none.
     *
     * @return list<string>
     */
    public static function actions(string $type): array
    {
        $definition = config('notification_types', [])[NotificationTypes::legacy($type)] ?? null;
        $actions = is_array($definition) ? array_values(array_filter((array) ($definition['push_actions'] ?? []), fn ($id) => in_array($id, self::ACTIONS, true))) : [];

        return self::category($actions) === null ? [] : $actions;
    }

    /** @param  list<string>  $actions */
    public static function category(array $actions): ?string
    {
        $found = array_search($actions, self::CATEGORIES, true);

        return $found === false ? null : (string) $found;
    }

    /**
     * Private notices (a private_notices.scopes prefix, or notice_only on the
     * type) never show their content on a lock screen.
     */
    public static function private(string $type): bool
    {
        foreach ([$type, NotificationTypes::legacy($type)] as $candidate) {
            foreach (array_keys((array) config('private_notices.scopes', [])) as $prefix) {
                if (is_string($prefix) && $prefix !== '' && str_starts_with($candidate, $prefix)) {
                    return true;
                }
            }
            if ((config('notification_types', [])[$candidate]['notice_only'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /** @return array{title: string, body: string} */
    public static function genericText(): array
    {
        return ['title' => __('School notification'), 'body' => __('Open NavigateEd to view this notice.')];
    }

    /**
     * The sender's name and picture from the producer's sender_info, when it
     * has them. The picture is the profile photo URL the chat already sends in
     * sender_info; nothing new is published. Anything that is not an absolute
     * http(s) URL to a file is sent as empty.
     *
     * @return array{sender_name: string, sender_avatar: string}
     */
    public static function sender(array $data): array
    {
        $info = $data['sender_info'] ?? null;
        if (is_string($info)) {
            $info = json_decode($info, true);
        }
        if (is_object($info)) {
            $info = (array) $info;
        }
        if (! is_array($info)) {
            return ['sender_name' => '', 'sender_avatar' => ''];
        }
        $name = trim(implode(' ', array_filter([$info['first_name'] ?? '', $info['last_name'] ?? ''], fn ($part) => is_string($part) && $part !== '')));
        $avatar = is_string($info['image'] ?? null) ? trim($info['image']) : '';
        $parts = parse_url($avatar);
        $valid = $avatar !== '' && strlen($avatar) <= 2048 && is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ($parts['host'] ?? '') !== '' && ! isset($parts['user']) && ! isset($parts['pass']) && ! preg_match('/[\s\x00-\x1F\x7F]/', $avatar)
            && ! str_ends_with($parts['path'] ?? '/', '/');

        return ['sender_name' => mb_substr($name, 0, 128), 'sender_avatar' => $valid ? $avatar : ''];
    }
}

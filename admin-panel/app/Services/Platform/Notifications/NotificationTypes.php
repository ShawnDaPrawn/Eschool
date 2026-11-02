<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use RuntimeException;

class NotificationTypes
{
    /**
     * Private (a private_notices scope prefix) and notice-only types only say
     * that something is waiting: their excerpt is the generic message and
     * they never store a body.
     */
    public static function isPrivate(string $type): bool
    {
        foreach (array_keys((array) config('private_notices.scopes', [])) as $prefix) {
            if (is_string($prefix) && $prefix !== '' && str_starts_with($type, $prefix)) {
                return true;
            }
        }
        $definition = config('notification_types', [])[$type] ?? null;

        return is_array($definition) && ($definition['notice_only'] ?? false) === true;
    }

    /** Links and targets are app paths: never a foreign origin or an open redirect in a trusted school message. */
    public static function assertAppPath(string $path): string
    {
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\r\n]/', $path)) {
            throw new RuntimeException('A notification link must be an app path.');
        }

        return $path;
    }

    public static function legacy(string $type): string
    {
        if (array_key_exists($type, config('notification_types', []))) {
            return $type;
        }

        return match (strtolower($type)) {
            'chat' => 'comms.message',
            'fees-due' => 'fees.reminder',
            'online', 'payment', 'fees' => 'fees.receipt',
            'attendance' => 'attendance.absence',
            'assignment' => 'academics.assignment_published',
            'assignment_submission' => 'academics.submission',
            'lesson', 'topic' => 'academics.content_published',
            default => 'comms.notice',
        };
    }

    public static function link(string $type): string
    {
        return match ($type) {
            'consent.requested' => '/family-forms',
            'approval.requested' => '/approvals',
            'approval.handle_requested' => '/handles',
            'comms.message' => '/chat',
            default => '/notifications',
        };
    }
}

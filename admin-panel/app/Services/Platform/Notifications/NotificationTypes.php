<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

class NotificationTypes
{
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

<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Models\Notification;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Notifications\NotificationTypes;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class FanoutNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $schoolId, public int $notificationId, public ?array $userIds = null) {}

    public function handle(CurrentSchool $schools, Notifier $notifier): void
    {
        $schools->run($this->schoolId, function () use ($notifier) {
            $notice = Notification::query()->findOrFail($this->notificationId);
            $members = SchoolMembership::query()->active()->where('school_id', $this->schoolId)->select('user_id');
            User::query()->whereIn('id', $members)->when($this->userIds !== null, fn ($query) => $query->whereIn('id', $this->userIds))
                ->where('status', 1)->orderBy('id')->chunkById(100, function ($users) use ($notice, $notifier) {
                    foreach ($users as $user) {
                        $notifier->send($user, NotificationTypes::legacy($notice->type), $notice->delivery_payload['title'] ?? $notice->title, $notice->delivery_payload['body'] ?? $notice->message, $notice->delivery_payload['link'] ?? NotificationTypes::link(NotificationTypes::legacy($notice->type)), $notice);
                    }
                });
        });
    }
}

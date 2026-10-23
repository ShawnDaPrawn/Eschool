<?php

declare(strict_types=1);

namespace App\Listeners\Platform;

use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\HandleProposal;
use App\Models\Platform\OutboxEvent;
use App\Models\User;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;

class NotifyHandleGuardians implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            $proposal = HandleProposal::query()->where('status', 'pending')->where('expires_at', '>', now())->find($event->payload['proposal_id']);
            if (! $proposal) {
                return;
            }
            $guardians = GuardianLink::query()->where('learner_id', $proposal->learner_id)->where('status', 'active')->where('can_approve', true)->select('guardian_user_id');
            $users = User::query()->whereIn('id', $guardians)->whereNotNull('email_verified_at')->get();
            if ($users->isEmpty()) {
                return;
            }
            $key = 'handle.proposal.'.$proposal->id;
            $notifier = app(Notifier::class);
            $notice = Notification::query()->where('source_key', $key)->first();
            if (! $notice) {
                $notice = $notifier->makeNotice('approval.handle_requested', __('Handle approval requested'), __('A learner has asked you to review a handle. Open NavigateEd to decide.'), '/handles');
                $notice->forceFill(['source_key' => $key, 'delivery_payload' => ['link' => '/handles', 'handle_proposal_id' => (int) $proposal->id]])->save();
            }
            foreach ($users as $user) {
                $notifier->send($user, 'approval.handle_requested', $notice->title, $notice->message, '/handles', $notice);
            }
        });
    }
}

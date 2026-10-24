<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\HandleProposal;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;

/** Recheck mutable access and the destination immediately before transport. */
class DeliveryGuard
{
    public function allows(?NotificationDelivery $delivery, string $type, string $channel, string $address, array $context = []): bool
    {
        if (! $delivery?->school_id || ! $delivery->recipient_user_id || ! $delivery->recipient_hash
            || ! hash_equals($delivery->recipient_hash, DeliveryLog::hash($address))) {
            return false;
        }

        return app(CurrentSchool::class)->run($delivery->school_id, function () use ($delivery, $type, $channel, $address, $context) {
            $user = User::query()->find($delivery->recipient_user_id);
            if (! $user || (int) $user->status !== 1 || app(CurrentSchool::class)->school()?->status !== 'active'
                || ! SchoolMembership::query()->active()->where('school_id', $delivery->school_id)->where('user_id', $user->id)->exists()) {
                return false;
            }
            if (isset($context['handle_proposal_id'])) {
                $proposal = HandleProposal::query()->where('status', 'pending')->where('expires_at', '>', now())->find($context['handle_proposal_id']);
                if (! $proposal || ! $user->email_verified_at || ! GuardianLink::query()->where('learner_id', $proposal->learner_id)
                    ->where('guardian_user_id', $user->id)->where('status', 'active')->where('can_approve', true)->exists()) {
                    return false;
                }
            }
            if (isset($context['register_learner_id'])) {
                $learner = Students::find($context['register_learner_id']);
                if ($learner === null || ! app(GuardianService::class)->guardianUserIds($learner)->contains((int) $user->id)) {
                    return false;
                }
            }
            $currentAddress = $channel === 'email' ? $user->email : $user->fcm_id;

            return is_string($currentAddress) && hash_equals($currentAddress, $address)
                && app(NotificationRules::class)->allows($user, $type, $channel);
        });
    }
}

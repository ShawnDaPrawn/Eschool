<?php

declare(strict_types=1);

namespace App\Services\Platform\Notifications;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\ReportCard;
use App\Models\Leave;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\HandleProposal;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\Attendance\RegisterRosters;
use App\Services\Communication\MessageDelivery;
use App\Services\Growth\ProgressNotices;
use App\Services\Pastoral\CoverNotices;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferNotices;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            if (isset($context['register_entry_id'])) {
                $entry = AttendanceEntry::with('register', 'learner')->find($context['register_entry_id']);
                $kind = $context['register_notice_kind'] ?? null;
                if ($entry === null || $entry->register === null || (int) $entry->learner_id !== (int) ($context['register_learner_id'] ?? 0)
                    || $entry->revision !== (int) ($context['register_entry_revision'] ?? 0)
                    || ! in_array($kind, ['absence', 'amended'], true)
                    || $type !== ($kind === 'absence' ? 'attendance.absence' : 'academics.register_amended')
                    || ! app(RegisterRosters::class)->learners($entry->register)->contains('id', $entry->learner_id)) {
                    return false;
                }
                if ($kind === 'absence' && ($entry->register->timetable_lesson_id !== null || $entry->state !== 'absent'
                    || isset(Leave::approvedTypesForDate($entry->register->on_date->toDateString())[$entry->learner->user_id]))) {
                    return false;
                }
            }
            if (str_starts_with($type, 'communication.') && ! app(MessageDelivery::class)->allows($user, $type, $context)) {
                return false;
            }
            if (isset($context['content_version_id'])) {
                if ($type !== 'academics.content_published' || ! isset($context['content_learner_id'])) {
                    return false;
                }
                try {
                    app(ContentPolicy::class)->reader($user, (int) $context['content_learner_id'], (int) $context['content_version_id']);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    return false;
                }
            }
            if (isset($context['report_card_id'])) {
                $card = ReportCard::with('batch')->find($context['report_card_id']);
                $learner = $card === null ? null : Students::find($card->learner_id);
                if ($type !== 'academics.report_published' || $card === null || $learner === null || $card->batch?->state !== 'released'
                    || $card->generated_fingerprint !== $card->batch->fingerprint || (int) $card->learner_id !== (int) ($context['report_learner_id'] ?? 0)
                    || ((int) $learner->user_id !== (int) $user->id && ! app(GuardianService::class)->guardianUserIds($learner)->contains((int) $user->id))) {
                    return false;
                }
            }
            if (! app(PrivateNoticeScopes::class)->permitsDelivery($user, $type, $context)) {
                return false;
            }
            if (str_starts_with($type, 'transfer.') && ! app(TransferNotices::class)->allows($user, $type, $context)) {
                return false;
            }
            if (str_starts_with($type, 'progress.') && ! app(ProgressNotices::class)->allows($user, $type, $context)) {
                return false;
            }
            if (str_starts_with($type, 'pastoral.cover') && ! app(CoverNotices::class)->allows($user, $type, $context)) {
                return false;
            }
            $current = $channel === 'email'
                ? is_string($user->email) && hash_equals($user->email, $address)
                : app(PushDevices::class)->isCurrent($user, $address);

            return $current && app(NotificationRules::class)->allows($user, $type, $channel);
        });
    }
}

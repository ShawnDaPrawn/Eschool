<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\ProgressBandAlert;
use App\Models\Growth\ProgressBandState;
use App\Models\Parents;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;

/** Source authorization precedes notice-body retrieval, pagination and counts. */
class ProgressNoticeScope implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        // Inspect only this recipient's metadata, never another recipient's notice body.
        $links = UserNotification::where('user_id', $actor->id)->select('notification_id');
        $allowed = [ProgressNotices::STAFF_TYPE => [], ProgressNotices::TYPE => []];
        ProgressBandAlert::whereIn('notification_id', $links)->whereNotNull('notification_id')
            ->whereExists(ProgressBandState::whereColumn('progress_band_states.id', 'progress_band_alerts.state_id')
                ->whereColumn('progress_band_states.revision', 'progress_band_alerts.state_revision')->whereColumn('progress_band_states.band', 'progress_band_alerts.band')->selectRaw('1')->toBase())
            ->select(['id', 'state_id', 'notification_id'])->chunkById(100, function ($rows) use ($actor, &$allowed) {
                foreach ($rows as $alert) {
                    $state = ProgressBandState::select('id', 'audience')->find($alert->state_id);
                    if ($state === null || ! in_array($state->audience, ['staff', 'family'], true)) {
                        continue;
                    }
                    $type = $state->audience === 'staff' ? ProgressNotices::STAFF_TYPE : ProgressNotices::TYPE;
                    $payload = ['progress_alert_id' => (int) $alert->id, 'link' => $state->audience === 'staff' ? '/learner-notes' : '/growth/progress'];
                    if ($this->allows($actor, $type, $payload)) {
                        $allowed[$type][] = (int) $alert->notification_id;
                    }
                }
            });
        $notifications->where(function (Builder $types) use ($notifications, $allowed, $actor) {
            foreach ($allowed as $type => $ids) {
                $types->orWhere(function (Builder $source) use ($notifications, $type, $ids, $actor) {
                    $payload = $notifications->qualifyColumn('delivery_payload');
                    $safe = "IF(JSON_VALID($payload), $payload, '{}')";
                    $reference = ProgressBandAlert::whereColumn('notification_id', $notifications->qualifyColumn('id'))
                        ->whereRaw("CAST(progress_band_alerts.id AS CHAR) = JSON_UNQUOTE(JSON_EXTRACT($safe, '$.progress_alert_id'))")
                        ->whereExists($this->currentSource($actor, $type)->toBase())->selectRaw('1');
                    $source->where($notifications->qualifyColumn('type'), $type)->whereIn($notifications->qualifyColumn('id'), $ids)
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = ?", [$type === ProgressNotices::STAFF_TYPE ? '/learner-notes' : '/growth/progress'])
                        ->whereExists($reference->toBase());
                });
            }
        });
    }

    /** Live SQL ownership survives revocation after a caller constructs its query. */
    private function currentSource(User $actor, string $type): Builder
    {
        $school = app(CurrentSchool::class)->id();
        $today = app(AcademicAuthority::class)->today();
        $state = ProgressBandState::whereColumn('progress_band_states.id', 'progress_band_alerts.state_id')
            ->whereColumn('progress_band_states.revision', 'progress_band_alerts.state_revision')
            ->whereColumn('progress_band_states.band', 'progress_band_alerts.band')->selectRaw('1')
            ->whereExists(fn ($member) => $member->selectRaw('1')->from('school_memberships as notice_actor_membership')->join('schools as notice_school', 'notice_school.id', '=', 'notice_actor_membership.school_id')
                ->join('users as notice_actor', 'notice_actor.id', '=', 'notice_actor_membership.user_id')
                ->where('notice_actor_membership.school_id', $school)->where('notice_actor_membership.user_id', $actor->id)->where('notice_actor_membership.status', 'active')
                ->where(fn ($start) => $start->whereNull('notice_actor_membership.starts_on')->orWhereDate('notice_actor_membership.starts_on', '<=', $today))
                ->where(fn ($end) => $end->whereNull('notice_actor_membership.ends_on')->orWhereDate('notice_actor_membership.ends_on', '>=', $today))
                ->where('notice_school.status', School::ACTIVE)->where('notice_actor.status', 1)->whereNull('notice_actor.deleted_at')->where('notice_actor.password_change_required', false))
            ->whereNotExists(fn ($platform) => $platform->selectRaw('1')->from('platform_staff_identities')->where('platform_staff_identities.user_id', $actor->id)->where('platform_staff_identities.active', true));
        if ($type === ProgressNotices::STAFF_TYPE) {
            $state->where('audience', 'staff')->whereExists(function ($q) use ($actor, $school, $today) {
                $q->selectRaw('1')->from('teaching_groups as notice_group')
                    ->whereColumn('notice_group.id', 'progress_band_alerts.group_id')->where('notice_group.school_id', $school)
                    ->whereNull('notice_group.deleted_at')->where('notice_group.active', true)
                    ->whereColumn('notice_group.subject_id', 'progress_band_states.subject_id')->whereColumn('notice_group.grade_level_id', 'progress_band_states.grade_level_id')
                    ->whereColumn('notice_group.session_year_id', 'progress_band_states.session_year_id')
                    ->whereExists(fn ($members) => $members->selectRaw('1')->from('teaching_group_memberships as notice_member')
                        ->where('notice_member.school_id', $school)->whereNull('notice_member.deleted_at')
                        ->whereColumn('notice_member.teaching_group_id', 'notice_group.id')->whereColumn('notice_member.learner_id', 'progress_band_states.learner_id')
                        ->whereDate('notice_member.starts_on', '<=', $today)->whereDate('notice_member.ends_on', '>=', $today))
                    ->where(function ($authority) use ($actor, $school, $today) {
                        $authority->whereExists(function ($allocation) use ($actor, $school, $today) {
                            $allocation->selectRaw('1')->from('teaching_allocations as notice_allocation')->join('teachers as notice_teacher', 'notice_teacher.id', '=', 'notice_allocation.teacher_id')
                                ->where('notice_allocation.school_id', $school)->where('notice_teacher.school_id', $school)->where('notice_teacher.user_id', $actor->id)
                                ->whereNull('notice_allocation.deleted_at')->whereNull('notice_teacher.deleted_at')
                                ->whereColumn('notice_allocation.teaching_group_id', 'notice_group.id')->whereDate('notice_allocation.starts_on', '<=', $today)->whereDate('notice_allocation.ends_on', '>=', $today)
                                ->whereExists($actor->roles()->where('roles.name', 'Teacher')->getQuery()->selectRaw('1')->toBase());
                        })->orWhereExists(function ($lead) use ($actor, $school, $today) {
                            $lead->selectRaw('1')->from('academic_leaderships as notice_lead')->where('notice_lead.school_id', $school)->where('notice_lead.user_id', $actor->id)
                                ->whereNull('notice_lead.deleted_at')->whereColumn('notice_lead.session_year_id', 'notice_group.session_year_id')
                                ->whereDate('notice_lead.starts_on', '<=', $today)->whereDate('notice_lead.ends_on', '>=', $today)
                                ->where(fn ($kind) => $kind->where(fn ($principal) => $principal->where('notice_lead.kind', 'principal')->whereNull('notice_lead.grade_level_id'))
                                    ->orWhere(fn ($head) => $head->where('notice_lead.kind', 'grade_head')->whereColumn('notice_lead.grade_level_id', 'notice_group.grade_level_id')))
                                ->whereExists($actor->roles()->whereNotIn('roles.name', ['Parent', 'Student'])->getQuery()->selectRaw('1')->toBase());
                        });
                    });
            });
        } else {
            $children = app(GuardianService::class)->childrenFor((int) $actor->id, (int) (Parents::where('user_id', $actor->id)->value('id') ?? 0))->select('students.id');
            $state->where('audience', 'family')->where(function ($owner) use ($actor, $children) {
                $owner->where(fn ($parent) => $parent->whereIn('progress_band_states.learner_id', $children)->whereExists($actor->roles()->where('roles.name', 'Parent')->getQuery()->selectRaw('1')->toBase()))
                    ->orWhere(fn ($own) => $own->whereIn('progress_band_states.learner_id', Students::where('user_id', $actor->id)->select('id'))
                        ->whereExists($actor->roles()->where('roles.name', 'Student')->getQuery()->selectRaw('1')->toBase()));
            })->whereExists(function ($approval) use ($school) {
                $approval->selectRaw('1')->from('learner_approvals as notice_approval')->where('notice_approval.school_id', $school)
                    ->whereColumn('notice_approval.learner_id', 'progress_band_states.learner_id')->whereNull('notice_approval.withdrawn_at')
                    ->whereExists(fn ($guardian) => $guardian->selectRaw('1')->from('guardian_links as notice_guardian')->join('users as notice_guardian_user', 'notice_guardian_user.id', '=', 'notice_guardian.guardian_user_id')
                        ->whereColumn('notice_guardian.guardian_user_id', 'notice_approval.guardian_user_id')->whereColumn('notice_guardian.learner_id', 'notice_approval.learner_id')
                        ->where('notice_guardian.school_id', $school)->where('notice_guardian.status', 'active')->where('notice_guardian.can_approve', true)
                        ->whereNotNull('notice_guardian_user.email_verified_at')->where('notice_guardian_user.status', 1)->whereNull('notice_guardian_user.deleted_at'));
            });
        }

        return $state;
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        return app(ProgressNotices::class)->allows($actor, $type, $payload);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Wellbeing\Appointment;
use App\Models\Wellbeing\CareCase;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\CaseEntry;
use App\Models\Wellbeing\Event;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\NoticeIntent;
use App\Models\Wellbeing\TeamAssignment;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** Only opaque per-recipient intent IDs and fixed generic text leave this domain. */
class WellbeingNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public const STAFF = 'wellbeing.staff_update';

    public const REPORTER = 'wellbeing.report_reply';

    public const STAFF_LINK = '/wellbeing';

    public const REPORTER_LINK = '/report-concern';

    public function __construct(private WellbeingAccess $access) {}

    public function record(Event $event, Model $row, array $context = []): void
    {
        abort_unless((int) $event->school_id === app(CurrentSchool::class)->id() && (int) $row->school_id === (int) $event->school_id, 403);
        $today = app(AcademicAuthority::class)->today();
        $targets = [];
        if ($row instanceof IncidentReport && in_array($event->event, ['wellbeing.report_submitted', 'wellbeing.reporter_follow_up', 'wellbeing.reporter_identified', 'wellbeing.photo_added', 'wellbeing.staff_follow_up'], true)) {
            $recipients = TeamAssignment::where('category_id', $row->category_id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('user_id');
            foreach ($recipients as $id) {
                $targets[] = ['kind' => 'report_staff', 'recipient_user_id' => (int) $id, 'incident_report_id' => (int) $row->id];
            }
            if ($event->event === 'wellbeing.staff_follow_up' && ($context['reporter_visible'] ?? false) === true && ! $row->anonymous && $row->reporter_user_id !== null) {
                $targets[] = ['kind' => 'report_reply', 'recipient_user_id' => (int) $row->reporter_user_id, 'incident_report_id' => (int) $row->id];
            }
        } elseif (($row instanceof CareCase && in_array($event->event, ['wellbeing.case_status_changed', 'wellbeing.case_incident_linked'], true))
            || ($row instanceof CaseEntry && $event->event === 'wellbeing.case_entry_added')
            || ($row instanceof Appointment && $event->event === 'wellbeing.appointment_saved')
            || ($row instanceof CaseAssignment && $event->event === 'wellbeing.case_assignment_saved')) {
            $case = $row instanceof CareCase ? (int) $row->id : (int) $row->support_case_id;
            $recipients = CaseAssignment::where('support_case_id', $case)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('user_id');
            foreach ($recipients as $id) {
                $targets[] = ['kind' => 'case_staff', 'recipient_user_id' => (int) $id, 'support_case_id' => $case];
            }
        }
        $created = false;
        foreach ($targets as $target) {
            if ($event->actor_id !== null && (int) $event->actor_id === $target['recipient_user_id']) {
                continue;
            }
            $identity = ['event_id' => (int) $event->id, 'recipient_user_id' => $target['recipient_user_id'], 'kind' => $target['kind']];
            if (NoticeIntent::where($identity)->exists()) {
                continue;
            }
            $intent = NoticeIntent::create($identity + $target + ['id' => (string) Str::uuid()]);
            Outbox::record(new NoticeRequested((int) $event->school_id, $intent->id));
            $created = true;
        }
        if ($created) {
            DB::afterCommit(static function () {
                try {
                    DispatchOutbox::dispatch();
                } catch (Throwable $error) {
                    Log::error('wellbeing_notice_acceleration_failed', ['exception' => get_class($error)]);
                }
            });
        }
    }

    /** Metadata only: replicate current named-reporter learner scope without reading bodies/codes. */
    private function ownReports(User $actor): Builder
    {
        $roles = app(ProfilePolicy::class)->roles($actor);
        $parentIds = in_array('Parent', $roles, true) ? Parents::where('user_id', $actor->id)->pluck('id')->all() : [];
        $school = app(CurrentSchool::class)->id();
        $learners = Students::where(function (Builder $query) use ($roles, $actor, $parentIds, $school) {
            $query->whereRaw('1 = 0');
            if (in_array('Student', $roles, true)) {
                $query->orWhere('user_id', $actor->id);
            }
            if (in_array('Parent', $roles, true)) {
                $query->orWhere(function (Builder $parents) use ($actor, $parentIds, $school) {
                    $parents->whereExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $school)->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $actor->id)->where('status', 'active'))
                        ->orWhere(function ($legacy) use ($actor, $parentIds, $school) {
                            $legacy->whereNotExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $school)->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $actor->id))
                                ->where(fn ($ids) => $ids->whereIn('father_id', $parentIds)->orWhereIn('mother_id', $parentIds)->orWhereIn('guardian_id', $parentIds));
                        });
                });
            }
        })->whereIn('id', Enrolment::where('session_year_id', (int) schoolSetting('session_year'))->onDate(app(AcademicAuthority::class)->today())->select('learner_id'))->select('id');

        return IncidentReport::where('anonymous', false)->where('reporter_user_id', $actor->id)
            ->where(fn ($query) => $query->whereNull('learner_id')->orWhereIn('learner_id', $learners))->select('id');
    }

    private function permitted(User $actor): Builder
    {
        $fresh = $this->access->actor($actor);
        $teams = $cases = [];
        if ($this->access->professionalAvailable($fresh)) {
            $teams = $this->access->teamIds($fresh);
            $cases = $this->access->caseIds($fresh);
        }
        $reports = IncidentReport::whereIn('category_id', $teams)->select('id');
        $owned = $this->ownReports($fresh);

        return NoticeIntent::where('recipient_user_id', $fresh->id)->where(function (Builder $query) use ($reports, $cases, $owned) {
            $query->where(fn ($staff) => $staff->where('kind', 'report_staff')->whereNull('support_case_id')->whereIn('incident_report_id', $reports))
                ->orWhere(fn ($case) => $case->where('kind', 'case_staff')->whereNull('incident_report_id')->whereIn('support_case_id', $cases))
                ->orWhere(fn ($owner) => $owner->where('kind', 'report_reply')->whereNull('support_case_id')->whereIn('incident_report_id', $owned));
        });
    }

    /** Shared staff inbox bridge; never includes named-reporter replies. */
    public function staffReadIntents(User $actor): Builder
    {
        return $this->permitted($actor)->whereIn('kind', ['report_staff', 'case_staff']);
    }

    public function apply(Builder $notifications, User $actor): void
    {
        $allowed = $this->permitted($actor);
        $notifications->where(function (Builder $query) use ($allowed) {
            $query->where(fn ($staff) => $staff->where('type', self::STAFF)->whereIn('id', (clone $allowed)->whereIn('kind', ['report_staff', 'case_staff'])->select('notification_id')))
                ->orWhere(fn ($owner) => $owner->where('type', self::REPORTER)->whereIn('id', (clone $allowed)->where('kind', 'report_reply')->select('notification_id')));
        });
    }

    private function delivered(User $actor): Builder
    {
        $fresh = $this->access->actor($actor);
        $links = UserNotification::where('user_id', $fresh->id)->select('notification_id');

        return NotificationVisibility::forUser(Notification::whereIn('type', [self::STAFF, self::REPORTER])->whereIn('id', $links), (int) $fresh->id, 0);
    }

    public function inbox(User $actor, int $page = 1, ?string $area = null): array
    {
        $query = $this->delivered($actor);
        abort_unless($area === null || in_array($area, ['staff', 'reporter'], true), 422);
        if ($area !== null) {
            $query->where('type', $area === 'staff' ? self::STAFF : self::REPORTER);
        }
        $rows = (clone $query)->orderByDesc('id')->paginate(25, ['id', 'type', 'created_at'], 'page', $page);
        $markers = $this->permitted($actor)->whereIn('notification_id', $rows->pluck('id')->all())->get(['notification_id', 'read_at'])->keyBy('notification_id');
        $unread = (clone $query)->whereIn('id', $this->permitted($actor)->whereNull('read_at')->select('notification_id'))->count();
        $items = $rows->map(fn ($notice) => ['id' => (int) $notice->id, 'title' => __('Private school update'),
            'message' => __('Open your private school area to review an available update.'),
            'link' => $notice->type === self::STAFF ? self::STAFF_LINK : self::REPORTER_LINK,
            'created_at' => $notice->created_at->toISOString(), 'read_at' => $markers->get($notice->id)?->read_at?->toISOString()])->all();

        return ['items' => $items, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'unread_count' => $unread];
    }

    public function markRead(User $actor, int $id): array
    {
        return DB::transaction(function () use ($actor, $id) {
            $this->delivered($actor)->select('id')->findOrFail($id);
            $intent = $this->permitted($actor)->where('notification_id', $id)->lockForUpdate()->firstOrFail();
            if ($intent->read_at === null) {
                $intent->read_at = now();
                $intent->save();
            }

            return ['id' => $id, 'read_at' => $intent->read_at->toISOString()];
        });
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if (! in_array($type, [self::STAFF, self::REPORTER], true) || ! is_string($payload['wellbeing_intent_id'] ?? null) || ! Str::isUuid($payload['wellbeing_intent_id'])
            || ($payload['link'] ?? null) !== ($type === self::STAFF ? self::STAFF_LINK : self::REPORTER_LINK)) {
            return false;
        }
        try {
            $school = app(CurrentSchool::class)->id();
            $query = $this->permitted($actor)->whereKey($payload['wellbeing_intent_id'])->whereNotNull('notification_id');
            $query->whereIn('kind', $type === self::STAFF ? ['report_staff', 'case_staff'] : ['report_reply']);
            $allowed = $query->exists();
            $context = app(CurrentSchool::class);

            return $allowed && $context->id() === $school && ! $context->isAcrossSchools() && ! $context->isImpersonating();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** Called only by the school-scoped private outbox listener. */
    public function queue(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }
        DB::transaction(function () use ($id) {
            $intent = NoticeIntent::whereKey($id)->lockForUpdate()->first();
            if ($intent === null || $intent->queued_at !== null) {
                return;
            }
            $actor = User::find($intent->recipient_user_id);
            try {
                $permitted = $actor !== null && $this->permitted($actor)->whereKey($id)->exists();
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $permitted = false;
            }
            // Processed even if access was withdrawn; re-granting rights does
            // not turn an old suppressed alert into a new clinical disclosure.
            $intent->queued_at = now();
            $intent->save();
            if (! $permitted) {
                return;
            }
            $staff = $intent->kind !== 'report_reply';
            $type = $staff ? self::STAFF : self::REPORTER;
            $link = $staff ? self::STAFF_LINK : self::REPORTER_LINK;
            $notifier = app(Notifier::class);
            $notice = $intent->notification_id === null ? $notifier->makeNotice($type, __('Private school update'), __('Open your private school area to review an available update.'), $link) : Notification::findOrFail($intent->notification_id);
            $notice->delivery_payload = ['link' => $link, 'wellbeing_intent_id' => $intent->id];
            $notice->save();
            $intent->notification_id = $notice->id;
            $intent->save();
            $notifier->assign($notice, [(int) $actor->id]);
        });
    }
}

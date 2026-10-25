<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Academics\AcademicLeadership;
use App\Models\Discipline\DetentionEntry;
use App\Models\Discipline\DetentionSession;
use App\Models\Discipline\DisciplineNoticeIntent;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Notification;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Routing metadata only. No infraction, name, reason, location or case body leaves its reader. */
class DisciplineNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public const FAMILY = 'discipline.family_update';

    public const STAFF = 'discipline.staff_update';

    public const REVIEW = 'discipline.review_requested';

    public const HEARING = 'discipline.hearing_requested';

    public const DETENTION = 'discipline.detention_updated';

    public const TYPES = [self::FAMILY, self::STAFF, self::REVIEW, self::HEARING, self::DETENTION];

    private function link(string $type): string
    {
        return $type === self::FAMILY ? '/discipline' : '/school-discipline';
    }

    private function context(int $school): void
    {
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        app(DisciplineAuthority::class)->enabled();
    }

    private function leaders(int $grade, bool $principalOnly = false): array
    {
        $today = app(AcademicAuthority::class)->today();

        return AcademicLeadership::whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->where(fn ($q) => $q->where(fn ($p) => $p->where('kind', 'principal')->whereNull('grade_level_id'))
                ->when(! $principalOnly, fn ($p) => $p->orWhere(fn ($g) => $g->where('kind', 'grade_head')->where('grade_level_id', $grade))))
            ->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->all();
    }

    public function record(int $id, int $revision): void
    {
        app(DisciplineAuthority::class)->enabled();
        $row = DisciplineRecord::select(DisciplineRecords::META)->whereKey($id)->where('revision', $revision)->first();
        if ($row === null) {
            return;
        }
        $leaders = $this->leaders((int) $row->grade_level_id);
        $type = match ($row->status) {
            'pending_review' => self::REVIEW,
            'hearing' => self::HEARING,
            default => null,
        };
        $actionRecipients = $type === null ? [] : $this->leaders((int) $row->grade_level_id, $type === self::HEARING);
        foreach (array_diff(array_unique([...$leaders, (int) $row->recorded_by]), $actionRecipients) as $user) {
            $this->queue($row, $user, self::STAFF);
        }
        if ($type !== null) {
            foreach ($actionRecipients as $user) {
                $this->queue($row, $user, $type);
            }
        }
        $this->family($row);
    }

    private function family(DisciplineRecord $record, ?DetentionSession $session = null): void
    {
        if ($record->published_at === null) {
            return;
        }
        $learner = Students::find($record->learner_id);
        if ($learner === null) {
            return;
        }
        foreach (app(GuardianService::class)->guardianUserIds($learner)->push((int) $learner->user_id)->unique() as $user) {
            $this->queue($record, (int) $user, self::FAMILY, $session);
        }
    }

    public function detention(int $id, int $revision): void
    {
        app(DisciplineAuthority::class)->enabled();
        $session = DetentionSession::select('id', 'revision', 'grade_level_id', 'session_year_id', 'supervisor_id')->whereKey($id)->where('revision', $revision)->first();
        if ($session === null) {
            return;
        }
        foreach (DetentionEntry::where('session_id', $id)->orderBy('id')->cursor() as $entry) {
            $record = DisciplineRecord::select(DisciplineRecords::META)->find($entry->record_id);
            if ($record === null || (int) $record->learner_id !== (int) $entry->learner_id) {
                continue;
            }
            foreach (array_unique([...(array) $this->leaders((int) $session->grade_level_id), (int) $session->supervisor_id]) as $user) {
                $this->queue($record, $user, self::DETENTION, $session);
            }
            $this->family($record, $session);
        }
    }

    private function queue(DisciplineRecord $record, int $userId, string $type, ?DetentionSession $session = null): void
    {
        $school = app(CurrentSchool::class)->id();
        $actor = User::find($userId);
        if ($actor === null) {
            return;
        }
        $source = ($session === null ? 'record' : 'detention:'.$session->id.':'.$session->revision).':'.$record->id.':'.$record->revision;
        DB::transaction(function () use ($record, $actor, $type, $session, $school, $source) {
            School::lockForUpdate()->findOrFail($school);
            $this->context($school);
            $intent = DisciplineNoticeIntent::firstOrNew(['user_id' => $actor->id, 'source_key' => $source, 'type' => $type],
                ['id' => (string) Str::uuid(), 'record_id' => $record->id, 'record_revision' => $record->revision,
                    'detention_session_id' => $session?->id, 'detention_revision' => $session?->revision]);
            if (! $this->permitted($actor, $intent)) {
                return;
            }
            $this->context($school);
            if (! $intent->exists) {
                $intent->save();
            }
            $notifier = app(Notifier::class);
            $notice = $intent->notification_id === null ? null : Notification::withTrashed()->find($intent->notification_id);
            if ($notice?->trashed()) {
                return;
            }
            $created = $notice === null;
            if ($created) {
                $notice = $notifier->makeNotice($type, __('School discipline update'), __('Open School discipline to review an available update.'), $this->link($type));
                $notice->delivery_payload = ['link' => $this->link($type), 'discipline_intent_id' => $intent->id];
                $notice->save();
                $intent->notification_id = $notice->id;
                $intent->save();
            }
            $this->context($school);
            abort_unless($this->permitted($actor, $intent), 403);
            $this->context($school);
            if ($created) {
                $notifier->assign($notice, [(int) $actor->id]);
            }
        });
    }

    private function permitted(User $actor, DisciplineNoticeIntent $intent): bool
    {
        if ((int) $intent->user_id !== (int) $actor->id || ! in_array($intent->type, self::TYPES, true)) {
            return false;
        }
        $school = app(CurrentSchool::class)->id();
        try {
            $authority = app(DisciplineAuthority::class);
            $fresh = $authority->actor($actor, $intent->type !== self::FAMILY);
            $record = DisciplineRecord::select(DisciplineRecords::META)->whereKey($intent->record_id)->where('revision', $intent->record_revision)->first();
            if ($record === null) {
                return false;
            }
            if ($intent->type === self::FAMILY) {
                if ($record->published_at === null) {
                    return false;
                }
                $authority->family($fresh, (int) $record->learner_id);
            } elseif ($intent->type === self::REVIEW) {
                if ($record->status !== 'pending_review') {
                    return false;
                }
                $authority->head($fresh, (int) $record->session_year_id, (int) $record->grade_level_id);
            } elseif ($intent->type === self::HEARING) {
                if ($record->status !== 'hearing') {
                    return false;
                }
                $authority->principal($fresh, (int) $record->session_year_id);
            } elseif ($intent->type !== self::DETENTION) {
                $authority->read($fresh, $record);
            }
            if ($intent->detention_session_id !== null) {
                $session = DetentionSession::select('id', 'revision', 'session_year_id', 'grade_level_id')->whereKey($intent->detention_session_id)->where('revision', $intent->detention_revision)->first();
                if ($session === null || (int) $session->session_year_id !== (int) $record->session_year_id || (int) $session->grade_level_id !== (int) $record->grade_level_id
                    || ! DetentionEntry::where('session_id', $session->id)->where('record_id', $record->id)->where('learner_id', $record->learner_id)->exists()) {
                    return false;
                }
                if ($intent->type !== self::FAMILY) {
                    app(DisciplineDetentions::class)->authorize($fresh, (int) $session->id);
                }
            } elseif ($intent->type === self::DETENTION) {
                return false;
            }
            $this->context($school);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if (! in_array($type, self::TYPES, true) || ($payload['link'] ?? null) !== $this->link($type)
            || ! is_string($payload['discipline_intent_id'] ?? null) || ! Str::isUuid($payload['discipline_intent_id'])) {
            return false;
        }
        $intent = DisciplineNoticeIntent::whereKey($payload['discipline_intent_id'])->where('user_id', $actor->id)->where('type', $type)->first();

        return $intent !== null && $this->permitted($actor, $intent);
    }

    public function apply(Builder $notifications, User $actor): void
    {
        $allowed = [];
        foreach (DisciplineNoticeIntent::where('user_id', $actor->id)->whereNotNull('notification_id')->cursor() as $intent) {
            if ($this->permitted($actor, $intent)) {
                $allowed[] = $intent->id;
            }
        }
        $notifications->whereExists(function ($q) use ($actor, $allowed) {
            $safe = "IF(JSON_VALID(notifications.delivery_payload), notifications.delivery_payload, '{}')";
            $q->selectRaw('1')->from('discipline_notice_intents as dni')->join('discipline_records as dnr', function ($j) {
                $j->on('dnr.id', '=', 'dni.record_id')->on('dnr.school_id', '=', 'dni.school_id')->on('dnr.revision', '=', 'dni.record_revision');
            })->where('dni.school_id', app(CurrentSchool::class)->id())->where('dni.user_id', $actor->id)->whereIn('dni.id', $allowed)
                ->whereColumn('dni.notification_id', 'notifications.id')->whereColumn('dni.type', 'notifications.type')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.discipline_intent_id')) = dni.id")
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT($safe, '$.link')) = CASE WHEN dni.type = ? THEN ? ELSE ? END", [self::FAMILY, $this->link(self::FAMILY), $this->link(self::STAFF)])
                ->where(fn ($p) => $p->where('dni.type', '!=', self::FAMILY)->orWhereNotNull('dnr.published_at'))
                ->where(fn ($p) => $p->whereNull('dni.detention_session_id')->orWhereExists(fn ($s) => $s->selectRaw('1')->from('detention_sessions as dns')
                    ->whereColumn('dns.school_id', 'dni.school_id')->whereColumn('dns.id', 'dni.detention_session_id')->whereColumn('dns.revision', 'dni.detention_revision')));
        });
    }
}

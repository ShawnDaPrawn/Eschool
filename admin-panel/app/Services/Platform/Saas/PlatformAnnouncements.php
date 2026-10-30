<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Announcements\AnnouncementText;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Navigate Ed's announcements to schools (Action Plan SA.13): release notes,
 * maintenance windows and policy changes. HQ drafts one, previews how many
 * schools and Super Admins it reaches, publishes it and may withdraw it. On
 * publication the schools it reaches are fixed (platform_announcement_schools)
 * and each school's Super Admins get a platform.announcement notice through
 * Notifier; the school panel shows a banner while it is current, and each
 * Super Admin's dismissal is a read. Published text never changes: withdraw
 * it and publish a new one. engineering/specs/platform/hq-announcements.md.
 */
class PlatformAnnouncements
{
    /** HQ works in South African time. */
    public const TIMEZONE = 'Africa/Johannesburg';

    /**
     * Announcement times are stored and compared in UTC, never in the app's
     * timezone: inside a school, the school's own time zone setting becomes
     * the app's (SettingsConfigService), so "now" would mean a different
     * clock at each school.
     */
    public const STORAGE_TIMEZONE = 'UTC';

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::STORAGE_TIMEZONE);
    }

    /** A stored announcement time, read as UTC. */
    public static function stored(mixed $value): CarbonImmutable
    {
        return CarbonImmutable::parse((string) $value, self::STORAGE_TIMEZONE);
    }

    public const TYPE = 'platform.announcement';

    /** The school panel page each notice opens. */
    public const LINK = '/navigate-ed-announcements';

    public const KINDS = ['release_notes' => 'Release notes', 'maintenance' => 'Maintenance window', 'policy' => 'Policy change'];

    public const SEVERITIES = ['info' => 'Information', 'warning' => 'Warning'];

    public const AUDIENCES = ['all' => 'All schools', 'packages' => 'Schools on a package', 'stages' => 'Schools at a lifecycle stage', 'schools' => 'Chosen schools'];

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'withdrawn' => 'Withdrawn'];

    public function __construct(private readonly CurrentSchool $currentSchool, private readonly Notifier $notifier) {}

    public static function draftRules(): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys(self::KINDS))],
            'severity' => ['required', Rule::in(array_keys(self::SEVERITIES))],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'shows_from' => ['required', 'date_format:Y-m-d\TH:i'],
            'shows_until' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'window_starts_at' => ['nullable', 'prohibited_unless:kind,maintenance', 'required_with:window_ends_at', 'date_format:Y-m-d\TH:i'],
            'window_ends_at' => ['nullable', 'prohibited_unless:kind,maintenance', 'required_with:window_starts_at', 'date_format:Y-m-d\TH:i'],
            'audience' => ['required', Rule::in(array_keys(self::AUDIENCES))],
            'packages' => ['nullable', 'required_if:audience,packages', 'array', 'max:20'],
            'packages.*' => ['string', 'distinct', Rule::in(array_keys((array) config('school_packages')))],
            'stages' => ['nullable', 'required_if:audience,stages', 'array', 'max:10'],
            'stages.*' => ['string', 'distinct', Rule::in(PlatformSchoolAccounts::STAGES)],
            'schools' => ['nullable', 'required_if:audience,schools', 'array', 'max:500'],
            'schools.*' => ['integer', 'distinct', 'exists:schools,id'],
        ];
    }

    public static function updateRules(): array
    {
        return self::draftRules() + ['revision' => ['required', 'integer', 'min:1']];
    }

    public static function publishRules(): array
    {
        return ['revision' => ['required', 'integer', 'min:1']];
    }

    public static function withdrawRules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:500']];
    }

    /**
     * Drafts, then what schools see now or soon, then the latest ended or withdrawn.
     *
     * @return array{drafts: list<array<string, mixed>>, live: list<array<string, mixed>>, past: list<array<string, mixed>>}
     */
    public function list(): array
    {
        return $this->currentSchool->acrossSchools(function (): array {
            $now = self::now()->format('Y-m-d H:i:s');
            $drafts = DB::table('platform_announcements')->where('status', 'draft')->orderByDesc('updated_at')->limit(100)->get();
            $live = DB::table('platform_announcements')->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('shows_until')->orWhere('shows_until', '>', $now))->orderBy('shows_from')->limit(100)->get();
            $past = DB::table('platform_announcements')->where(fn ($q) => $q->where('status', 'withdrawn')
                ->orWhere(fn ($q) => $q->where('status', 'published')->whereNotNull('shows_until')->where('shows_until', '<=', $now)))
                ->orderByDesc('updated_at')->limit(50)->get();
            $reach = $this->reach($live->pluck('id')->merge($past->pluck('id'))->all());

            $row = fn (object $row): array => $this->summary($row) + ($reach[(int) $row->id] ?? ['schools' => 0, 'schools_read' => 0, 'recipients' => 0, 'reads' => 0]);

            return ['drafts' => $drafts->map($row)->values()->all(), 'live' => $live->map($row)->values()->all(), 'past' => $past->map($row)->values()->all()];
        });
    }

    /** One announcement: its text as schools see it, its audience and, once published, who has read it. */
    public function show(int $id): array
    {
        return $this->currentSchool->acrossSchools(function () use ($id): array {
            $row = DB::table('platform_announcements')->where('id', $id)->first();
            abort_if($row === null, 404);
            $values = $this->values($row);
            $schools = [];
            if ($row->status === 'draft') {
                $preview = $this->preview((string) $row->audience, $values);
                $reach = ['schools' => $preview['schools'], 'recipients' => $preview['admins'], 'schools_read' => 0, 'reads' => 0];
            } else {
                $reach = $this->reach([$id])[$id] ?? ['schools' => 0, 'schools_read' => 0, 'recipients' => 0, 'reads' => 0];
                $reads = DB::table('platform_announcement_reads')->where('announcement_id', $id)->selectRaw('school_id, count(*) as read_count')->groupBy('school_id')->pluck('read_count', 'school_id');
                $schools = DB::table('platform_announcement_schools')->join('schools', 'schools.id', '=', 'platform_announcement_schools.school_id')
                    ->where('announcement_id', $id)->orderBy('schools.name')->limit(500)
                    ->get(['platform_announcement_schools.school_id', 'platform_announcement_schools.recipients', 'schools.name'])
                    ->map(fn (object $school) => ['id' => (int) $school->school_id, 'name' => (string) $school->name, 'recipients' => (int) $school->recipients, 'reads' => (int) ($reads[$school->school_id] ?? 0)])
                    ->values()->all();
            }
            $actors = $this->actorNames([$row->created_by, $row->published_by, $row->withdrawn_by]);

            return [
                'announcement' => $this->summary($row) + $reach + [
                    'body' => (string) $row->body, 'html' => AnnouncementText::html((string) $row->body),
                    'shows_from_input' => $this->input($row->shows_from), 'shows_until_input' => $this->input($row->shows_until),
                    'window_starts_input' => $this->input($row->window_starts_at), 'window_ends_input' => $this->input($row->window_ends_at),
                    'audience_values' => $values, 'revision' => (int) $row->revision,
                    'published_at' => $this->local($row->published_at), 'withdrawn_at' => $this->local($row->withdrawn_at), 'withdrawn_reason' => $row->withdrawn_reason,
                    'created_by' => $actors[$row->created_by] ?? null, 'published_by' => $actors[$row->published_by] ?? null, 'withdrawn_by' => $actors[$row->withdrawn_by] ?? null,
                ],
                'schools' => $schools,
            ];
        });
    }

    /** @return array{packages: list<string>, stages: list<string>, schools: array<int, string>} the audience choices */
    public function choices(): array
    {
        return $this->currentSchool->acrossSchools(fn (): array => [
            'packages' => array_keys((array) config('school_packages')),
            'stages' => PlatformSchoolAccounts::STAGES,
            'schools' => DB::table('schools')->where('status', School::ACTIVE)->orderBy('name')->pluck('name', 'id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])->all(),
        ]);
    }

    public function draft(array $input, User $actor): int
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::draftRules())->validate();
        $fields = $this->fields($data);

        return $this->currentSchool->acrossSchools(fn (): int => DB::transaction(function () use ($fields, $actor): int {
            $id = (int) DB::table('platform_announcements')->insertGetId($fields + [
                'status' => 'draft', 'created_by' => $actor->id, 'revision' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            Audit::record('platform_announcement_drafted', null, [], ['announcement_id' => $id, 'kind' => $fields['kind'], 'audience' => $fields['audience'], 'changed_by' => $actor->id]);

            return $id;
        }, 3));
    }

    public function update(int $id, array $input, User $actor): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::updateRules())->validate();
        $fields = $this->fields($data);

        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $data, $fields, $actor): void {
            $row = $this->lockDraft($id, (int) $data['revision']);
            DB::table('platform_announcements')->where('id', $id)->update($fields + ['revision' => (int) $row->revision + 1, 'updated_at' => now()]);
            Audit::record('platform_announcement_updated', null, [], ['announcement_id' => $id, 'kind' => $fields['kind'], 'audience' => $fields['audience'], 'changed_by' => $actor->id]);
        }, 3));
    }

    /**
     * Fixes the schools it reaches and sends each one's Super Admins the
     * notice. The banner follows the announcement's own dates.
     *
     * @return array{schools: int, recipients: int}
     */
    public function publish(int $id, array $input, User $actor): array
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::publishRules())->validate();

        return $this->currentSchool->acrossSchools(fn (): array => DB::transaction(function () use ($id, $data, $actor): array {
            $row = $this->lockDraft($id, (int) $data['revision']);
            if ($row->shows_until !== null && self::stored($row->shows_until)->lessThanOrEqualTo(self::now())) {
                throw ValidationException::withMessages(['shows_until' => __('This announcement would already have stopped showing. Change its dates first.')]);
            }
            $schoolIds = $this->audienceSchools((string) $row->audience, $this->values($row));
            if ($schoolIds === []) {
                throw ValidationException::withMessages(['audience' => __('No active school matches this audience.')]);
            }
            $admins = $this->superAdmins($schoolIds);
            $message = $this->noticeMessage($row);
            $total = 0;
            foreach ($schoolIds as $schoolId) {
                $recipients = $admins[$schoolId] ?? [];
                DB::table('platform_announcement_schools')->insert(['announcement_id' => $id, 'school_id' => $schoolId, 'recipients' => count($recipients), 'created_at' => now(), 'updated_at' => now()]);
                if ($recipients !== []) {
                    $this->currentSchool->run($schoolId, function () use ($row, $message, $recipients): void {
                        $notice = $this->notifier->makeNotice(self::TYPE, (string) $row->title, $message, self::LINK);
                        $this->notifier->assign($notice, $recipients);
                    });
                }
                $total += count($recipients);
            }
            DB::table('platform_announcements')->where('id', $id)->update([
                'status' => 'published', 'published_at' => self::now()->format('Y-m-d H:i:s'), 'published_by' => $actor->id, 'revision' => (int) $row->revision + 1, 'updated_at' => now(),
            ]);
            Audit::record('platform_announcement_published', null, ['status' => 'draft'], [
                'announcement_id' => $id, 'status' => 'published', 'kind' => $row->kind, 'severity' => $row->severity, 'audience' => $row->audience,
                'schools' => count($schoolIds), 'recipients' => $total, 'changed_by' => $actor->id,
            ]);

            return ['schools' => count($schoolIds), 'recipients' => $total];
        }, 3));
    }

    /** Takes it down at once: the banner goes and the school page no longer lists it. Notices already sent stay sent. */
    public function withdraw(int $id, array $input, User $actor): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::withdrawRules())->validate();

        $this->currentSchool->acrossSchools(fn () => DB::transaction(function () use ($id, $data, $actor): void {
            $row = DB::table('platform_announcements')->where('id', $id)->lockForUpdate()->first();
            abort_if($row === null, 404);
            abort_if($row->status !== 'published', 409, __('Only a published announcement can be withdrawn.'));
            DB::table('platform_announcements')->where('id', $id)->update([
                'status' => 'withdrawn', 'withdrawn_at' => self::now()->format('Y-m-d H:i:s'), 'withdrawn_by' => $actor->id, 'withdrawn_reason' => $data['reason'],
                'revision' => (int) $row->revision + 1, 'updated_at' => now(),
            ]);
            Audit::record('platform_announcement_withdrawn', null, ['status' => 'published'], ['announcement_id' => $id, 'status' => 'withdrawn', 'changed_by' => $actor->id]);
        }, 3));
    }

    /**
     * How many active schools, and their Super Admins, an audience reaches now.
     *
     * @param  list<string|int>  $values
     * @return array{schools: int, admins: int}
     */
    public function preview(string $audience, array $values): array
    {
        return $this->currentSchool->acrossSchools(function () use ($audience, $values): array {
            $schoolIds = $this->audienceSchools($audience, $values);

            return ['schools' => count($schoolIds), 'admins' => array_sum(array_map('count', $this->superAdmins($schoolIds)))];
        });
    }

    /**
     * The active schools an audience names. A school with no HQ record is at
     * the default stage, and one with no package is on the legacy package.
     *
     * @param  list<string|int>  $values
     * @return list<int>
     */
    private function audienceSchools(string $audience, array $values): array
    {
        $query = DB::table('schools')->where('schools.status', School::ACTIVE)
            ->leftJoin('school_accounts', 'school_accounts.school_id', '=', 'schools.id');
        match ($audience) {
            'all' => null,
            'packages' => $query->whereIn(DB::raw("coalesce(schools.package_key, 'legacy')"), array_map('strval', $values)),
            'stages' => $query->whereIn(DB::raw("coalesce(school_accounts.stage, '".PlatformSchoolAccounts::DEFAULT_STAGE."')"), array_map('strval', $values)),
            'schools' => $query->whereIn('schools.id', array_map('intval', $values)),
            default => $query->whereRaw('1 = 0'),
        };

        return $query->orderBy('schools.id')->pluck('schools.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Each school's active Super Admins, never a platform staff account.
     *
     * @param  list<int>  $schoolIds
     * @return array<int, list<int>> school id => user ids
     */
    private function superAdmins(array $schoolIds): array
    {
        if ($schoolIds === []) {
            return [];
        }
        $staff = DB::table('platform_staff_identities')->where('active', true)->pluck('user_id');

        return SchoolMembership::query()->active()->whereIn('school_memberships.school_id', $schoolIds)
            ->where('school_memberships.role', SchoolRoles::SUPER_ADMIN)->whereNotIn('school_memberships.user_id', $staff)
            ->whereHas('user', fn ($user) => $user->where('status', 1))
            ->get(['school_memberships.school_id', 'school_memberships.user_id'])
            ->groupBy('school_id')->map(fn ($rows) => $rows->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all())->all();
    }

    /**
     * Schools reached and Super Admins told at publication, and how many of each have read it.
     *
     * @param  list<int>  $ids
     * @return array<int, array{schools: int, schools_read: int, recipients: int, reads: int}>
     */
    private function reach(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $targets = DB::table('platform_announcement_schools')->whereIn('announcement_id', $ids)
            ->selectRaw('announcement_id, count(*) as schools, coalesce(sum(recipients), 0) as recipients')->groupBy('announcement_id')->get()->keyBy('announcement_id');
        $reads = DB::table('platform_announcement_reads')->whereIn('announcement_id', $ids)
            ->selectRaw('announcement_id, count(*) as read_count, count(distinct school_id) as schools_read')->groupBy('announcement_id')->get()->keyBy('announcement_id');
        $reach = [];
        foreach ($ids as $id) {
            $reach[(int) $id] = [
                'schools' => (int) ($targets[$id]->schools ?? 0), 'recipients' => (int) ($targets[$id]->recipients ?? 0),
                'schools_read' => (int) ($reads[$id]->schools_read ?? 0), 'reads' => (int) ($reads[$id]->read_count ?? 0),
            ];
        }

        return $reach;
    }

    /** The validated form as columns, with its times checked and stored in UTC. */
    private function fields(array $data): array
    {
        $from = $this->parse($data['shows_from']);
        $until = ($data['shows_until'] ?? null) ? $this->parse($data['shows_until']) : null;
        $windowStart = ($data['window_starts_at'] ?? null) ? $this->parse($data['window_starts_at']) : null;
        $windowEnd = ($data['window_ends_at'] ?? null) ? $this->parse($data['window_ends_at']) : null;
        if ($windowStart !== null && $windowEnd !== null && ! $windowEnd->greaterThan($windowStart)) {
            throw ValidationException::withMessages(['window_ends_at' => __('The maintenance window must end after it starts.')]);
        }
        // A maintenance banner stops showing when the window ends, unless told otherwise.
        $until ??= $windowEnd;
        if ($until !== null && ! $until->greaterThan($from)) {
            throw ValidationException::withMessages(['shows_until' => __('The announcement must stop showing after it starts.')]);
        }
        $values = match ($data['audience']) {
            'packages' => array_values(array_map('strval', $data['packages'] ?? [])),
            'stages' => array_values(array_map('strval', $data['stages'] ?? [])),
            'schools' => array_values(array_unique(array_map('intval', $data['schools'] ?? []))),
            default => null,
        };

        return [
            'kind' => $data['kind'], 'severity' => $data['severity'], 'title' => trim((string) $data['title']), 'body' => trim((string) $data['body']),
            'shows_from' => $from, 'shows_until' => $until, 'window_starts_at' => $windowStart, 'window_ends_at' => $windowEnd,
            'audience' => $data['audience'], 'audience_values' => $values === null ? null : json_encode($values),
        ];
    }

    private function lockDraft(int $id, int $revision): object
    {
        $row = DB::table('platform_announcements')->where('id', $id)->lockForUpdate()->first();
        abort_if($row === null, 404);
        abort_if($row->status !== 'draft', 409, __('A published announcement is never changed. Withdraw it and publish a new one.'));
        abort_if((int) $row->revision !== $revision, 409, __('This announcement changed. Reload before saving.'));

        return $row;
    }

    private function authorise(User $actor): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'announcements.manage'), 403);
    }

    /** The notice's line: what kind of announcement and, for maintenance, when. */
    private function noticeMessage(object $row): string
    {
        if ($row->kind === 'maintenance' && $row->window_starts_at !== null) {
            return 'Maintenance from '.$this->local($row->window_starts_at).' to '.$this->local($row->window_ends_at).' (South African time).';
        }

        return self::KINDS[$row->kind].' from Navigate Ed: '.AnnouncementText::plain((string) $row->body, 80);
    }

    private function summary(object $row): array
    {
        $now = self::now();
        $from = self::stored($row->shows_from);
        $until = $row->shows_until === null ? null : self::stored($row->shows_until);
        $showing = match (true) {
            $row->status !== 'published' => $row->status,
            $from->greaterThan($now) => 'upcoming',
            $until !== null && $until->lessThanOrEqualTo($now) => 'ended',
            default => 'showing',
        };

        return [
            'id' => (int) $row->id, 'kind' => (string) $row->kind, 'severity' => (string) $row->severity, 'title' => (string) $row->title,
            'status' => (string) $row->status, 'showing' => $showing, 'audience' => (string) $row->audience,
            'shows_from' => $this->local($row->shows_from), 'shows_until' => $this->local($row->shows_until),
            'window_starts_at' => $this->local($row->window_starts_at), 'window_ends_at' => $this->local($row->window_ends_at),
        ];
    }

    /** @return list<string|int> */
    private function values(object $row): array
    {
        $values = $row->audience_values === null ? [] : json_decode((string) $row->audience_values, true);

        return is_array($values) ? array_values($values) : [];
    }

    private function parse(string $value): CarbonImmutable
    {
        $at = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value, self::TIMEZONE);
        if ($at === null) {
            throw ValidationException::withMessages(['shows_from' => __('Enter a date and time.')]);
        }

        return $at->setTimezone(self::STORAGE_TIMEZONE);
    }

    private function local(mixed $value): ?string
    {
        return $value === null ? null : self::stored($value)->setTimezone(self::TIMEZONE)->format('Y-m-d H:i');
    }

    private function input(mixed $value): ?string
    {
        return $value === null ? null : self::stored($value)->setTimezone(self::TIMEZONE)->format('Y-m-d\TH:i');
    }

    /** @return array<int, string> */
    private function actorNames(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return User::acrossSchools()->withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
    }
}

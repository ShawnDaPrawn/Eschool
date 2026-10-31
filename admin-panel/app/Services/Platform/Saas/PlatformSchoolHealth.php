<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\School;
use App\Models\Platform\SchoolHealthSnapshot;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\PushDevices;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * School health and usage in HQ (Action Plan SA.10, the Build 1 core
 * metrics): weekly active families, guardian reach, sign-ins by role, module
 * use, email and push delivery, AI spend against the cap and failed jobs, with
 * at-risk flags from config/school_health.php, for one school and across
 * schools. Counts and shares only: no query here returns a person's name or
 * contact, and nothing is shown per person.
 *
 * One school's page is computed when it is opened (bounded aggregate queries
 * over that school's rows). The comparison across schools reads the nightly
 * figures (school_health_snapshots, written by platform:school-health), which
 * are also the only history of weekly active families: a sign-in token keeps
 * only its latest use. engineering/specs/platform/hq-school-health.md.
 */
class PlatformSchoolHealth
{
    public const ABILITY = 'health.view';

    /** The role groups sign-ins are counted in; every other school role is staff. */
    public const ROLE_GROUPS = ['Parent', 'Student', 'Teacher', 'Staff'];

    /**
     * Modules whose activity is never counted in HQ: even the number of
     * messages could tell HQ that something happened to a child.
     */
    public const SENSITIVE_MODULES = ['wellbeing', 'discipline', 'support_plans', 'family_forms', 'early_warning'];

    /** AI features (config/ai.php) and the module each one belongs to. */
    public const AI_FEATURE_MODULES = [
        'school_assistant' => 'ask_school', 'message_translation' => 'communication', 'exam_coach' => 'exam_coach',
        'teaching_assistant' => 'teaching_assistant', 'tutor' => 'tutor',
    ];

    /** Failed jobs read to name their kinds; the count is never limited. */
    private const FAILED_JOB_NAMES_READ = 200;

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    public function authorise(?User $actor): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, self::ABILITY), 403);
    }

    /**
     * One school's health, computed now, with its flags and the date of its
     * last nightly figures.
     *
     * @return array{metrics: array<string, mixed>, flags: list<array<string, mixed>>, computed_at: CarbonImmutable, last_snapshot_at: ?string, history: list<int>}
     */
    public function school(?User $actor, int $schoolId): array
    {
        $this->authorise($actor);
        $metrics = $this->compute($schoolId);
        $history = $this->history($schoolId, $metrics['date']);
        $last = $this->currentSchool->run($schoolId, fn () => SchoolHealthSnapshot::query()->orderByDesc('snapshot_date')->value('computed_at'));
        $this->authorise($actor);

        return [
            'metrics' => $metrics,
            'flags' => $this->flags($metrics, $history),
            'computed_at' => CarbonImmutable::now(),
            'last_snapshot_at' => $last === null ? null : (string) $last,
            'history' => $history,
        ];
    }

    /**
     * Every school that is not archived, with its latest nightly figures,
     * ordered by $sort (flagged schools first unless another order is asked).
     *
     * @return array{schools: list<array<string, mixed>>, sort: string, sorts: list<string>}
     */
    public function overview(?User $actor, string $sort = 'flags'): array
    {
        $this->authorise($actor);
        $sorts = ['flags', 'name', 'active', 'reach', 'bounce'];
        $sort = in_array($sort, $sorts, true) ? $sort : 'flags';
        $schools = DB::table('schools')->where('status', '!=', School::ARCHIVED)->orderBy('name')->orderBy('id')->get(['id', 'name', 'status']);
        $latest = $this->currentSchool->acrossSchools(function () {
            // Platform code: HQ compares every school's own latest figures.
            $dates = DB::table('school_health_snapshots')->select('school_id', DB::raw('MAX(snapshot_date) AS latest'))->groupBy('school_id');

            return SchoolHealthSnapshot::acrossSchools()
                ->joinSub($dates, 'latest', fn ($join) => $join->on('latest.school_id', '=', 'school_health_snapshots.school_id')
                    ->on('latest.latest', '=', 'school_health_snapshots.snapshot_date'))
                ->select('school_health_snapshots.*')->get()->keyBy(fn (SchoolHealthSnapshot $row) => (int) $row->getAttribute('school_id'));
        });
        $rows = [];
        foreach ($schools as $school) {
            $snapshot = $latest->get((int) $school->id);
            $rows[] = ['id' => (int) $school->id, 'name' => (string) $school->name, 'status' => (string) $school->status]
                + ($snapshot === null ? ['computed_at' => null, 'flags' => [], 'flag_count' => 0, 'headline' => null]
                    : ['computed_at' => (string) $snapshot->computed_at, 'flags' => array_values(array_filter($snapshot->flags, fn ($flag) => ($flag['state'] ?? '') === 'flagged')),
                        'flag_count' => $snapshot->flag_count, 'headline' => $this->headline($snapshot->metrics)]);
        }
        usort($rows, fn (array $a, array $b) => $this->order($sort, $a, $b));
        $this->authorise($actor);

        return ['schools' => $rows, 'sort' => $sort, 'sorts' => $sorts];
    }

    /**
     * Computes one school's figures and flags for its day and keeps them
     * (one row per school and day; a second run that day replaces the first).
     * Figures older than school_health.keep_days are deleted. For the nightly
     * command only: it shows nothing to anyone.
     */
    public function snapshot(int $schoolId): SchoolHealthSnapshot
    {
        $metrics = $this->compute($schoolId);
        $flags = $this->flags($metrics, $this->history($schoolId, $metrics['date']));

        return $this->currentSchool->run($schoolId, function () use ($metrics, $flags): SchoolHealthSnapshot {
            $snapshot = SchoolHealthSnapshot::query()->updateOrCreate(['snapshot_date' => $metrics['date']], [
                'guardians' => $metrics['families']['guardians'],
                'active_guardians' => $metrics['families']['active'],
                'learners' => $metrics['reach']['learners'],
                'reached_learners' => $metrics['reach']['reached'],
                'flag_count' => count(array_filter($flags, fn (array $flag) => $flag['state'] === 'flagged')),
                'metrics' => $metrics,
                'flags' => $flags,
                'computed_at' => now(),
            ]);
            $keep = max(35, (int) config('school_health.keep_days', 400));
            SchoolHealthSnapshot::query()->where('snapshot_date', '<', CarbonImmutable::parse($metrics['date'])->subDays($keep)->toDateString())->delete();

            return $snapshot;
        });
    }

    /**
     * The school's figures now. Every query names the school; people are
     * counted, never listed.
     *
     * @return array<string, mixed>
     */
    public function compute(int $schoolId): array
    {
        $school = DB::table('schools')->where('id', $schoolId)->first(['id', 'timezone']);
        abort_if($school === null, 404);
        $timezone = (string) ($school->timezone ?: 'Africa/Johannesburg');
        $now = CarbonImmutable::now();
        $today = $now->setTimezone($timezone)->toDateString();
        $lastActive = $this->lastActive($schoolId, $today);

        return [
            'date' => $today,
            ...$this->families($schoolId, $today, $lastActive, $now->subDays(7)),
            'sign_ins' => $this->signIns($schoolId, $today, $lastActive, $now),
            'learners_active' => DB::table('learner_activity_days')->where('school_id', $schoolId)
                ->where('on_date', '>', CarbonImmutable::parse($today)->subDays(7)->toDateString())->distinct()->count('learner_id'),
            'modules' => $this->modules($schoolId, $now->subDays(30)),
            'delivery' => $this->delivery($schoolId, $now->subDays(30)),
            'ai' => $this->ai($schoolId, $now->setTimezone($timezone)),
            'failed_jobs' => $this->failedJobs($schoolId, $now->subDays(30)),
            // Not measured yet: file sizes are not recorded for most uploads,
            // the school's own sending tenant arrives with Action Plan 2.3,
            // and application errors are only in the logs.
            'storage' => null,
            'sending_tenant' => null,
            'errors' => null,
        ];
    }

    /**
     * The at-risk rules (config/school_health.php) against these figures and
     * the weekly active families of the four weeks before.
     *
     * @param  array<string, mixed>  $metrics
     * @param  list<int>  $history
     * @return list<array{key: string, label: string, state: string, detail: string}>
     */
    public function flags(array $metrics, array $history): array
    {
        $rules = (array) config('school_health.flags', []);
        $flags = [];

        $rule = (array) ($rules['activity_drop'] ?? []);
        $now = (int) $metrics['families']['active'];
        $n = count($history);
        $sum = array_sum($history);
        $needed = max(1, (int) ($rule['minimum_history'] ?? 4));
        if ($n < $needed) {
            $flags[] = $this->flag('activity_drop', 'Activity down', 'unknown', __('Not enough nightly figures yet to compare weekly active families (:count of :needed).', ['count' => $n, 'needed' => $needed]));
        } elseif ($sum < (float) ($rule['minimum_average'] ?? 0) * $n) {
            $flags[] = $this->flag('activity_drop', 'Activity down', 'unknown', __('Too few active families in the four weeks before to compare (an average of :average).', ['average' => $this->average($sum, $n)]));
        } else {
            $drop = ($sum - $now * $n);
            $state = $drop > 0 && $this->reaches($drop, $sum, (float) ($rule['percent'] ?? 30)) ? 'flagged' : 'ok';
            $flags[] = $this->flag('activity_drop', 'Activity down', $state, __(':now weekly active families against an average of :average in the four weeks before (:change%).', [
                'now' => $now, 'average' => $this->average($sum, $n), 'change' => ($drop > 0 ? '-' : '+').(string) intdiv(abs($drop) * 100, max(1, $sum)),
            ]));
        }

        $rule = (array) ($rules['low_reach'] ?? []);
        $learners = (int) $metrics['reach']['learners'];
        $reached = (int) $metrics['reach']['reached'];
        if ($learners < max(1, (int) ($rule['minimum_learners'] ?? 1))) {
            $flags[] = $this->flag('low_reach', 'Low reach', 'unknown', __('Too few learners to judge reach (:count).', ['count' => $learners]));
        } else {
            $state = $this->reaches($reached, $learners, (float) ($rule['percent'] ?? 60)) ? 'ok' : 'flagged';
            $flags[] = $this->flag('low_reach', 'Low reach', $state, __(':reached of :learners learners (:share%) have a guardian the school can reach.', ['reached' => $reached, 'learners' => $learners, 'share' => intdiv($reached * 100, $learners)]));
        }

        $sent = (int) $metrics['delivery']['mail_sent'];
        foreach (['bounce_rate' => ['Bounces', 'mail_bounced', 'bounced'], 'complaint_rate' => ['Complaints', 'mail_complained', 'marked as spam']] as $key => [$label, $field, $word]) {
            $rule = (array) ($rules[$key] ?? []);
            $count = (int) $metrics['delivery'][$field];
            if ($sent < max(1, (int) ($rule['minimum_sent'] ?? 1))) {
                $flags[] = $this->flag($key, $label, 'unknown', __('Too few emails in 30 days to judge (:count sent).', ['count' => $sent]));
            } else {
                $flags[] = $this->flag($key, $label, $this->reaches($count, $sent, (float) ($rule['percent'] ?? 5)) ? 'flagged' : 'ok',
                    __(':count of :sent emails :word in 30 days (:rate%).', ['count' => $count, 'sent' => $sent, 'word' => __($word), 'rate' => $this->rate($count, $sent)]));
            }
        }

        $rule = (array) ($rules['push_failure_rate'] ?? []);
        $attempts = (int) $metrics['delivery']['push_attempts'];
        $failed = (int) $metrics['delivery']['push_failed'];
        if ($attempts < max(1, (int) ($rule['minimum_attempts'] ?? 1))) {
            $flags[] = $this->flag('push_failure_rate', 'Push failures', 'unknown', __('Too few push notifications in 30 days to judge (:count).', ['count' => $attempts]));
        } else {
            $flags[] = $this->flag('push_failure_rate', 'Push failures', $this->reaches($failed, $attempts, (float) ($rule['percent'] ?? 10)) ? 'flagged' : 'ok',
                __(':failed of :attempts push attempts failed in 30 days (:rate%).', ['failed' => $failed, 'attempts' => $attempts, 'rate' => $this->rate($failed, $attempts)]));
        }

        $rule = (array) ($rules['ai_spend'] ?? []);
        $cap = $metrics['ai']['cap_cents'];
        $spent = (int) $metrics['ai']['spent_cents'];
        if (! is_int($cap) || $cap < 1) {
            $flags[] = $this->flag('ai_spend', 'AI spend', 'unknown', __('No AI cap is set for this school.'));
        } else {
            $flags[] = $this->flag('ai_spend', 'AI spend', $this->reaches($spent, $cap, (float) ($rule['percent'] ?? 90)) ? 'flagged' : 'ok',
                __(':share% of this month\'s AI cap used.', ['share' => intdiv($spent * 100, $cap)]));
        }

        $rule = (array) ($rules['failed_jobs'] ?? []);
        $jobs = (int) $metrics['failed_jobs']['count'];
        $flags[] = $this->flag('failed_jobs', 'Failed jobs', $jobs >= max(1, (int) ($rule['count'] ?? 10)) ? 'flagged' : 'ok',
            __(':count of the school\'s queued jobs failed in 30 days.', ['count' => $jobs]));

        return $flags;
    }

    /**
     * The school's weekly active families of the four weeks before $today:
     * the nightly figures dated 7 to 28 days back (each counts the 7 days up
     * to its date, so together they cover the 4 weeks before this one).
     *
     * @return list<int>
     */
    public function history(int $schoolId, string $today): array
    {
        $day = CarbonImmutable::parse($today);

        return $this->currentSchool->run($schoolId, fn (): array => SchoolHealthSnapshot::query()
            ->whereBetween('snapshot_date', [$day->subDays(28)->toDateString(), $day->subDays(7)->toDateString()])
            ->orderBy('snapshot_date')->pluck('active_guardians')->map(fn ($value) => (int) $value)->values()->all());
    }

    /** People with an active membership of the school today (their account switched on). */
    private function members(int $schoolId, string $today): Builder
    {
        return DB::table('school_memberships')->join('users', 'users.id', '=', 'school_memberships.user_id')
            ->where('school_memberships.school_id', $schoolId)->where('school_memberships.status', SchoolMembership::ACTIVE)
            ->where('users.status', 1)->whereNull('users.deleted_at')
            ->where(fn ($q) => $q->whereNull('school_memberships.starts_on')->orWhere('school_memberships.starts_on', '<=', $today))
            ->where(fn ($q) => $q->whereNull('school_memberships.ends_on')->orWhere('school_memberships.ends_on', '>=', $today));
    }

    /**
     * When each member of the school last signed in to or used an app
     * (personal_access_tokens: created on sign-in, last_used_at on use) or
     * registered a device for push. Two grouped queries.
     *
     * @return array<int, CarbonImmutable>
     */
    private function lastActive(int $schoolId, string $today): array
    {
        $members = fn () => $this->members($schoolId, $today)->select('school_memberships.user_id');
        $latest = [];
        $tokens = DB::table('personal_access_tokens')->where('tokenable_type', (new User)->getMorphClass())->whereIn('tokenable_id', $members())
            ->groupBy('tokenable_id')->selectRaw('tokenable_id AS user_id, MAX(created_at) AS signed_in, MAX(last_used_at) AS used')->get();
        $devices = DB::table('user_devices')->whereIn('user_id', $members())->whereNotNull('last_seen_at')
            ->groupBy('user_id')->selectRaw('user_id, MAX(last_seen_at) AS signed_in, NULL AS used')->get();
        foreach ([...$tokens, ...$devices] as $row) {
            foreach ([$row->signed_in, $row->used] as $at) {
                if ($at === null) {
                    continue;
                }
                $time = CarbonImmutable::parse((string) $at);
                $id = (int) $row->user_id;
                if (! isset($latest[$id]) || $time->greaterThan($latest[$id])) {
                    $latest[$id] = $time;
                }
            }
        }

        return $latest;
    }

    /**
     * Weekly active families and guardian reach. A learner's guardians are the
     * people GuardianService::guardianUserIds() counts (active links, and
     * legacy parents with no link row); a guardian counts while their account
     * is on and they belong to the school (FamilyReach, Action Plan 2.6). A
     * guardian is reachable by a verified email address or an active push
     * device (PushDevices).
     *
     * @param  array<int, CarbonImmutable>  $lastActive
     * @return array{families: array{guardians: int, active: int}, reach: array{learners: int, reached: int, by_email: int, by_push: int}}
     */
    private function families(int $schoolId, string $today, array $lastActive, CarbonImmutable $since): array
    {
        $learners = DB::table('students')->join('users', 'users.id', '=', 'students.user_id')
            ->where('students.school_id', $schoolId)->whereNull('students.deleted_at')->where('users.status', 1)->whereNull('users.deleted_at')
            ->whereIn('students.user_id', $this->members($schoolId, $today)->where('school_memberships.role', 'Student')->select('school_memberships.user_id'))
            ->get(['students.id', 'students.father_id', 'students.mother_id', 'students.guardian_id']);
        $learnerIds = $learners->pluck('id')->map(fn ($id) => (int) $id)->all();
        $linked = [];
        $anyLink = [];
        foreach (array_chunk($learnerIds, 1000) as $chunk) {
            foreach (DB::table('guardian_links')->where('school_id', $schoolId)->whereIn('learner_id', $chunk)->get(['learner_id', 'guardian_user_id', 'status']) as $link) {
                $anyLink[(int) $link->learner_id][(int) $link->guardian_user_id] = true;
                if ($link->status === 'active') {
                    $linked[(int) $link->learner_id][] = (int) $link->guardian_user_id;
                }
            }
        }
        $parentIds = $learners->flatMap(fn ($learner) => array_filter([$learner->father_id, $learner->mother_id, $learner->guardian_id]))->unique()->values()->all();
        $parentUsers = [];
        foreach (array_chunk($parentIds, 1000) as $chunk) {
            $parentUsers += DB::table('parents')->where('school_id', $schoolId)->whereNull('deleted_at')->whereIn('id', $chunk)->pluck('user_id', 'id')->all();
        }
        $guardiansOf = [];
        foreach ($learners as $learner) {
            $own = $linked[(int) $learner->id] ?? [];
            foreach (array_filter([$learner->father_id, $learner->mother_id, $learner->guardian_id]) as $parentId) {
                $user = (int) ($parentUsers[$parentId] ?? 0);
                if ($user > 0 && ! isset($anyLink[(int) $learner->id][$user])) {
                    $own[] = $user;
                }
            }
            $guardiansOf[(int) $learner->id] = array_values(array_unique($own));
        }

        $candidates = array_values(array_unique(array_merge([], ...array_values($guardiansOf))));
        $eligible = [];
        $push = [];
        $morph = (new User)->getMorphClass();
        foreach (array_chunk($candidates, 1000) as $chunk) {
            // Whether the address is verified, never the address itself.
            foreach ($this->members($schoolId, $today)->whereIn('school_memberships.user_id', $chunk)->distinct()
                ->selectRaw("users.id AS id, CASE WHEN users.email_verified_at IS NOT NULL AND users.email LIKE '%_@_%' THEN 1 ELSE 0 END AS verified")->get() as $row) {
                $eligible[(int) $row->id] = (bool) $row->verified;
            }
            // An active device, as PushDevices::activeQuery() decides it for one account.
            $push += DB::table('user_devices')->whereIn('user_id', $chunk)->whereNull('revoked_at')->whereIn('platform', PushDevices::PLATFORMS)
                ->where(function ($query) use ($morph) {
                    $query->whereExists(fn ($tokens) => $tokens->selectRaw('1')->from('personal_access_tokens')
                        ->whereColumn('personal_access_tokens.id', 'user_devices.personal_access_token_id'))
                        ->orWhere(fn ($unbound) => $unbound->whereNull('personal_access_token_id')
                            ->whereExists(fn ($tokens) => $tokens->selectRaw('1')->from('personal_access_tokens')
                                ->where('personal_access_tokens.tokenable_type', $morph)
                                ->whereColumn('personal_access_tokens.tokenable_id', 'user_devices.user_id')
                                ->whereColumn('personal_access_tokens.created_at', '<=', 'user_devices.created_at')));
                })->distinct()->pluck('user_id')->mapWithKeys(fn ($id) => [(int) $id => true])->all();
        }

        $reach = ['learners' => count($learnerIds), 'reached' => 0, 'by_email' => 0, 'by_push' => 0];
        foreach ($guardiansOf as $ids) {
            $email = false;
            $device = false;
            foreach ($ids as $id) {
                if (! array_key_exists($id, $eligible)) {
                    continue;
                }
                $email = $email || $eligible[$id];
                $device = $device || isset($push[$id]);
            }
            $reach['by_email'] += (int) $email;
            $reach['by_push'] += (int) $device;
            $reach['reached'] += (int) ($email || $device);
        }
        $active = count(array_filter(array_keys($eligible), fn (int $id) => isset($lastActive[$id]) && $lastActive[$id]->greaterThanOrEqualTo($since)));

        return ['families' => ['guardians' => count($eligible), 'active' => $active], 'reach' => $reach];
    }

    /**
     * People of each role group who signed in to or used an app in the last 7
     * and 30 days, of the people holding that role. Someone with two roles
     * counts in both.
     *
     * @param  array<int, CarbonImmutable>  $lastActive
     * @return array<string, array{people: int, days_7: int, days_30: int}>
     */
    private function signIns(int $schoolId, string $today, array $lastActive, CarbonImmutable $now): array
    {
        $groups = array_fill_keys(self::ROLE_GROUPS, []);
        foreach ($this->members($schoolId, $today)->distinct()->get(['school_memberships.user_id', 'school_memberships.role']) as $row) {
            $group = in_array($row->role, ['Parent', 'Student', 'Teacher'], true) ? $row->role : 'Staff';
            $groups[$group][(int) $row->user_id] = true;
        }
        $result = [];
        foreach ($groups as $group => $ids) {
            $result[$group] = ['people' => count($ids), 'days_7' => 0, 'days_30' => 0];
            foreach (array_keys($ids) as $id) {
                $at = $lastActive[$id] ?? null;
                $result[$group]['days_7'] += (int) ($at !== null && $at->greaterThanOrEqualTo($now->subDays(7)));
                $result[$group]['days_30'] += (int) ($at !== null && $at->greaterThanOrEqualTo($now->subDays(30)));
            }
        }

        return $result;
    }

    /**
     * The notification type a delivery row was sent for. The transports log
     * their channel in front of it (MailService: mail.comms.notice, PushQueue:
     * push.comms.message), while config/notification_types.php has the bare type.
     */
    private static function notificationType(string $type): string
    {
        foreach ([DeliveryLog::MAIL, DeliveryLog::PUSH] as $channel) {
            if (str_starts_with($type, $channel.'.')) {
                return substr($type, strlen($channel) + 1);
            }
        }

        return $type;
    }

    /**
     * Modules on, each with the messages it sent and the AI requests it made
     * in the last 30 days (none counted for sensitive modules).
     *
     * @return array{on: int, list: list<array{key: string, name: string, read_only: bool, sensitive: bool, messages: ?int, ai_requests: ?int}>}
     */
    private function modules(int $schoolId, CarbonImmutable $since): array
    {
        $types = (array) config('notification_types', []);
        $messages = [];
        foreach (DB::table('notification_deliveries')->where('school_id', $schoolId)->where('created_at', '>=', $since)
            ->groupBy('type')->selectRaw('type, COUNT(DISTINCT delivery_key) AS messages')->get() as $row) {
            $module = $types[self::notificationType((string) $row->type)]['module'] ?? null;
            if (is_string($module)) {
                $messages[$module] = ($messages[$module] ?? 0) + (int) $row->messages;
            }
        }
        $ai = [];
        foreach (DB::table('ai_usage')->where('school_id', $schoolId)->where('created_at', '>=', $since)
            ->groupBy('feature')->selectRaw('feature, COUNT(*) AS requests')->get() as $row) {
            $module = self::AI_FEATURE_MODULES[$row->feature] ?? null;
            if ($module !== null) {
                $ai[$module] = ($ai[$module] ?? 0) + (int) $row->requests;
            }
        }
        $list = [];
        foreach (app(PlatformSchools::class)->modules($schoolId) as $key => $module) {
            if (! ($module['enabled'] ?? false)) {
                continue;
            }
            $sensitive = in_array($key, self::SENSITIVE_MODULES, true);
            $list[] = ['key' => (string) $key, 'name' => (string) ($module['name'] ?? $key), 'read_only' => (bool) ($module['read_only'] ?? false), 'sensitive' => $sensitive,
                'messages' => $sensitive ? null : ($messages[$key] ?? 0), 'ai_requests' => $sensitive ? null : ($ai[$key] ?? 0)];
        }

        return ['on' => count($list), 'list' => $list];
    }

    /**
     * Email and push delivery attempts of the last 30 days (one row per
     * attempt; a bounce or complaint is the provider's later report on a sent
     * email).
     *
     * @return array{mail_sent: int, mail_bounced: int, mail_complained: int, mail_failed: int, push_attempts: int, push_failed: int}
     */
    private function delivery(int $schoolId, CarbonImmutable $since): array
    {
        $sent = [NotificationDelivery::SENT, NotificationDelivery::DELIVERED, NotificationDelivery::BOUNCED, NotificationDelivery::COMPLAINED];
        $marks = implode(',', array_fill(0, count($sent), '?'));
        $row = DB::table('notification_deliveries')->where('school_id', $schoolId)->where('created_at', '>=', $since)->selectRaw(
            "COALESCE(SUM(CASE WHEN channel = 'mail' AND status IN ($marks) THEN 1 ELSE 0 END), 0) AS mail_sent,
             COALESCE(SUM(CASE WHEN channel = 'mail' AND status = ? THEN 1 ELSE 0 END), 0) AS mail_bounced,
             COALESCE(SUM(CASE WHEN channel = 'mail' AND status = ? THEN 1 ELSE 0 END), 0) AS mail_complained,
             COALESCE(SUM(CASE WHEN channel = 'mail' AND status = ? THEN 1 ELSE 0 END), 0) AS mail_failed,
             COALESCE(SUM(CASE WHEN channel = 'push' AND status IN (?, ?, ?) THEN 1 ELSE 0 END), 0) AS push_attempts,
             COALESCE(SUM(CASE WHEN channel = 'push' AND status = ? THEN 1 ELSE 0 END), 0) AS push_failed",
            [...$sent, NotificationDelivery::BOUNCED, NotificationDelivery::COMPLAINED, NotificationDelivery::FAILED,
                NotificationDelivery::SENT, NotificationDelivery::DELIVERED, NotificationDelivery::FAILED, NotificationDelivery::FAILED],
        )->first();

        return array_map(fn ($value) => (int) $value, (array) $row);
    }

    /**
     * AI spend this month (spent and reserved, in the AI billing currency's
     * cents) against the most the school's daily cap allows this month: the
     * school's own daily cap where it set one, never above the largest
     * feature's, times the days in the month.
     *
     * @return array{spent_cents: int, cap_cents: ?int, daily_cap_cents: ?int, currency: string, other_currency: bool, paused: bool, enabled: bool}
     */
    private function ai(int $schoolId, CarbonImmutable $local): array
    {
        // Only spend in the cap's currency counts against it: receipts from before a change of
        // billing currency (or with none recorded) are never added to it, only noted.
        $currency = (string) config('ai.billing_currency', 'USD');
        $month = DB::table('ai_usage')->where('school_id', $schoolId)
            ->whereBetween('usage_date', [$local->startOfMonth()->toDateString(), $local->toDateString()]);
        $spent = (int) (clone $month)->where('billing_currency', $currency)->sum(DB::raw('cost_cents + reserved_cost_cents'));
        $otherCurrency = (clone $month)->where(fn ($q) => $q->whereNull('billing_currency')->orWhere('billing_currency', '!=', $currency))
            ->where(fn ($q) => $q->where('cost_cents', '>', 0)->orWhere('reserved_cost_cents', '>', 0))->exists();
        $configured = null;
        foreach ((array) config('ai.inference.features', []) as $feature) {
            $value = is_array($feature) ? ($feature['school_daily_cents'] ?? null) : null;
            if (is_int($value) && $value > 0) {
                $configured = max($configured ?? 0, $value);
            }
        }
        $row = DB::table('ai_school_limits')->where('school_id', $schoolId)->first(['paused', 'limits']);
        $limits = $row === null ? [] : (array) json_decode((string) $row->limits, true);
        $own = $limits['school_daily_cents'] ?? null;
        $daily = is_int($own) ? ($configured === null ? $own : min($own, $configured)) : $configured;

        return [
            'spent_cents' => $spent,
            'cap_cents' => $daily === null ? null : $daily * $local->daysInMonth,
            'daily_cap_cents' => $daily,
            'currency' => $currency,
            'other_currency' => $otherCurrency,
            'paused' => (bool) ($row->paused ?? false),
            'enabled' => (bool) config('ai.inference.enabled', false),
        ];
    }

    /**
     * Queued jobs of the school that failed in the last 30 days, by kind of
     * job. A job carries its school in its Laravel context (CurrentSchool);
     * the exception text is never read, as it can hold personal information.
     *
     * @return array{count: int, by_job: array<string, int>}
     */
    private function failedJobs(int $schoolId, CarbonImmutable $since): array
    {
        // The JSON payload holds "illuminate:log:context":{"data":{"school_id":"i:5;"}}
        // (each context value is serialised). The underscore is escaped for LIKE.
        $marker = '%"school\\_id":"'.serialize($schoolId).'"%';
        $query = fn () => DB::table('failed_jobs')->where('failed_at', '>=', $since)->where('payload', 'like', $marker);
        $byJob = [];
        foreach ($query()->orderByDesc('id')->limit(self::FAILED_JOB_NAMES_READ)->pluck('payload') as $payload) {
            $name = json_decode((string) $payload, true)['displayName'] ?? null;
            $name = is_string($name) && $name !== '' ? class_basename($name) : 'Unknown';
            $byJob[$name] = ($byJob[$name] ?? 0) + 1;
        }
        arsort($byJob);

        return ['count' => $query()->count(), 'by_job' => $byJob];
    }

    /**
     * The comparison page's headline figures from one night's metrics.
     *
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    private function headline(array $metrics): array
    {
        $families = (array) ($metrics['families'] ?? []);
        $reach = (array) ($metrics['reach'] ?? []);
        $delivery = (array) ($metrics['delivery'] ?? []);
        $ai = (array) ($metrics['ai'] ?? []);

        return [
            'guardians' => (int) ($families['guardians'] ?? 0), 'active' => (int) ($families['active'] ?? 0),
            'active_share' => $this->share((int) ($families['active'] ?? 0), (int) ($families['guardians'] ?? 0)),
            'learners' => (int) ($reach['learners'] ?? 0), 'reached' => (int) ($reach['reached'] ?? 0),
            'reach_share' => $this->share((int) ($reach['reached'] ?? 0), (int) ($reach['learners'] ?? 0)),
            'mail_sent' => (int) ($delivery['mail_sent'] ?? 0),
            'bounce_basis' => (int) ($delivery['mail_sent'] ?? 0) === 0 ? null : intdiv((int) ($delivery['mail_bounced'] ?? 0) * 10000, (int) $delivery['mail_sent']),
            'bounce_rate' => $this->rate((int) ($delivery['mail_bounced'] ?? 0), (int) ($delivery['mail_sent'] ?? 0)),
            'push_rate' => $this->rate((int) ($delivery['push_failed'] ?? 0), (int) ($delivery['push_attempts'] ?? 0)),
            'ai_share' => is_int($ai['cap_cents'] ?? null) && $ai['cap_cents'] > 0 ? intdiv((int) ($ai['spent_cents'] ?? 0) * 100, $ai['cap_cents']) : null,
            'modules_on' => (int) (($metrics['modules'] ?? [])['on'] ?? 0),
            'failed_jobs' => (int) (($metrics['failed_jobs'] ?? [])['count'] ?? 0),
        ];
    }

    /** The order of two comparison rows; schools without figures come last. */
    private function order(string $sort, array $a, array $b): int
    {
        $byName = [mb_strtolower($a['name']), $a['id']] <=> [mb_strtolower($b['name']), $b['id']];
        if ($sort === 'name') {
            return $byName;
        }
        if (($a['headline'] === null) !== ($b['headline'] === null)) {
            return $a['headline'] === null ? 1 : -1;
        }
        $key = fn (array $row): array => match ($sort) {
            'active' => [$row['headline']['active_share'] ?? 101],
            'reach' => [$row['headline']['reach_share'] ?? 101],
            'bounce' => [-1 * ($row['headline']['bounce_basis'] ?? -1)],
            default => [-1 * $row['flag_count']],
        };

        return ($key($a) <=> $key($b)) ?: $byName;
    }

    /** Whether $count is at least $percent% of $of, in whole-number arithmetic. */
    private function reaches(int $count, int $of, float $percent): bool
    {
        return $count * 10000 >= (int) round($percent * 100) * $of;
    }

    private function share(int $count, int $of): ?int
    {
        return $of === 0 ? null : intdiv($count * 100, $of);
    }

    /** A percentage with one decimal, as text ("4.8"). */
    private function rate(int $count, int $of): string
    {
        if ($of === 0) {
            return '0';
        }
        $tenths = intdiv($count * 1000, $of);

        return intdiv($tenths, 10).'.'.($tenths % 10);
    }

    private function average(int $sum, int $n): string
    {
        $tenths = intdiv($sum * 10, max(1, $n));

        return $tenths % 10 === 0 ? (string) intdiv($tenths, 10) : intdiv($tenths, 10).'.'.($tenths % 10);
    }

    /** @return array{key: string, label: string, state: string, detail: string} */
    private function flag(string $key, string $label, string $state, string $detail): array
    {
        return ['key' => $key, 'label' => __($label), 'state' => $state, 'detail' => $detail];
    }
}

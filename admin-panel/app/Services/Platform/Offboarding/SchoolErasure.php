<?php

declare(strict_types=1);

namespace App\Services\Platform\Offboarding;

use App\Models\Platform\School;
use App\Models\Platform\SchoolErasureCertificate;
use App\Models\Platform\SchoolOffboarding;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Erasing a leaving school's data (Action Plan SA.11), the last step of
 * offboarding, once two different people approved it and the cooling-off
 * period has passed (PlatformOffboarding::startErasure).
 *
 * What may be deleted is exactly OffboardingDeletionPlan::targets(): the
 * school's rows of every school-owned table (SchoolOwnedTables), and nothing
 * with another school's id or none. Before anything is deleted, and again each
 * time a run resumes, preflight() checks that no other school's row, no kept
 * record and no platform row refers to a row in scope; any such reference
 * stops the erasure before a row is touched. A row is deleted only when no row
 * anywhere still refers to it, so no delete ever cascades into, or is refused
 * because of, another row.
 *
 * History the database keeps immutable (money, ledger and similar tables whose
 * triggers refuse every delete) is never deleted, and the rows it refers to
 * stay with it. The certificate counts what was held back and why.
 *
 * Accounts only this school knows are erased in place (names, contact
 * details, sign-in, devices and preferences removed; the row stays, soft
 * deleted, so the kept audit log still points at an id). People who also
 * belong to another school keep their account and lose only this school's
 * membership.
 *
 * The work is done in small batches, each in its own transaction with the
 * progress (counts per table) saved with it, so an interrupted erasure resumes
 * where it stopped and never counts a row twice. When the last step is done
 * the export copy is discarded and an erasure certificate is issued: counts,
 * dates and audit entry ids only, never personal information. The audit log
 * and Navigate Ed's own records of the school are kept.
 * engineering/specs/platform/offboarding.md.
 */
class SchoolErasure
{
    public const FORMAT = 'navigateed-school-erasure-certificate-v1';

    /** The step that erases accounts only this school knows. */
    public const PEOPLE = '@people';

    /** The first step: clears the links between the school's own rows that refer to each other in a cycle. */
    public const DETACH = '@detach';

    /** The step that discards the stored export archive. */
    public const EXPORT_COPY = '@export_copy';

    /** Marks an erased account (and keeps the unique email column unique). */
    public const ERASED_EMAIL_DOMAIN = 'erased.invalid';

    /** Per-person platform rows removed with an erased account: table => the column naming the person. */
    public const PERSONAL_ROWS = [
        'sessions' => 'user_id',
        'user_devices' => 'user_id',
        'notification_preferences' => 'user_id',
        'notification_quiet_hours' => 'user_id',
        'family_summary_preferences' => 'user_id',
        'family_daily_summaries' => 'user_id',
        'policy_acceptances' => 'user_id',
        'handle_claims' => 'user_id',
        'handle_changes' => 'user_id',
    ];

    /** @var list<array{table: string, foreign_table: string, columns: list<array{0: string, 1: string}>, on_delete: string}>|null */
    private ?array $keys = null;

    /** @var array<string, true>|null "table.column" for every column that may be null */
    private ?array $nullable = null;

    /** @var array<int, array{0: list<string>, 1: array<string, list<string>>}> order() per school, worked out once per run */
    private array $orders = [];

    public function __construct(
        private readonly CurrentSchool $currentSchool,
        private readonly OffboardingDeletionPlan $plan,
        private readonly OffboardingExport $export,
    ) {}

    /**
     * The steps in order: first unlinking the school's rows that refer to
     * each other in a cycle, then every target table after the tables that
     * refer to it, the people step just before the memberships it reads (an
     * account is erased only while its membership still shows it belongs here
     * alone), and the export copy last. Tables the database keeps immutable
     * are not steps: nothing is deleted from them.
     *
     * @return list<string>
     */
    public function steps(int $schoolId): array
    {
        [$order] = $this->order($schoolId);
        $at = array_search('school_memberships', $order, true);
        array_splice($order, $at === false ? count($order) : (int) $at, 0, [self::PEOPLE]);

        return [self::DETACH, ...$order, self::EXPORT_COPY];
    }

    /**
     * What would make the erasure touch anything but the school's own rows:
     * a row of another school, a kept record or a platform row that refers to
     * one of the school's rows in scope. Each problem is a code
     * (table.column→table); none means the erasure may go on. References
     * that carry the school's id in the key itself cannot cross schools and
     * are not checked.
     *
     * @return list<string>
     */
    public function preflight(int $schoolId): array
    {
        $targets = $this->plan->targets($schoolId);
        $withSchool = SchoolOwnedTables::withSchoolId();
        $problems = [];
        foreach ($this->foreignKeys() as $key) {
            $parent = $targets[$key['foreign_table']] ?? null;
            if ($parent === null || count($key['columns']) !== 1) {
                continue;
            }
            [$column, $foreign] = $key['columns'][0];
            // The referring rows under an alias, so a table that refers to its own rows (a group's parent group) is checked too.
            $referrers = DB::table($key['table'].' as referrer')->whereIn('referrer.'.$column, (clone $parent)->select($key['foreign_table'].'.'.$foreign));
            if (isset($targets[$key['table']])) {
                // A row that is itself in scope is fine; one of another school (or of none) is not.
                if ($key['table'] === 'role_has_permissions' || ! in_array($key['table'], $withSchool, true)) {
                    continue;
                }
                $referrers->where(fn (Builder $query) => $query->where('referrer.school_id', '!=', $schoolId)->orWhereNull('referrer.school_id'));
            }
            if ($referrers->exists()) {
                $problems[] = $key['table'].'.'.$column.'→'.$key['foreign_table'];
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * Run the erasure of an offboarding that is erasing, inside its school,
     * a batch at a time until it is done, $batches batches have run, or
     * $seconds have passed. Safe to run again, or twice at once: each batch
     * locks the offboarding and re-reads its progress.
     *
     * @return bool whether the school is now erased
     */
    public function run(int $offboardingId, ?int $batches = null, int $seconds = 100): bool
    {
        $schoolId = $this->currentSchool->id();
        $problems = $this->preflight($schoolId);
        if ($problems !== []) {
            $this->stop($offboardingId, 'blocked:'.$problems[0]);

            return false;
        }
        $steps = $this->steps($schoolId);
        $until = microtime(true) + $seconds;
        $ran = 0;
        while (($batches === null || $ran < $batches) && microtime(true) < $until) {
            $outcome = $this->batch($offboardingId, $steps);
            $ran++;
            if ($outcome !== 'more') {
                return $outcome === 'erased';
            }
        }

        return false;
    }

    /** Whether the offboarding is still erasing and not stopped by a problem (so another run would make progress). */
    public function erasing(int $offboardingId): bool
    {
        $row = SchoolOffboarding::query()->find($offboardingId);

        return $row !== null && $row->state === SchoolOffboarding::ERASING && $row->erasure_failure_code === null;
    }

    /**
     * One batch of the current step, with its progress, in one transaction.
     *
     * @param  list<string>  $steps
     * @return string 'more', 'erased' or 'stopped'
     */
    private function batch(int $offboardingId, array $steps): string
    {
        $schoolId = $this->currentSchool->id();

        return DB::transaction(function () use ($offboardingId, $steps, $schoolId): string {
            School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();
            $row = SchoolOffboarding::query()->lockForUpdate()->find($offboardingId);
            if ($row === null || $row->state !== SchoolOffboarding::ERASING) {
                return $row?->state === SchoolOffboarding::ERASED ? 'erased' : 'stopped';
            }
            $progress = $this->progress($row);
            $step = null;
            foreach ($steps as $candidate) {
                if (! in_array($candidate, $progress['completed'], true)) {
                    $step = $candidate;
                    break;
                }
            }
            if ($step === null) {
                $this->finish($row, $progress);

                return 'erased';
            }
            $size = max(1, (int) config('offboarding.erasure_batch_size'));
            [$done, $count] = match ($step) {
                self::DETACH => $this->detach($schoolId),
                self::PEOPLE => $this->erasePeople($schoolId, $size),
                self::EXPORT_COPY => $this->discardExport($row),
                default => $this->eraseRows($schoolId, $step, $size),
            };
            if ($step === self::PEOPLE) {
                $progress['accounts_erased'] += $count;
            } elseif ($step !== self::EXPORT_COPY && $step !== self::DETACH) {
                $progress['rows'][$step] = ($progress['rows'][$step] ?? 0) + $count;
            }
            if ($done) {
                $progress['completed'][] = $step;
            }
            $row->forceFill(['erasure_progress' => $progress, 'erasure_step' => $step, 'erasure_heartbeat_at' => now(), 'erasure_failure_code' => null])->save();

            return 'more';
        });
    }

    /**
     * Clears the links that make the school's own rows refer to each other in
     * a cycle (a profile and its current version), and rows that refer to rows
     * of their own table, so each can then be deleted.
     *
     * @return array{0: bool, 1: int}
     */
    private function detach(int $schoolId): array
    {
        [, $detach] = $this->order($schoolId);
        $targets = $this->plan->targets($schoolId);
        foreach ($detach as $table => $columns) {
            foreach ($columns as $column) {
                (clone $targets[$table])->whereNotNull($table.'.'.$column)->update([$column => null]);
            }
        }

        return [true, 0];
    }

    /**
     * Deletes up to $size of the school's rows from one table: only rows no
     * other row refers to any more.
     *
     * @return array{0: bool, 1: int} whether nothing more can go from this table, and how many went
     */
    private function eraseRows(int $schoolId, string $table, int $size): array
    {
        $query = $this->plan->targets($schoolId)[$table] ?? null;
        if ($query === null) {
            return [true, 0];
        }
        $query = clone $query;
        foreach ($this->foreignKeys() as $key) {
            if ($key['foreign_table'] !== $table || $key['table'] === $table) {
                continue;
            }
            $query->whereNotExists(function (Builder $refers) use ($key, $table): void {
                $refers->from($key['table'])->selectRaw('1');
                foreach ($key['columns'] as [$column, $foreign]) {
                    $refers->whereColumn($key['table'].'.'.$column, $table.'.'.$foreign);
                }
            });
        }
        // Links between rows of this table that could not be cleared first (a column that may not be null): delete the rows nothing in the table refers to, a layer at a time.
        [, $detach] = $this->order($schoolId);
        $nullable = $this->nullableColumns();
        $selfKeys = array_values(array_filter($this->foreignKeys(), fn (array $key): bool => $key['table'] === $table && $key['foreign_table'] === $table
            && array_filter(array_column($key['columns'], 0), fn (string $column): bool => $column !== 'school_id' && ! isset($nullable[$table.'.'.$column]) && ! in_array($column, $detach[$table] ?? [], true)) !== []));
        if ($selfKeys === []) {
            $deleted = $query->limit($size)->delete();

            return [$deleted < $size, $deleted];
        }
        foreach ($selfKeys as $key) {
            $query->whereNotExists(function (Builder $refers) use ($key, $table): void {
                $refers->from($table.' as child')->selectRaw('1');
                foreach ($key['columns'] as [$column, $foreign]) {
                    $refers->whereColumn('child.'.$column, $table.'.'.$foreign);
                }
            });
        }
        $ids = (clone $query)->orderBy($table.'.id')->limit($size)->pluck($table.'.id')->all();
        if ($ids === []) {
            return [true, 0];
        }
        $deleted = DB::table($table)->whereIn('id', $ids)->delete();

        return [false, $deleted];
    }

    /**
     * Erases up to $size accounts that only this school knows, and are not
     * Navigate Ed staff, in place.
     *
     * @return array{0: bool, 1: int}
     */
    private function erasePeople(int $schoolId, int $size): array
    {
        $ids = DB::table('school_memberships as m')->where('m.school_id', $schoolId)
            ->whereNotExists(fn (Builder $query) => $query->from('school_memberships as o')->selectRaw('1')->whereColumn('o.user_id', 'm.user_id')->where('o.school_id', '!=', $schoolId))
            ->whereNotIn('m.user_id', DB::table('platform_staff_identities')->select('user_id'))
            ->whereNotIn('m.user_id', DB::table('platform_admin_grants')->select('user_id'))
            ->join('users', 'users.id', '=', 'm.user_id')->where('users.email', 'not like', 'erased-%@'.self::ERASED_EMAIL_DOMAIN)
            ->distinct()->orderBy('m.user_id')->limit($size)->pluck('m.user_id')->map(fn ($id): int => (int) $id)->all();
        if ($ids === []) {
            return [true, 0];
        }
        $emails = DB::table('users')->whereIn('id', $ids)->pluck('email')->all();
        // Devices first: they point at the sign-in tokens.
        foreach (self::PERSONAL_ROWS as $table => $column) {
            DB::table($table)->whereIn($column, $ids)->delete();
        }
        DB::table('personal_access_tokens')->where('tokenable_type', (new User)->getMorphClass())->whereIn('tokenable_id', $ids)->delete();
        DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
        DB::table('password_resets')->whereIn('email', $emails)->delete();
        $now = now();
        foreach ($ids as $id) {
            DB::table('users')->where('id', $id)->update([
                'first_name' => 'Erased', 'last_name' => 'account', 'email' => 'erased-'.$id.'@'.self::ERASED_EMAIL_DOMAIN,
                'gender' => null, 'mobile' => null, 'image' => null, 'dob' => null, 'current_address' => null, 'permanent_address' => null,
                'fcm_id' => null, 'device_type' => null, 'remember_token' => null, 'handle' => null, 'email_verified_at' => null,
                // Not a password hash: nothing can sign in as this account.
                'password' => '!erased', 'status' => 0, 'deleted_at' => $now, 'updated_at' => $now,
            ]);
        }

        return [count($ids) < $size, count($ids)];
    }

    /** @return array{0: bool, 1: int} */
    private function discardExport(SchoolOffboarding $row): array
    {
        $this->export->discard($row);
        $row->forceFill(['artifact_path' => null]);

        return [true, 0];
    }

    /**
     * Every step is done: issue the certificate and mark the school erased.
     *
     * @param  array{completed: list<string>, rows: array<string, int>, accounts_erased: int}  $progress
     */
    private function finish(SchoolOffboarding $row, array $progress): void
    {
        $schoolId = (int) $row->school_id;
        $erased = array_filter($progress['rows']);
        ksort($erased);
        $held = [];
        $immutable = array_flip($this->plan->protectedTables());
        foreach ($this->plan->targets($schoolId) as $table => $query) {
            $left = $query->count();
            if ($left > 0) {
                $held[$table] = ['rows' => $left, 'reason' => isset($immutable[$table])
                    ? 'history the database keeps immutable (money, ledger and similar records)'
                    : 'referred to by history the database keeps immutable'];
            }
        }
        ksort($held);
        $audit = fn (string $action): ?int => ($id = DB::table('audit_log')->where('school_id', $schoolId)->where('subject_type', $row->getMorphClass())
            ->where('subject_id', $row->id)->where('action', $action)->max('id')) === null ? null : (int) $id;
        $finishedAt = CarbonImmutable::now();
        $reference = 'ERC-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);
        $body = [
            'format' => self::FORMAT,
            'reference' => $reference,
            'offboarding' => 'OFF-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT),
            'school' => ['id' => $schoolId, 'name' => (string) DB::table('schools')->where('id', $schoolId)->value('name')],
            'retention' => ['source' => $row->retention_source, 'delete_on' => $row->delete_on->toDateString()],
            'approvals' => [
                'requested_at' => $row->deletion_requested_at?->toIso8601String(), 'approved_at' => $row->deletion_approved_at?->toIso8601String(),
                'different_people' => $row->deletion_requested_by !== null && (int) $row->deletion_requested_by !== (int) $row->deletion_approved_by,
                'cooling_off_hours' => (int) config('offboarding.erasure_cooling_off_hours'),
                'erasure_not_before' => $row->erasure_not_before?->toIso8601String(),
                'audit_entries' => [
                    'requested' => $audit('platform_school_offboarding_deletion_requested'),
                    'approved' => $audit('platform_school_offboarding_deletion_approved'),
                    'started' => $audit('platform_school_offboarding_erasure_started'),
                ],
            ],
            'export' => ['receipt' => $row->receipt_basis, 'sha256' => $row->artifact_sha256, 'copy' => 'discarded'],
            'started_at' => $row->erasure_started_at?->toIso8601String(),
            'finished_at' => $finishedAt->toIso8601String(),
            'runs' => (int) $row->erasure_runs,
            'erased' => ['rows' => $erased, 'rows_total' => array_sum($erased), 'tables' => count($erased), 'accounts' => $progress['accounts_erased']],
            'held' => $held,
            'kept' => $this->plan->kept($schoolId),
            'not_erased' => ['stored files' => 'uploaded files (documents, photos, report PDFs) are not erased in this step'],
        ];
        $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // Stored as these exact bytes (not a JSON column, which a database may reorder), so the hash always matches what is downloaded.
        $certificate = SchoolErasureCertificate::query()->create([
            'offboarding_id' => $row->id, 'reference' => $reference, 'body' => $json,
            'certificate_sha256' => hash('sha256', $json), 'issued_at' => $finishedAt,
        ]);
        $row->forceFill(['state' => SchoolOffboarding::ERASED, 'erased_at' => $finishedAt, 'erasure_step' => null, 'erasure_progress' => $progress, 'erasure_failure_code' => null])->save();
        Audit::record('platform_school_offboarding_erased', $row, ['state' => SchoolOffboarding::ERASING], ['offboarding_id' => (int) $row->id, 'state' => $row->state,
            'certificate' => $reference, 'certificate_sha256' => $certificate->certificate_sha256, 'rows_erased' => array_sum($erased),
            'rows_held' => array_sum(array_column($held, 'rows')), 'accounts_erased' => $progress['accounts_erased']]);
    }

    /** Stops an erasing offboarding with a reason code; it can be resumed once the cause is fixed. */
    private function stop(int $offboardingId, string $code): void
    {
        $code = mb_substr($code, 0, 191);
        DB::transaction(function () use ($offboardingId, $code): void {
            School::query()->whereKey($this->currentSchool->id())->lockForUpdate()->firstOrFail();
            $row = SchoolOffboarding::query()->lockForUpdate()->find($offboardingId);
            if ($row === null || $row->state !== SchoolOffboarding::ERASING || $row->erasure_failure_code === $code) {
                return;
            }
            $row->forceFill(['erasure_failure_code' => $code, 'erasure_heartbeat_at' => now()])->save();
            Audit::record('platform_school_offboarding_erasure_blocked', $row, [], ['offboarding_id' => (int) $row->id, 'code' => $code]);
        });
    }

    /**
     * @return array{completed: list<string>, rows: array<string, int>, accounts_erased: int}
     */
    private function progress(SchoolOffboarding $row): array
    {
        $stored = is_array($row->erasure_progress) ? $row->erasure_progress : [];

        return [
            'completed' => array_values(array_map('strval', (array) ($stored['completed'] ?? []))),
            'rows' => array_map('intval', (array) ($stored['rows'] ?? [])),
            'accounts_erased' => (int) ($stored['accounts_erased'] ?? 0),
        ];
    }

    /**
     * The deletable target tables (not the immutable ones), each after every
     * table whose rows refer to it, and the columns to clear first: links to
     * rows of the same table, and one nullable link in each cycle of tables
     * that refer to each other.
     *
     * @return array{0: list<string>, 1: array<string, list<string>>}
     */
    private function order(int $schoolId): array
    {
        return $this->orders[$schoolId] ??= $this->workOutOrder($schoolId);
    }

    /** @return array{0: list<string>, 1: array<string, list<string>>} */
    private function workOutOrder(int $schoolId): array
    {
        $immutable = array_flip($this->plan->protectedTables());
        $tables = array_values(array_filter(array_keys($this->plan->targets($schoolId)), fn (string $table): bool => ! isset($immutable[$table])));
        $set = array_flip($tables);
        $nullable = $this->nullableColumns();
        $detach = [];
        // $waits[parent][child] = the links from child rows to parent rows.
        $waits = array_fill_keys($tables, []);
        foreach ($this->foreignKeys() as $key) {
            if (! isset($set[$key['table']], $set[$key['foreign_table']])) {
                continue;
            }
            $own = array_values(array_filter(array_column($key['columns'], 0), fn (string $column): bool => $column !== 'school_id'));
            if ($key['table'] === $key['foreign_table']) {
                foreach ($own as $column) {
                    if (isset($nullable[$key['table'].'.'.$column])) {
                        $detach[$key['table']][] = $column;
                    }
                }

                continue;
            }
            $waits[$key['foreign_table']][$key['table']][] = $own;
        }
        $order = [];
        $left = $set;
        while ($left !== []) {
            $ready = array_values(array_filter(array_keys($left), fn (string $table): bool => array_intersect_key($waits[$table], $left) === []));
            if ($ready === []) {
                // A cycle: clear one nullable link of a table still waiting, then go on.
                $broken = false;
                foreach (array_keys($left) as $parent) {
                    foreach (array_intersect_key($waits[$parent], $left) as $child => $links) {
                        $columns = [];
                        foreach ($links as $link) {
                            $clear = array_values(array_filter($link, fn (string $column): bool => isset($nullable[$child.'.'.$column])));
                            if ($clear === []) {
                                continue 2;
                            }
                            $columns = [...$columns, $clear[0]];
                        }
                        if ($columns !== []) {
                            $detach[$child] = [...($detach[$child] ?? []), ...$columns];
                            unset($waits[$parent][$child]);
                            $broken = true;
                            break 2;
                        }
                    }
                }
                if (! $broken) {
                    throw new RuntimeException('School erasure: school-owned tables refer to each other in a cycle with no link that can be cleared.');
                }

                continue;
            }
            sort($ready);
            foreach ($ready as $table) {
                $order[] = $table;
                unset($left[$table]);
            }
        }
        ksort($detach);

        return [$order, array_map(fn (array $columns): array => array_values(array_unique($columns)), $detach)];
    }

    /**
     * The database's foreign keys, each with its column pairs and delete
     * rule. Read in two queries (MariaDB is slow to join its information schema).
     *
     * @return list<array{table: string, foreign_table: string, columns: list<array{0: string, 1: string}>, on_delete: string}>
     */
    private function foreignKeys(): array
    {
        if ($this->keys !== null) {
            return $this->keys;
        }
        $database = DB::connection()->getDatabaseName();
        $rules = [];
        foreach (DB::select('select constraint_name, table_name, delete_rule from information_schema.referential_constraints where constraint_schema = ?', [$database]) as $rule) {
            $rule = array_change_key_case((array) $rule);
            $rules[$rule['table_name'].'|'.$rule['constraint_name']] = strtoupper((string) $rule['delete_rule']);
        }
        $keys = [];
        foreach (DB::select('select constraint_name, table_name, column_name, referenced_table_name, referenced_column_name, ordinal_position from information_schema.key_column_usage where table_schema = ? and referenced_table_name is not null order by table_name, constraint_name, ordinal_position', [$database]) as $column) {
            $column = array_change_key_case((array) $column);
            $id = $column['table_name'].'|'.$column['constraint_name'];
            $keys[$id] ??= ['table' => (string) $column['table_name'], 'foreign_table' => (string) $column['referenced_table_name'], 'columns' => [], 'on_delete' => $rules[$id] ?? 'RESTRICT'];
            $keys[$id]['columns'][] = [(string) $column['column_name'], (string) $column['referenced_column_name']];
        }

        return $this->keys = array_values($keys);
    }

    /** @return array<string, true> */
    private function nullableColumns(): array
    {
        if ($this->nullable !== null) {
            return $this->nullable;
        }
        $columns = DB::select("select table_name, column_name from information_schema.columns where table_schema = ? and is_nullable = 'YES'", [DB::connection()->getDatabaseName()]);
        $nullable = [];
        foreach ($columns as $column) {
            $column = array_change_key_case((array) $column);
            $nullable[$column['table_name'].'.'.$column['column_name']] = true;
        }

        return $this->nullable = $nullable;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Platform\Offboarding;

use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What erasing a leaving school removes and keeps (Action Plan SA.11), with
 * SchoolOwnedTables as the source of truth: every school-owned table's rows
 * of that school, less the records kept and why. The dry run (report) only
 * counts: it never deletes, changes or reads the content of a row, so HQ sees
 * numbers only. SchoolErasure deletes exactly the rows targets() selects.
 * engineering/specs/platform/offboarding.md.
 */
class OffboardingDeletionPlan
{
    /** Kept when the school's data is erased, with the reason. */
    public const KEPT = OffboardingExport::HQ_RECORDS + [
        'audit_log' => 'the audit records (who did what, and when) are kept as the law and the contract require',
        // Navigate Ed's own accounting with the school: tax records it must keep, not the school's records about its people.
        'platform_invoices' => 'Navigate Ed\'s invoices to the school, kept as tax law requires',
        'platform_invoice_lines' => 'the lines of Navigate Ed\'s invoices to the school, kept as tax law requires',
        'platform_payments' => 'the school\'s payments to Navigate Ed, kept as tax law requires',
        'platform_payment_allocations' => 'what each payment to Navigate Ed settled, kept as tax law requires',
        'platform_billing_reminders' => 'Navigate Ed\'s overdue reminders to the school, kept with the invoices they were about',
        'school_group_invoice_schools' => 'the school\'s part of a school group\'s invoice from Navigate Ed, kept as tax law requires',
        'school_group_invoice_lines' => 'the lines of the school\'s part of a group invoice, kept as tax law requires',
        // Accountability for Navigate Ed's own staff.
        'support_access_grants' => 'the record of Navigate Ed staff given support access to the school, kept like the audit log',
        'support_access_views' => 'the pages Navigate Ed staff viewed under support access, kept like the audit log',
        // Delivery machinery outside the database.
        'mail_streams' => 'the school\'s email configuration sets in Amazon SES (no personal information); removing them in SES is a separate step',
    ];

    /** School rows outside SchoolOwnedTables::REQUIRED that erasure also removes. */
    private const ALSO_DELETED = ['notification_deliveries', 'settings', 'domain_events', 'idempotency_keys'];

    /** @var list<string>|null the database's delete-refusing tables, read once */
    private ?array $protected = null;

    /** @var array<string, true>|null Schema metadata only; row queries remain school-specific. */
    private ?array $existingTables = null;

    private function tableExists(string $table): bool
    {
        $this->existingTables ??= array_fill_keys(array_column(Schema::getTables(), 'name'), true);

        return isset($this->existingTables[$table]);
    }

    /**
     * Every table erasure deletes from, with the query that selects the
     * school's rows in it. Nothing else is ever deleted.
     *
     * @return array<string, Builder>
     */
    public function targets(int $schoolId): array
    {
        $targets = [];
        foreach (SchoolOwnedTables::REQUIRED as $table) {
            if (! isset(self::KEPT[$table]) && $this->tableExists($table)) {
                $targets[$table] = DB::table($table)->where('school_id', $schoolId);
            }
        }
        foreach ([...self::ALSO_DELETED, ...SchoolOwnedTables::TEAMS] as $table) {
            $targets[$table] = DB::table($table)->where('school_id', $schoolId);
        }
        // role_has_permissions follows its role (SchoolOwnedTables::FOLLOWS_PARENT).
        $targets['role_has_permissions'] = DB::table('role_has_permissions')->whereIn('role_id', DB::table('roles')->select('id')->where('school_id', $schoolId));
        $targets['school_memberships'] = DB::table('school_memberships')->where('school_id', $schoolId);

        return $targets;
    }

    /**
     * Target tables the database itself keeps immutable: a trigger refuses
     * every delete (money, ledger and other history that must be retained).
     * Erasure never deletes from them, and keeps the rows they refer to.
     *
     * @return list<string>
     */
    public function protectedTables(): array
    {
        if ($this->protected !== null) {
            return $this->protected;
        }
        $tables = DB::select("select distinct event_object_table as name from information_schema.triggers where trigger_schema = ? and event_manipulation = 'DELETE'", [DB::connection()->getDatabaseName()]);

        return $this->protected = array_values(array_map(fn (object $row): string => (string) (array_change_key_case((array) $row)['name'] ?? ''), $tables));
    }

    /**
     * @return array{generated_at: string, school_id: int, tables_checked: int, delete: array<string, int>, delete_total: int, protected: array<string, int>, users: array{only_this_school: int, also_other_schools: int}, kept: array<string, array{rows: int, reason: string}>, not_counted: array<string, string>, deleted: int}
     */
    public function report(int $schoolId): array
    {
        $counts = [];
        $protected = [];
        $immutable = array_flip($this->protectedTables());
        $targets = $this->targets($schoolId);
        foreach ($targets as $table => $query) {
            if (isset($immutable[$table])) {
                $protected[$table] = $query->count();
            } else {
                $counts[$table] = $query->count();
            }
        }

        // People: an account only this school knows is erased; one another school also has keeps its account and loses only this school's membership.
        $members = DB::table('school_memberships')->where('school_id', $schoolId)->distinct()->pluck('user_id');
        $shared = DB::table('school_memberships')->whereIn('user_id', $members)->where('school_id', '!=', $schoolId)->distinct()->count('user_id');

        $delete = array_filter($counts);
        ksort($delete);
        $protected = array_filter($protected);
        ksort($protected);

        return [
            'generated_at' => now()->toISOString(), 'school_id' => $schoolId, 'tables_checked' => count($targets),
            'delete' => $delete, 'delete_total' => array_sum($delete),
            // Immutable history the database refuses to delete; the rows it refers to stay too, so fewer rows than 'delete' may go.
            'protected' => $protected,
            'users' => ['only_this_school' => $members->count() - $shared, 'also_other_schools' => $shared],
            'kept' => $this->kept($schoolId),
            'not_counted' => ['stored files' => 'uploaded files (documents, photos, report PDFs) are not counted and not erased in this step'],
            'deleted' => 0,
        ];
    }

    /**
     * The school's rows that erasure keeps, per table, with the reason.
     *
     * @return array<string, array{rows: int, reason: string}>
     */
    public function kept(int $schoolId): array
    {
        $kept = [];
        foreach (self::KEPT as $table => $reason) {
            if ($this->tableExists($table)) {
                $kept[$table] = ['rows' => DB::table($table)->where('school_id', $schoolId)->count(), 'reason' => $reason];
            }
        }
        $kept['schools'] = ['rows' => DB::table('schools')->where('id', $schoolId)->count(), 'reason' => 'the archived school itself stays, so its audit and HQ records still name it'];

        return $kept;
    }
}

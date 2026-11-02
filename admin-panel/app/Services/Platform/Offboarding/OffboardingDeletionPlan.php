<?php

declare(strict_types=1);

namespace App\Services\Platform\Offboarding;

use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The deletion dry run of a leaving school (Action Plan SA.11): how many rows
 * of each school-owned table (SchoolOwnedTables) deleting the school would
 * remove, and what would be kept and why. It only counts: it never deletes,
 * changes or reads the content of a row, so HQ sees numbers only.
 * engineering/specs/platform/offboarding.md.
 */
class OffboardingDeletionPlan
{
    /** Kept when the school's data is deleted, with the reason. */
    public const KEPT = OffboardingExport::HQ_RECORDS + [
        'audit_log' => 'the audit records (who did what, and when) are kept as the law and the contract require',
    ];

    /** School rows outside SchoolOwnedTables::REQUIRED that deletion would also remove. */
    private const ALSO_DELETED = ['notification_deliveries', 'settings', 'domain_events', 'idempotency_keys'];

    /**
     * @return array{generated_at: string, school_id: int, tables_checked: int, delete: array<string, int>, delete_total: int, users: array{only_this_school: int, also_other_schools: int}, kept: array<string, array{rows: int, reason: string}>, not_counted: array<string, string>, deleted: int}
     */
    public function report(int $schoolId): array
    {
        $counts = [];
        foreach (SchoolOwnedTables::REQUIRED as $table) {
            if (! isset(self::KEPT[$table]) && Schema::hasTable($table)) {
                $counts[$table] = DB::table($table)->where('school_id', $schoolId)->count();
            }
        }
        foreach ([...self::ALSO_DELETED, ...SchoolOwnedTables::TEAMS] as $table) {
            $counts[$table] = DB::table($table)->where('school_id', $schoolId)->count();
        }
        $counts['role_has_permissions'] = DB::table('role_has_permissions')->whereIn('role_id', DB::table('roles')->select('id')->where('school_id', $schoolId))->count();
        $counts['school_memberships'] = DB::table('school_memberships')->where('school_id', $schoolId)->count();

        // People: an account only this school knows would go; one another school also has keeps its account and loses only this school's membership.
        $members = DB::table('school_memberships')->where('school_id', $schoolId)->distinct()->pluck('user_id');
        $shared = DB::table('school_memberships')->whereIn('user_id', $members)->where('school_id', '!=', $schoolId)->distinct()->count('user_id');

        $kept = [];
        foreach (self::KEPT as $table => $reason) {
            $kept[$table] = ['rows' => DB::table($table)->where('school_id', $schoolId)->count(), 'reason' => $reason];
        }
        $kept['schools'] = ['rows' => DB::table('schools')->where('id', $schoolId)->count(), 'reason' => 'the archived school itself stays, so its audit and HQ records still name it'];
        $delete = array_filter($counts);
        ksort($delete);

        return [
            'generated_at' => now()->toISOString(), 'school_id' => $schoolId, 'tables_checked' => count($counts),
            'delete' => $delete, 'delete_total' => array_sum($delete),
            'users' => ['only_this_school' => $members->count() - $shared, 'also_other_schools' => $shared],
            'kept' => $kept,
            'not_counted' => ['stored files' => 'uploaded files (documents, photos, report PDFs) and the export archive are not counted in this step'],
            'deleted' => 0,
        ];
    }
}

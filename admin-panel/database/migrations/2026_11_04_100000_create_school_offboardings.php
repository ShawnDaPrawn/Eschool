<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * School offboarding (Action Plan SA.11): one row per offboarding of a school
 * that is leaving Navigate Ed. HQ's own record of the steps (export, receipt,
 * archive, deletion approvals, dry run); it holds states, dates, hashes and
 * counts, never the school's data. Written from /saas, and the receipt from
 * the school's own panel. engineering/specs/platform/offboarding.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_offboardings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->string('state', 16);
            $t->string('reason', 500);
            $t->foreignId('contract_id')->nullable()->constrained('school_contracts')->restrictOnDelete();
            $t->string('retention_source', 16);
            $t->string('retention_terms', 500);
            $t->date('delete_on');
            $t->date('receipt_deadline');
            $t->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            // The export: an encrypted, signed archive on the private disk.
            $t->uuid('generation_id')->nullable();
            $t->string('artifact_path', 255)->nullable();
            $t->char('artifact_sha256', 64)->nullable();
            $t->char('cipher_sha256', 64)->nullable();
            $t->unsignedBigInteger('artifact_bytes')->nullable();
            $t->char('manifest_sha256', 64)->nullable();
            $t->string('signing_key_id', 64)->nullable();
            $t->unsignedInteger('table_count')->nullable();
            $t->unsignedBigInteger('row_count')->nullable();
            $t->string('failure_code', 64)->nullable();
            $t->dateTime('export_ready_at')->nullable();
            $t->unsignedInteger('download_count')->default(0);
            $t->dateTime('last_downloaded_at')->nullable();
            // Receipt: confirmed by the school's Super Admin, or the deadline passed.
            $t->dateTime('received_at')->nullable();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('receipt_basis', 16)->nullable();
            $t->dateTime('archived_at')->nullable();
            $t->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('cancelled_at')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->boolean('legal_hold')->default(false);
            $t->string('legal_hold_reason', 500)->nullable();
            $t->dateTime('legal_hold_at')->nullable();
            $t->foreignId('legal_hold_by')->nullable()->constrained('users')->nullOnDelete();
            // Deletion: two approvals by different people (the second an Owner) and a dry run.
            $t->dateTime('deletion_requested_at')->nullable();
            $t->foreignId('deletion_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('deletion_approved_at')->nullable();
            $t->foreignId('deletion_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->json('dry_run_report')->nullable();
            $t->dateTime('dry_run_at')->nullable();
            $t->foreignId('dry_run_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'state'], 'school_offboardings_state_index');
        });

        // The contract's retention term: days after it ends before the school's data is deleted.
        Schema::table('school_contracts', function (Blueprint $t) {
            $t->unsignedSmallInteger('deletion_days_after_end')->nullable()->after('data_processing_signed_on');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('school_offboardings') && DB::table('school_offboardings')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain school offboarding history.');
        }
        if (DB::table('school_contracts')->whereNotNull('deletion_days_after_end')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain recorded contract retention terms.');
        }
        Schema::dropIfExists('school_offboardings');
        Schema::table('school_contracts', fn (Blueprint $t) => $t->dropColumn('deletion_days_after_end'));
    }
};

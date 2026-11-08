<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * School erasure (Action Plan SA.11): the last step of offboarding. After two
 * approvals and a cooling-off period, the leaving school's own rows are erased
 * in batches that can be interrupted and resumed, and an erasure certificate
 * is issued. The certificate holds counts, dates and hashes only, never
 * personal information. engineering/specs/platform/offboarding.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_offboardings', function (Blueprint $t) {
            // The earliest moment erasure may start: the later of the deletion date and the end of the cooling-off period after the second approval.
            $t->dateTime('erasure_not_before')->nullable()->after('deletion_approved_by');
            $t->dateTime('erasure_started_at')->nullable()->after('erasure_not_before');
            $t->foreignId('erasure_started_by')->nullable()->after('erasure_started_at')->constrained('users')->nullOnDelete();
            // Rows erased so far, per table (counts only), and the table being erased now.
            $t->json('erasure_progress')->nullable()->after('erasure_started_by');
            $t->string('erasure_step', 64)->nullable()->after('erasure_progress');
            $t->unsignedInteger('erasure_runs')->default(0)->after('erasure_step');
            $t->dateTime('erasure_heartbeat_at')->nullable()->after('erasure_runs');
            $t->string('erasure_failure_code', 191)->nullable()->after('erasure_heartbeat_at');
            $t->dateTime('erased_at')->nullable()->after('erasure_failure_code');
        });

        Schema::create('school_erasure_certificates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('offboarding_id')->unique()->constrained('school_offboardings')->restrictOnDelete();
            $t->string('reference', 32)->unique();
            // What was erased and kept, when, and the audit entries for each approval: counts, dates, hashes and ids of records only.
            $t->json('certificate');
            $t->char('certificate_sha256', 64);
            $t->dateTime('issued_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('school_erasure_certificates') && DB::table('school_erasure_certificates')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain school erasure certificates.');
        }
        if (DB::table('school_offboardings')->whereNotNull('erasure_started_at')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain school erasure progress.');
        }
        Schema::dropIfExists('school_erasure_certificates');
        Schema::table('school_offboardings', function (Blueprint $t) {
            $t->dropConstrainedForeignId('erasure_started_by');
            $t->dropColumn(['erasure_not_before', 'erasure_started_at', 'erasure_progress', 'erasure_step', 'erasure_runs', 'erasure_heartbeat_at', 'erasure_failure_code', 'erased_at']);
        });
    }
};

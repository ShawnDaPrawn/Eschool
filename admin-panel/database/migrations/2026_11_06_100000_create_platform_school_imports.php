<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding imports that Navigate Ed Operations run on a school's behalf
 * (Action Plan SA.11): the file, encrypted, the preview the school approves,
 * the approval and the result. The file and preview are wiped when the
 * import is applied, declined, cancelled, stopped or expires.
 * Specification: engineering/specs/platform/hq-imports.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_school_imports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->string('kind', 16);
            // awaiting_approval, approved, applied, declined, cancelled, void or expired.
            $t->string('status', 20)->default('awaiting_approval');
            $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            // The school leader who approves; the import is written with their permissions.
            $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            // The file (encrypted) and the rows of its preview (encrypted): null once finished.
            $t->longText('contents')->nullable();
            $t->longText('preview')->nullable();
            $t->char('file_sha256', 64);
            $t->char('fingerprint', 64);
            $t->json('mapping');
            $t->json('options');
            $t->json('counts');
            $t->unsignedInteger('rows');
            $t->char('approved_sha256', 64)->nullable();
            $t->string('decision_reason', 500)->nullable();
            $t->dateTime('decided_at')->nullable();
            $t->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('applied_at')->nullable();
            $t->json('result')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('close_reason', 500)->nullable();
            $t->dateTime('expires_at');
            $t->timestamps();
            $t->index(['status', 'expires_at'], 'platform_school_imports_status_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_school_imports') && DB::table('platform_school_imports')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain the record of imports run for schools.');
        }
        Schema::dropIfExists('platform_school_imports');
    }
};

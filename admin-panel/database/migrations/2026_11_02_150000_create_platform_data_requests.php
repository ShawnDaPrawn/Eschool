<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * POPIA data subject requests that reach Navigate Ed (Action Plan SA.11):
 * HQ logs each one with its deadline and passes it to the school, the
 * responsible party, which records its answer. One row per request, owned by
 * the school it concerns. Specification: engineering/specs/platform/data-requests.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_data_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            // access, correction, deletion or objection (POPIA sections 23, 24 and 11(3)).
            $t->string('kind', 16);
            // received, with_school, answered, closed or withdrawn.
            $t->string('status', 16)->default('received');
            $t->string('channel', 16);
            $t->string('requester_role', 16);
            // Who asked, how to reach them and what they asked for: encrypted at rest, never in the audit log.
            $t->text('requester_name');
            $t->text('requester_contact');
            $t->text('details');
            $t->date('received_on');
            $t->date('due_on');
            $t->dateTime('passed_at')->nullable();
            $t->foreignId('passed_by')->nullable()->constrained('users')->nullOnDelete();
            // fulfilled, partly or refused, with the school's note to Navigate Ed (encrypted).
            $t->string('outcome', 16)->nullable();
            $t->text('outcome_note')->nullable();
            $t->dateTime('answered_at')->nullable();
            $t->foreignId('answered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('closed_at')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('closing_note')->nullable();
            $t->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'due_on'], 'platform_data_requests_status_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_data_requests') && DB::table('platform_data_requests')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain the data request register.');
        }
        Schema::dropIfExists('platform_data_requests');
    }
};

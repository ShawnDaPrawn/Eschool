<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The content production pipeline (Action Plan 3.4, SA.16):
 *  - platform_ai_usage: HQ's own AI metering, for model calls made from /saas
 *    with no school (the school ledger, ai_usage, is school-owned). Counts,
 *    cents and hashes only: never a prompt or an answer.
 *  - library_ai_drafts: each HQ request for AI drafts of library items, its
 *    brief and outcome, and the items it produced.
 *  - library_item_versions.origin and library_ai_draft_id: whether a version's
 *    content was written by staff or drafted by AI, and which request.
 * All three are global: no school_id, written only from /saas/library.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_ai_usage', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('feature', 64);
            $t->string('provider', 32);
            $t->string('model', 128);
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->date('usage_date');
            $t->string('unit', 16)->default('tokens');
            $t->string('state', 16);
            $t->unsignedInteger('reserved_units')->default(0);
            $t->unsignedInteger('used_units')->default(0);
            $t->unsignedBigInteger('reserved_cost_cents')->default(0);
            $t->unsignedBigInteger('cost_cents')->default(0);
            $t->char('billing_currency', 3);
            $t->string('prompt_key', 64);
            $t->string('prompt_version', 16);
            $t->char('prompt_sha256', 64);
            $t->json('flags')->nullable();
            $t->string('failure_code', 64)->nullable();
            $t->char('output_sha256', 64)->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['feature', 'operation_id']);
            $t->index(['feature', 'usage_date']);
        });
        Schema::create('library_ai_drafts', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_id')->unique();
            $t->char('request_hash', 64);
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('library_topic_id')->constrained('library_topics')->restrictOnDelete();
            $t->foreignId('library_source_id')->constrained('library_sources')->restrictOnDelete();
            $t->string('kind', 24);
            $t->unsignedTinyInteger('count');
            $t->string('brief', 1500);
            $t->string('state', 16)->default('pending');
            $t->string('failure_code', 64)->nullable();
            $t->foreignId('platform_ai_usage_id')->nullable()->constrained('platform_ai_usage')->restrictOnDelete();
            $t->json('item_ids')->nullable();
            $t->timestamps();
            $t->index(['requested_by', 'created_at']);
        });
        Schema::table('library_item_versions', function (Blueprint $t) {
            $t->string('origin', 16)->default('staff')->after('editor_ids');
            $t->foreignId('library_ai_draft_id')->nullable()->after('origin')->constrained('library_ai_drafts')->restrictOnDelete();
            $t->index('origin');
        });
    }

    public function down(): void
    {
        if (DB::table('library_ai_drafts')->exists() || DB::table('platform_ai_usage')->exists() || DB::table('library_item_versions')->where('origin', '!=', 'staff')->exists()) {
            throw new RuntimeException('AI-drafted library content and HQ AI metering are kept: export them before rolling back.');
        }
        Schema::table('library_item_versions', function (Blueprint $t) {
            $t->dropIndex(['origin']);
            $t->dropConstrainedForeignId('library_ai_draft_id');
            $t->dropColumn('origin');
        });
        Schema::dropIfExists('library_ai_drafts');
        Schema::dropIfExists('platform_ai_usage');
    }
};

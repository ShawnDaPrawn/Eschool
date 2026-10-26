<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_drafts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('content_version_id')->constrained('content_versions')->restrictOnDelete();
            $t->foreignId('learner_id')->nullable()->constrained('students')->restrictOnDelete();
            $t->foreignId('assessment_task_id')->nullable()->constrained('assessment_tasks')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->string('kind', 24);
            $t->string('state', 24)->default('pending');
            $t->unsignedInteger('revision')->default(0);
            $t->longText('request')->nullable();
            $t->longText('source_manifest')->nullable();
            $t->longText('context_text')->nullable();
            $t->foreignId('ai_usage_id')->nullable()->constrained('ai_usage')->restrictOnDelete();
            $t->string('failure_code', 64)->nullable();
            $t->uuid('attempt_id');
            $t->timestamp('lease_until');
            $t->timestamp('purged_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->index(['school_id', 'author_id', 'teaching_group_id']);
        });
        Schema::create('teaching_draft_photos', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('source_digest', 64);
            $t->string('sha256', 64);
            $t->longText('crop');
            $t->string('path');
            $t->unsignedInteger('bytes');
            $t->timestamp('approved_at');
            $t->timestamp('revoked_at')->nullable();
            $t->timestamp('purged_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'author_id', 'operation_id']);
        });
        Schema::create('teaching_draft_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->uuid('teaching_draft_id');
            $t->foreign(['school_id', 'teaching_draft_id'], 'teaching_version_scope')->references(['school_id', 'id'])->on('teaching_drafts')->restrictOnDelete();
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('number');
            $t->longText('definition')->nullable();
            $t->string('action', 16);
            $t->timestamps();
            $t->unique(['school_id', 'teaching_draft_id', 'number'], 'teaching_version_number');
        });
        Schema::create('teaching_draft_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->longText('result');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'teaching_operation_identity');
        });
        Schema::create('task_insights', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('assessment_task_id')->constrained('assessment_tasks')->restrictOnDelete();
            $t->string('fingerprint', 64);
            $t->longText('summary');
            $t->timestamps();
            $t->unique(['school_id', 'teaching_group_id', 'assessment_task_id'], 'task_insight_identity');
        });
    }

    public function down(): void
    {
        foreach (['teaching_draft_photos', 'teaching_draft_versions', 'teaching_draft_operations', 'teaching_drafts', 'task_insights'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Teaching proposals and their audit history need explicit retention handling before rollback.');
            }
        }
        foreach (['task_insights', 'teaching_draft_photos', 'teaching_draft_versions', 'teaching_draft_operations', 'teaching_drafts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

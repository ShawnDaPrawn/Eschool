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
        Schema::create('content_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools');
            $t->boolean('transcription_enabled')->default(true);
            $t->unsignedInteger('daily_transcription_seconds')->default(3600);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('content_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('author_id')->constrained('users');
            $t->foreignId('subject_id')->constrained('subjects');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->unsignedBigInteger('published_version_id')->nullable();
            $t->unsignedBigInteger('draft_version_id')->nullable();
            $t->unsignedBigInteger('notice_after_learner_id')->default(0);
            $t->timestamp('notice_completed_at')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'author_id', 'operation_id'], 'content_create_operation');
            $t->index(['school_id', 'session_year_id', 'subject_id', 'grade_level_id'], 'content_library_scope');
        });
        Schema::create('content_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('content_item_id')->constrained('content_items');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('kind', 30);
            $t->string('request_hash', 64);
            $t->longText('result');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'content_operation_receipt');
        });
        Schema::create('content_sources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('content_item_id')->constrained('content_items');
            $t->foreignId('uploaded_by')->constrained('users');
            $t->text('name');
            $t->string('path', 255);
            $t->string('mime', 120);
            $t->string('extension', 10);
            $t->unsignedInteger('bytes');
            $t->string('sha256', 64);
            $t->string('scan_state', 20)->default('pending');
            $t->string('scan_engine', 191)->nullable();
            $t->string('scan_signature', 191)->nullable();
            $t->dateTime('scanned_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'content_item_id'], 'content_source_item');
        });
        Schema::create('content_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('content_item_id')->constrained('content_items');
            $t->foreignId('content_source_id')->constrained('content_sources');
            $t->foreignId('created_by')->constrained('users');
            $t->unsignedInteger('number');
            $t->string('state', 20)->default('queued');
            $t->longText('metadata');
            $t->longText('markdown')->nullable();
            $t->uuid('generation_id');
            $t->uuid('attempt_id')->nullable();
            $t->dateTime('lease_until')->nullable();
            $t->string('failure_code', 64)->nullable();
            $t->unsignedInteger('duration_seconds')->nullable();
            $t->string('language', 10)->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
            $t->text('review_reason')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->dateTime('published_at')->nullable();
            $t->string('published_fingerprint', 64)->nullable();
            $t->uuid('publish_operation_id')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'content_item_id', 'number'], 'content_item_version');
            $t->index(['school_id', 'state', 'lease_until'], 'content_conversion_queue');
        });
        Schema::table('content_items', function (Blueprint $t) {
            $t->foreign('published_version_id')->references('id')->on('content_versions');
            $t->foreign('draft_version_id')->references('id')->on('content_versions');
        });
        Schema::create('content_item_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('content_item_id')->constrained('content_items');
            $t->foreignId('teaching_group_id')->constrained('teaching_groups');
            $t->unique(['school_id', 'content_item_id', 'teaching_group_id'], 'content_published_group');
        });
        Schema::create('content_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('content_version_id')->constrained('content_versions');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('notification_id')->nullable()->constrained('notifications');
            $t->dateTime('read_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'content_version_id', 'learner_id', 'user_id'], 'content_recipient');
        });
        Schema::create('ai_usage', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('learner_id')->nullable()->constrained('students');
            $t->string('feature', 64);
            $t->string('provider', 64);
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->date('usage_date');
            $t->string('unit', 20)->default('seconds');
            $t->string('state', 20)->default('reserved');
            $t->unsignedInteger('reserved_units')->default(0);
            $t->unsignedInteger('used_units')->default(0);
            $t->unsignedBigInteger('cost_cents')->default(0);
            $t->string('output_sha256', 64)->nullable();
            $t->string('failure_code', 64)->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'feature', 'operation_id'], 'ai_usage_operation');
            $t->index(['school_id', 'feature', 'usage_date'], 'ai_daily_cap');
        });
        Schema::create('ai_flags', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('ai_usage_id')->constrained('ai_usage');
            $t->string('code', 64);
            $t->timestamps();
            $t->index(['school_id', 'ai_usage_id'], 'ai_flag_usage');
        });
    }

    public function down(): void
    {
        if (DB::table('content_items')->exists() || DB::table('ai_usage')->exists()) {
            throw new LogicException('Private content and metering history must be retained.');
        }
        foreach (['ai_flags', 'ai_usage', 'content_recipients', 'content_item_groups', 'content_operations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('content_items', function (Blueprint $t) {
            $t->dropForeign(['published_version_id']);
            $t->dropForeign(['draft_version_id']);
        });
        foreach (['content_versions', 'content_sources', 'content_items', 'content_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

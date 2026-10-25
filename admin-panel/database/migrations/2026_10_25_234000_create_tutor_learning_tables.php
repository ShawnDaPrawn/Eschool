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
        Schema::create('tutor_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $table->uuid('operation_id');
            $table->string('request_hash', 64);
            $table->longText('source_version_ids');
            $table->string('state', 24)->default('open');
            $table->timestamps();
            $table->index(['school_id', 'learner_id', 'subject_id']);
            $table->unique(['school_id', 'operation_id']);
        });
        Schema::create('tutor_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignUuid('tutor_session_id')->constrained('tutor_sessions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->uuid('operation_id');
            $table->string('request_hash', 64);
            $table->longText('learner_text')->nullable();
            $table->longText('working')->nullable();
            $table->longText('media_ids')->nullable();
            $table->longText('source_manifest')->nullable();
            $table->longText('context_text')->nullable();
            $table->longText('response')->nullable();
            $table->string('response_kind', 16)->nullable();
            $table->string('state', 24)->default('pending');
            $table->string('failure_code', 64)->nullable();
            $table->foreignId('ai_usage_id')->nullable()->constrained('ai_usage')->restrictOnDelete();
            $table->uuid('attempt_id');
            $table->timestamp('lease_until');
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'operation_id']);
            $table->index(['school_id', 'tutor_session_id', 'created_at']);
        });
        Schema::create('tutor_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignUuid('tutor_session_id')->constrained('tutor_sessions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->uuid('operation_id');
            $table->string('source_digest', 64);
            $table->string('sha256', 64);
            $table->longText('crop');
            $table->string('path');
            $table->unsignedInteger('bytes');
            $table->timestamp('approved_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'tutor_session_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        foreach (['tutor_messages', 'tutor_photos', 'tutor_sessions'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Tutor records require explicit retention handling before rollback.');
            }
        }
        Schema::dropIfExists('tutor_photos');
        Schema::dropIfExists('tutor_messages');
        Schema::dropIfExists('tutor_sessions');
    }
};

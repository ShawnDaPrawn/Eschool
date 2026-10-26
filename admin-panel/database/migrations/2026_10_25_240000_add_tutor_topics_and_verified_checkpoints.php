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
        Schema::table('tutor_sessions', function (Blueprint $t) {
            $t->foreignId('topic_id')->nullable()->constrained('mastery_topics')->restrictOnDelete();
        });
        Schema::create('tutor_checkpoints', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignUuid('tutor_session_id')->constrained('tutor_sessions')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('practice_item_version_id')->constrained('practice_item_versions')->restrictOnDelete();
            $t->foreignId('topic_id')->constrained('mastery_topics')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->string('definition_hash', 64);
            $t->uuid('answer_operation_id')->nullable();
            $t->string('answer_hash', 64)->nullable();
            $t->longText('answer')->nullable();
            $t->boolean('correct')->nullable();
            $t->dateTime('answered_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'operation_id'], 'tutor_checkpoint_issue_identity');
            $t->unique(['school_id', 'user_id', 'answer_operation_id'], 'tutor_checkpoint_answer_identity');
            // A new session or UUID must not turn a known answer into repeated
            // verified evidence. Daily FSRS practice remains independently resumable.
            $t->unique(['school_id', 'learner_id', 'practice_item_version_id'], 'tutor_checkpoint_once_per_version');
            $t->index(['school_id', 'tutor_session_id', 'created_at'], 'tutor_checkpoint_session_order');
        });
    }

    public function down(): void
    {
        if (DB::table('tutor_checkpoints')->exists() || DB::table('tutor_sessions')->whereNotNull('topic_id')->exists()) {
            throw new RuntimeException('Retain Tutor topic/checkpoint evidence before rollback.');
        }
        Schema::dropIfExists('tutor_checkpoints');
        Schema::table('tutor_sessions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('topic_id');
        });
    }
};

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
        Schema::create('mastery_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools');
            $t->boolean('family_marks_enabled')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('mastery_topics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('subject_id')->constrained('subjects');
            $t->uuid('topic_key');
            $t->foreignId('curriculum_topic_id')->constrained('curriculum_topics');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->string('name', 191);
            $t->timestamps();
            $t->unique(['school_id', 'subject_id', 'topic_key'], 'mastery_topic_identity');
        });
        Schema::create('topic_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('subject_id')->constrained('subjects');
            $t->foreignId('topic_id')->constrained('mastery_topics');
            $t->foreignId('prerequisite_id')->constrained('mastery_topics');
            $t->string('source', 1000);
            $t->foreignId('recorded_by')->constrained('users');
            $t->timestamps();
            $t->unique(['school_id', 'topic_id', 'prerequisite_id'], 'mastery_edge_identity');
        });
        Schema::create('mastery_evidence', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('subject_id')->constrained('subjects');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('topic_id')->constrained('mastery_topics');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->string('audience', 16);
            $t->string('source_type', 32);
            $t->string('source_key', 128);
            $t->unsignedInteger('achieved_units');
            $t->unsignedInteger('maximum_units');
            $t->timestamp('observed_at');
            $t->timestamps();
            $t->unique(['school_id', 'topic_id', 'learner_id', 'audience', 'source_key'], 'mastery_evidence_identity');
            $t->index(['school_id', 'learner_id', 'audience', 'topic_id'], 'mastery_evidence_reader');
        });
        Schema::create('mastery_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('subject_id')->constrained('subjects');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('topic_id')->constrained('mastery_topics');
            $t->string('audience', 16);
            $t->unsignedTinyInteger('level')->nullable();
            $t->unsignedSmallInteger('score_bp')->nullable();
            $t->unsignedSmallInteger('confidence_bp')->default(0);
            $t->unsignedSmallInteger('evidence_count')->default(0);
            $t->string('policy_version', 32);
            $t->timestamp('computed_at');
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'topic_id', 'audience'], 'mastery_record_identity');
        });
        Schema::create('mastery_scopes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('subject_id')->constrained('subjects');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->timestamp('computed_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'subject_id', 'grade_level_id', 'session_year_id'], 'mastery_scope_identity');
        });
    }

    public function down(): void
    {
        if (DB::table('mastery_evidence')->exists() || DB::table('topic_links')->exists()) {
            throw new RuntimeException('Retained mastery evidence or sourced links block destructive rollback.');
        }
        foreach (['mastery_scopes', 'mastery_records', 'mastery_evidence', 'topic_links', 'mastery_topics', 'mastery_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peer_tutoring_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('teaching_group_id')->constrained('teaching_groups');
            $t->foreignId('topic_id')->constrained('mastery_topics');
            $t->foreignId('tutor_learner_id')->constrained('students');
            $t->foreignId('tutee_learner_id')->constrained('students');
            $t->foreignId('supervisor_user_id')->constrained('users');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('venue', 191);
            $t->string('status', 16);
            $t->unsignedInteger('revision')->default(1);
            $t->unsignedSmallInteger('verified_minutes')->nullable();
            $t->foreignId('house_point_id')->nullable()->constrained('house_points');
            $t->longText('correction_reason')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'supervisor_user_id', 'status', 'starts_at'], 'peer_supervisor_calendar');
            $t->index(['school_id', 'tutor_learner_id', 'starts_at'], 'peer_tutor_calendar');
            $t->index(['school_id', 'tutee_learner_id', 'starts_at'], 'peer_tutee_calendar');
        });
        Schema::create('service_hours', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('peer_tutoring_session_id');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('verified_by')->constrained('users');
            $t->string('kind', 16);
            $t->smallInteger('minutes');
            $t->timestamps();
            $t->unique(['school_id', 'peer_tutoring_session_id', 'kind'], 'peer_service_once');
            $t->foreign(['school_id', 'peer_tutoring_session_id'], 'peer_service_session_fk')->references(['school_id', 'id'])->on('peer_tutoring_sessions');
        });
        Schema::create('peer_tutoring_operations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('kind', 24);
            $t->char('request_hash', 64);
            $t->uuid('peer_tutoring_session_id');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'peer_operation_identity');
            $t->foreign(['school_id', 'peer_tutoring_session_id'], 'peer_operation_session_fk')->references(['school_id', 'id'])->on('peer_tutoring_sessions');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('peer_tutoring_sessions') && DB::table('peer_tutoring_sessions')->exists()) {
            throw new RuntimeException('Peer tutoring rollback refused: preserve supervised attendance and service history.');
        }
        Schema::dropIfExists('peer_tutoring_operations');
        Schema::dropIfExists('service_hours');
        Schema::dropIfExists('peer_tutoring_sessions');
    }
};

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
        Schema::table('session_years', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'facilitator_leave_year_identity'));
        Schema::table('activity_sessions', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'facilitator_cover_session_identity'));
        Schema::create('facilitator_absences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users');
            $t->unsignedBigInteger('session_year_id');
            $t->date('from_date');
            $t->date('to_date');
            $t->json('portions')->comment('Explicit full, first_half or second_half for each requested date');
            $t->text('reason')->comment('Encrypted; requester and authorised reviewer only');
            $t->longText('attachments')->nullable()->comment('Encrypted private-file metadata');
            $t->string('status', 16)->default('pending');
            $t->foreignId('decided_by')->nullable()->constrained('users');
            $t->timestamp('decided_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'session_year_id'], 'facilitator_absence_year')->references(['school_id', 'id'])->on('session_years');
            $t->index(['school_id', 'session_year_id', 'status', 'from_date'], 'facilitator_absence_queue');
        });
        Schema::create('facilitator_session_covers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('facilitator_absence_id');
            $t->unsignedBigInteger('activity_session_id');
            $t->unsignedBigInteger('activity_group_id');
            $t->foreignId('replacement_user_id')->constrained('users');
            $t->foreignId('assigned_by')->constrained('users');
            $t->date('on_date');
            $t->time('starts_at')->nullable();
            $t->time('ends_at')->nullable();
            $t->unsignedInteger('session_revision');
            $t->unsignedInteger('revision')->default(1);
            $t->text('reason')->comment('Encrypted; authorised cover reviewers only');
            $t->timestamps();
            $t->unique(['school_id', 'facilitator_absence_id', 'activity_session_id'], 'facilitator_cover_identity');
            $t->foreign(['school_id', 'facilitator_absence_id'], 'facilitator_cover_absence')->references(['school_id', 'id'])->on('facilitator_absences');
            $t->foreign(['school_id', 'activity_session_id'], 'facilitator_cover_session')->references(['school_id', 'id'])->on('activity_sessions');
            $t->foreign(['school_id', 'activity_group_id'], 'facilitator_cover_group')->references(['school_id', 'id'])->on('activity_groups');
            $t->index(['school_id', 'replacement_user_id', 'on_date'], 'facilitator_cover_conflicts');
        });
    }

    public function down(): void
    {
        foreach (['facilitator_session_covers', 'facilitator_absences'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained facilitator leave and cover prevent destructive rollback.');
            }
        }
        Schema::dropIfExists('facilitator_session_covers');
        Schema::dropIfExists('facilitator_absences');
        Schema::table('activity_sessions', fn (Blueprint $t) => $t->dropUnique('facilitator_cover_session_identity'));
        Schema::table('session_years', fn (Blueprint $t) => $t->dropUnique('facilitator_leave_year_identity'));
    }
};

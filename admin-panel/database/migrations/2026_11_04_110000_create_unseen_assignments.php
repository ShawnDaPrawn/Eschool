<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "No child unseen" (Action Plan 4.5): the named adult a grade head or
 * principal asks to reach out to a learner with no recent positive contact.
 * One open row per learner and year; it closes when a positive contact is
 * recorded or another adult is named. engineering/specs/growth/risk-interventions.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unseen_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('learner_id')->constrained('students');
            $table->foreignId('session_year_id')->constrained('session_years');
            $table->foreignId('adult_user_id')->constrained('users')->comment('The named staff member who reaches out');
            $table->foreignId('assigned_by')->constrained('users');
            $table->date('assigned_on');
            $table->timestamp('cleared_at')->nullable();
            $table->string('cleared_reason', 24)->nullable()->comment('contact_recorded or reassigned');
            $table->timestamps();

            $table->index(['school_id', 'session_year_id', 'learner_id', 'cleared_at'], 'unseen_learner_open');
            $table->index(['school_id', 'adult_user_id', 'cleared_at'], 'unseen_adult_open');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('unseen_assignments') && DB::table('unseen_assignments')->exists()) {
            throw new RuntimeException('Rollback refused: unseen_assignments holds named-adult history.');
        }
        Schema::dropIfExists('unseen_assignments');
    }
};

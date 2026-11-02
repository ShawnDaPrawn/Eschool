<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Short private wellbeing check-ins (Action Plan 4.11): a one-question scale
 * and an optional note, scheduled by the learner's pastoral professional. The
 * answer is encrypted and read only by the learner's current authorised
 * pastoral staff. engineering/specs/growth/check-ins-and-intervention-library.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wellbeing_check_ins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('scheduled_by')->constrained('users')->restrictOnDelete();
            $t->uuid('group_id')->nullable()->comment('Shared by the check-ins scheduled together for a group');
            $t->string('prompt', 24);
            $t->date('due_on');
            $t->string('status', 16)->default('scheduled');
            $t->longText('answer')->nullable();
            $t->string('answer_source', 16)->nullable();
            $t->foreignId('answered_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('answered_at')->nullable();
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->index(['school_id', 'learner_id', 'session_year_id', 'due_on'], 'wellbeing_check_in_learner');
            $t->index(['school_id', 'session_year_id', 'status', 'due_on'], 'wellbeing_check_in_queue');
        });
    }

    public function down(): void
    {
        if (DB::table('wellbeing_check_ins')->exists()) {
            throw new LogicException('Retain wellbeing check-in history.');
        }
        Schema::dropIfExists('wellbeing_check_ins');
    }
};

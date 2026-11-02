<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The school's intervention library and dated progress against a plan's steps
 * (Action Plan 4.11). A library action is a reusable, non-clinical school
 * action; an intervention made from one keeps an encrypted copy of its plan,
 * so later library edits never rewrite a learner's plan.
 * engineering/specs/growth/check-ins-and-intervention-library.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_library_actions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('name', 120);
            $t->string('concern_area', 24);
            $t->text('steps');
            $t->unsignedTinyInteger('suggested_weeks');
            $t->string('progress_measure', 500);
            $t->string('default_key', 48)->nullable()->comment('The suggested action this row was adopted from, if any');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('archived_at')->nullable();
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'default_key'], 'intervention_library_default');
            $t->index(['school_id', 'archived_at', 'concern_area'], 'intervention_library_choices');
        });
        Schema::table('interventions', function (Blueprint $t) {
            $t->foreignId('library_action_id')->nullable()->after('created_by')->constrained('intervention_library_actions')->restrictOnDelete();
            $t->longText('plan')->nullable()->after('outcome');
        });
        Schema::create('intervention_progress_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('intervention_id')->constrained('interventions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->date('recorded_on');
            $t->unsignedTinyInteger('step')->nullable()->comment('Position of the plan step, from 1; null is the whole plan');
            $t->string('status', 16);
            $t->longText('note')->nullable();
            $t->timestamp('created_at');
            $t->index(['school_id', 'intervention_id', 'recorded_on'], 'intervention_progress_timeline');
        });
    }

    public function down(): void
    {
        foreach (['intervention_progress_entries', 'intervention_library_actions'] as $table) {
            if (DB::table($table)->exists()) {
                throw new LogicException('Retain the intervention library and progress history.');
            }
        }
        Schema::dropIfExists('intervention_progress_entries');
        Schema::table('interventions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('library_action_id');
            $t->dropColumn('plan');
        });
        Schema::dropIfExists('intervention_library_actions');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The marking accuracy gate (Action Plan 3.18). Evaluation papers (a memo's
 * mark points), de-identified teacher-marked scripts, evaluation runs and each
 * subject's learner-marking switch are Navigate Ed's own evaluation data:
 * global, written only from /saas/marking-accuracy, with no school, learner or
 * school record anywhere. A script's own reference is kept only as a keyed
 * hash. engineering/specs/learning/marking-accuracy-gate.md.
 */
return new class extends Migration
{
    private const TABLES = ['marking_subject_gates', 'marking_evaluation_runs', 'marking_evaluation_scripts', 'marking_evaluation_papers'];

    public function up(): void
    {
        Schema::create('marking_evaluation_papers', function (Blueprint $t): void {
            $t->id();
            // A marking profile key (mathematics, physical_sciences).
            $t->string('subject', 32);
            $t->string('code', 64);
            $t->string('title', 191);
            $t->string('source', 500);
            // [{number, max_units, points: [{earns, type, mark_units, rule}]}], marks in hundredths, as structured memos store them.
            $t->json('questions');
            $t->unsignedInteger('max_units');
            $t->char('fingerprint', 64);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['subject', 'code']);
        });
        Schema::create('marking_evaluation_scripts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('marking_evaluation_paper_id')->constrained('marking_evaluation_papers')->restrictOnDelete();
            // HMAC of the source's own script reference; the reference itself is never stored.
            $t->char('reference_hash', 64);
            // A marker code (Marker A, Senior marker 2), never a learner.
            $t->string('marker', 60);
            $t->string('source', 200);
            // [{number, answer, marks: [units per mark point]}]
            $t->json('questions');
            $t->unsignedInteger('teacher_units');
            $t->unsignedInteger('max_units');
            $t->char('import_batch', 36);
            $t->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['marking_evaluation_paper_id', 'reference_hash'], 'marking_eval_scripts_paper_reference_unique');
            $t->index('import_batch');
        });
        Schema::create('marking_evaluation_runs', function (Blueprint $t): void {
            $t->id();
            $t->string('subject', 32);
            $t->string('engine_version', 32);
            $t->string('profile_version', 48);
            $t->unsignedInteger('script_count');
            $t->unsignedInteger('point_count');
            $t->boolean('passed');
            $t->json('failures');
            $t->json('metrics');
            $t->json('point_stats');
            // The thresholds, tolerance and minimum the run was measured against, and whether the owner had confirmed them.
            $t->json('settings');
            $t->char('scripts_fingerprint', 64);
            $t->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['subject', 'engine_version', 'profile_version', 'id'], 'marking_eval_runs_subject_versions_index');
        });
        Schema::create('marking_subject_gates', function (Blueprint $t): void {
            $t->id();
            $t->string('subject', 32)->unique();
            $t->boolean('enabled')->default(false);
            $t->foreignId('marking_evaluation_run_id')->nullable()->constrained('marking_evaluation_runs')->restrictOnDelete();
            $t->string('engine_version', 32)->nullable();
            $t->string('profile_version', 48)->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->string('reason', 500)->nullable();
            $t->foreignId('changed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

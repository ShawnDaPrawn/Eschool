<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_marks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('assessment_task_id')->constrained('assessment_tasks')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->unsignedInteger('mark_units')->nullable();
            $t->string('status', 16)->default('captured');
            $t->string('disposition', 16)->default('missing');
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('captured_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('moderated_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('moderated_at')->nullable();
            $t->string('note', 500)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'assessment_task_id', 'learner_id'], 'assessment_marks_identity');
            $t->index(['school_id', 'learner_id'], 'assessment_marks_learner');
        });
        Schema::create('question_marks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('assessment_mark_id')->constrained('assessment_marks')->restrictOnDelete();
            $t->foreignId('assessment_question_id')->constrained('assessment_questions')->restrictOnDelete();
            $t->unsignedInteger('mark_units')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'assessment_mark_id', 'assessment_question_id'], 'question_marks_identity');
        });
        Schema::create('mark_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->restrictOnDelete();
            $t->string('period_key', 40);
            $t->unsignedInteger('version');
            $t->char('fingerprint', 64);
            $t->unsignedSmallInteger('percentage_bp')->nullable();
            $t->unsignedSmallInteger('running_bp')->nullable();
            $t->unsignedSmallInteger('coverage_bp');
            $t->unsignedTinyInteger('rating_level')->nullable();
            $t->boolean('complete');
            $t->json('inputs');
            $t->json('result');
            $t->timestamp('created_at');
            $t->unique(['school_id', 'subject_id', 'grade_level_id', 'session_year_id', 'learner_id', 'period_key', 'version'], 'mark_snapshots_version');
            $t->index(['school_id', 'session_year_id', 'term_id', 'learner_id'], 'mark_snapshots_period');
        });
        Schema::create('mark_releases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->restrictOnDelete();
            $t->string('period_key', 40);
            $t->string('mode', 16)->default('official');
            $t->unsignedInteger('version');
            $t->char('fingerprint', 64);
            $t->foreignId('released_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('created_at');
            $t->unique(['school_id', 'subject_id', 'grade_level_id', 'session_year_id', 'period_key', 'version'], 'mark_releases_version');
        });
        Schema::create('mark_release_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('mark_release_id')->constrained('mark_releases')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('mark_snapshot_id')->constrained('mark_snapshots')->restrictOnDelete();
            $t->timestamp('created_at');
            $t->unique(['school_id', 'mark_release_id', 'learner_id'], 'mark_release_entries_identity');
            $t->index(['school_id', 'learner_id'], 'mark_release_entries_learner');
        });
        Schema::create('academic_exam_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->restrictOnDelete();
            $t->string('period_key', 40);
            $t->foreignId('exam_id')->constrained('exams')->restrictOnDelete();
            $t->foreignId('mark_release_id')->constrained('mark_releases')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'subject_id', 'grade_level_id', 'session_year_id', 'period_key'], 'academic_exam_links_context');
            $t->index(['school_id', 'exam_id']);
        });
        foreach (['marks-view-all', 'marks-release'] as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            foreach (DB::table('roles')->where('name', 'Super Admin')->pluck('id') as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::table('exam_marks', function (Blueprint $t) {
            $t->unsignedInteger('exact_obtained_units')->nullable();
            $t->boolean('passing_status')->nullable()->change();
        });
        Schema::table('exam_timetables', fn (Blueprint $t) => $t->integer('passing_marks')->nullable()->change());
        Schema::table('exam_results', function (Blueprint $t) {
            $t->unsignedInteger('exact_obtained_units')->nullable();
            $t->unsignedInteger('exact_total_units')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('mark_releases')->exists() || DB::table('exam_marks')->whereRaw('MOD(exact_obtained_units, 100) <> 0')->exists()) {
            throw new LogicException('Restore a tested backup to roll back released or fractional academic evidence.');
        }
        Schema::table('exam_marks', fn (Blueprint $t) => $t->boolean('passing_status')->nullable(false)->change());
        Schema::table('exam_timetables', fn (Blueprint $t) => $t->integer('passing_marks')->nullable(false)->change());
        $ids = DB::table('permissions')->whereIn('name', ['marks-view-all', 'marks-release'])->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('exam_results', fn (Blueprint $t) => $t->dropColumn(['exact_obtained_units', 'exact_total_units']));
        Schema::table('exam_marks', fn (Blueprint $t) => $t->dropColumn('exact_obtained_units'));
        foreach (['academic_exam_links', 'mark_release_entries', 'mark_releases', 'mark_snapshots', 'question_marks', 'assessment_marks'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

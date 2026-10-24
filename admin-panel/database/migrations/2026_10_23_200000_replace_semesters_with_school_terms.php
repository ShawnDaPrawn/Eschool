<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const LINKS = ['class_subjects', 'elective_subject_groups', 'timetables', 'student_subjects', 'lessons', 'assignments', 'announcements', 'class_teachers', 'subject_teachers', 'exams', 'online_exams'];

    public function up(): void
    {
        $this->permissions('semester', 'term');
        foreach (self::LINKS as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign(['semester_id']));
        }
        Schema::rename('semesters', 'terms');
        foreach (self::LINKS as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('semester_id', 'term_id');
                $t->foreign('term_id')->references('id')->on('terms')->restrictOnDelete();
            });
        }
        Schema::table('class_session_configs', fn (Blueprint $t) => $t->renameColumn('include_semesters', 'include_terms'));
        Schema::table('terms', function (Blueprint $t) {
            $t->unsignedTinyInteger('grade_from')->default(0);
            $t->unsignedTinyInteger('grade_to')->default(12);
            $t->date('marks_lock_on')->nullable();
            $t->date('reports_on')->nullable();
            $t->index(['school_id', 'session_year_id', 'start_date', 'end_date']);
        });
        Schema::create('term_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('name', 120);
            $t->unsignedSmallInteger('base_year');
            $t->json('terms');
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'name']);
        });
    }

    private function permissions(string $from, string $to): void
    {
        // Preserve permission IDs and every existing role/direct grant.
        foreach (['list', 'create', 'edit', 'delete'] as $operation) {
            $old = DB::table('permissions')->where('name', "$from-$operation")->first();
            if ($old && DB::table('permissions')->where('name', "$to-$operation")->exists()) {
                throw new RuntimeException('Conflicting calendar permission names require review before migration.');
            }
        }
        foreach (['list', 'create', 'edit', 'delete'] as $operation) {
            DB::table('permissions')->where('name', "$from-$operation")->update(['name' => "$to-$operation"]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $this->permissions('term', 'semester');
        Schema::dropIfExists('term_templates');
        Schema::table('terms', function (Blueprint $t) {
            $t->dropIndex(['school_id', 'session_year_id', 'start_date', 'end_date']);
            $t->dropColumn(['grade_from', 'grade_to', 'marks_lock_on', 'reports_on']);
        });
        foreach (self::LINKS as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign(['term_id']));
        }
        Schema::table('class_session_configs', fn (Blueprint $t) => $t->renameColumn('include_terms', 'include_semesters'));
        Schema::rename('terms', 'semesters');
        foreach (self::LINKS as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->renameColumn('term_id', 'semester_id');
                $fk = $t->foreign('semester_id')->references('id')->on('semesters');
                if (in_array($table, ['lessons', 'assignments', 'announcements', 'class_teachers', 'subject_teachers', 'exams', 'online_exams'], true)) {
                    $fk->cascadeOnDelete();
                }
            });
        }
    }
};

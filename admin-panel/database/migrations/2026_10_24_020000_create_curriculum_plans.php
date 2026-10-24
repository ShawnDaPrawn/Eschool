<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = ['curriculum-read', 'curriculum-manage', 'curriculum-publish'];

    public function up(): void
    {
        Schema::create('curriculum_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('source_plan_id')->nullable()->constrained('curriculum_plans')->restrictOnDelete();
            $t->string('title', 191);
            $t->foreignId('standard_id')->nullable()->constrained('subject_standards')->restrictOnDelete();
            $t->json('standard_snapshot')->nullable();
            $t->json('annual_components')->nullable();
            $t->string('rounding', 16)->default('half_up');
            $t->string('absent_policy', 16)->default('incomplete');
            $t->string('state', 16)->default('draft');
            $t->string('fingerprint', 64);
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['school_id', 'subject_id', 'grade_level_id', 'session_year_id', 'version'], 'curriculum_context_version');
        });
        Schema::create('curriculum_topics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('curriculum_plan_id')->constrained('curriculum_plans')->restrictOnDelete();
            $t->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $t->uuid('key');
            $t->string('name', 191);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->unsignedTinyInteger('week_from')->nullable();
            $t->unsignedTinyInteger('week_to')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'curriculum_plan_id', 'term_id']);
        });
        Schema::create('assessment_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('curriculum_plan_id')->constrained('curriculum_plans')->restrictOnDelete();
            $t->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $t->json('components');
            $t->string('state', 16)->default('draft');
            $t->foreignId('published_by')->nullable()->constrained('users');
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'term_id', 'state']);
        });
        Schema::create('assessment_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('assessment_plan_id')->constrained('assessment_plans')->restrictOnDelete();
            $t->uuid('key');
            $t->foreignId('source_task_id')->nullable()->constrained('assessment_tasks')->restrictOnDelete();
            $t->string('name', 191);
            $t->string('type', 40);
            $t->string('component', 24);
            $t->string('annual_component', 24)->nullable();
            $t->unsignedSmallInteger('weight_bp');
            $t->unsignedSmallInteger('annual_weight_bp')->default(0);
            $t->unsignedInteger('max_mark_units');
            $t->date('due_on')->nullable();
            $t->json('topic_keys');
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'assessment_plan_id']);
            $t->index(['school_id', 'key']);
        });
        Schema::create('assessment_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('assessment_task_id')->constrained('assessment_tasks')->restrictOnDelete();
            $t->string('number', 24);
            $t->unsignedInteger('max_mark_units');
            $t->json('topic_keys');
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'assessment_task_id']);
        });
        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            $roles = DB::table('roles')->whereIn('name', $name === 'curriculum-publish' ? ['Super Admin'] : ['Super Admin', 'Teacher'])->pluck('id');
            foreach ($roles as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['assessment_questions', 'assessment_tasks', 'assessment_plans', 'curriculum_topics', 'curriculum_plans'] as $table) {
            Schema::dropIfExists($table);
        }
        $ids = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

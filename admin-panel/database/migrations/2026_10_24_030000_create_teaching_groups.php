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
        Schema::create('teaching_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('class_section_id')->nullable()->constrained('class_sections')->restrictOnDelete();
            $t->string('name', 191);
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'session_year_id', 'subject_id']);
        });
        Schema::create('teaching_group_memberships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('reason', 500);
            $t->string('mark_rule', 16)->default('catch_up');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'learner_id', 'starts_on']);
        });
        Schema::create('teaching_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('reason', 500);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'teacher_id', 'starts_on']);
        });
        Schema::create('homeroom_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('class_section_id')->constrained('class_sections')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('reason', 500);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'class_section_id', 'session_year_id'], 'homeroom_allocations_context');
        });
        Schema::create('academic_leaderships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->restrictOnDelete();
            $t->string('kind', 16);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('reason', 500);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'user_id', 'session_year_id']);
        });
        Schema::table('subject_teachers', fn (Blueprint $t) => $t->foreignId('teaching_allocation_id')->nullable()->constrained('teaching_allocations')->restrictOnDelete());
        Schema::table('class_teachers', fn (Blueprint $t) => $t->foreignId('homeroom_allocation_id')->nullable()->constrained('homeroom_allocations')->restrictOnDelete());
        DB::table('permissions')->insertOrIgnore(['name' => 'academic-structure-manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', 'academic-structure-manage')->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('subject_teachers', fn (Blueprint $t) => $t->dropConstrainedForeignId('teaching_allocation_id'));
        Schema::table('class_teachers', fn (Blueprint $t) => $t->dropConstrainedForeignId('homeroom_allocation_id'));
        foreach (['academic_leaderships', 'homeroom_allocations', 'teaching_allocations', 'teaching_group_memberships', 'teaching_groups'] as $table) {
            Schema::dropIfExists($table);
        }
        $ids = DB::table('permissions')->where('name', 'academic-structure-manage')->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

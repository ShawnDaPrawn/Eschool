<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const TABLES = ['activity_materials', 'activity_attendance', 'activity_sessions', 'activity_group_staff', 'activity_group_memberships', 'activity_groups', 'activity_operations'];

    public function up(): void
    {
        Schema::create('activity_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'activity_operation_identity');
        });
        Schema::create('activity_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('parent_id')->nullable()->constrained('activity_groups');
            $t->string('name', 191);
            $t->string('discipline', 100);
            $t->string('season', 100);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('gender', 16)->default('any');
            $t->unsignedTinyInteger('minimum_grade')->nullable();
            $t->unsignedTinyInteger('maximum_grade')->nullable();
            $t->unsignedTinyInteger('minimum_age')->nullable();
            $t->unsignedTinyInteger('maximum_age')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'session_year_id', 'parent_id'], 'activity_tree_lookup');
        });
        foreach (['activity_group_memberships', 'activity_group_staff'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->foreignId('school_id')->constrained('schools');
                $t->foreignId('activity_group_id')->constrained('activity_groups');
                if ($table === 'activity_group_memberships') {
                    $t->foreignId('learner_id')->constrained('students');
                } else {
                    $t->foreignId('user_id')->constrained('users');
                    $t->boolean('cascade')->default(false);
                }
                $t->string('role', 24);
                $t->boolean('active')->default(true);
                $t->date('starts_on');
                $t->date('ends_on');
                $t->text('reason');
                $t->unsignedInteger('revision')->default(1);
                $t->timestamps();
                $t->index(['school_id', 'activity_group_id', 'starts_on', 'ends_on'], $table.'_dates');
            });
        }
        Schema::create('activity_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('activity_group_id')->constrained('activity_groups');
            $t->date('on_date');
            $t->string('name', 191);
            $t->string('venue', 191);
            $t->string('status', 20)->default('scheduled');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'activity_group_id', 'on_date']);
        });
        Schema::create('activity_attendance', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('activity_session_id')->constrained('activity_sessions');
            $t->foreignId('learner_id')->constrained('students');
            $t->string('status', 20);
            $t->foreignId('recorded_by')->constrained('users');
            $t->text('reason');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'activity_session_id', 'learner_id'], 'activity_attendance_identity');
        });
        Schema::create('activity_materials', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('activity_group_id')->constrained('activity_groups');
            $t->foreignId('author_id')->constrained('users');
            $t->string('title', 191);
            $t->longText('markdown');
            $t->boolean('published')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'activity_group_id', 'published']);
        });
        DB::table('permissions')->insertOrIgnore(['name' => 'activity-groups-manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $id = DB::table('permissions')->where('name', 'activity-groups-manage')->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained activity records prevent destructive rollback. Restore the approved database backup.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

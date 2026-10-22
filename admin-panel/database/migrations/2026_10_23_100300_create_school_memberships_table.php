<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How people belong to schools (Build Bible, section 7, step 2): one row per
 * user, school and role, with a status and dates, so nothing historical is
 * overwritten when a role ends. Every existing user with a role becomes a
 * member of school 1 with each role they hold, from the day their account
 * was created; soft-deleted users' memberships are ended.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('role', 125)->comment('the role name at this school, as in roles.name');
            $table->string('status', 16)->default('active')->comment('active, suspended or ended');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'school_id', 'role']);
            $table->index(['school_id', 'role']);
        });

        $now = now();
        DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->orderBy('users.id')
            ->select(['users.id as user_id', 'roles.name as role', 'users.created_at', 'users.deleted_at'])
            ->chunk(1000, function ($rows) use ($now) {
                DB::table('school_memberships')->insertOrIgnore($rows->map(fn ($row) => [
                    'user_id' => $row->user_id,
                    'school_id' => 1,
                    'role' => $row->role,
                    'status' => $row->deleted_at === null ? 'active' : 'ended',
                    'starts_on' => $row->created_at === null ? null : substr((string) $row->created_at, 0, 10),
                    'ends_on' => $row->deleted_at === null ? null : substr((string) $row->deleted_at, 0, 10),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_memberships');
    }
};

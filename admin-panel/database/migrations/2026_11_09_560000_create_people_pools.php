<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * The school's alumni and volunteer pool (Action Plan 5.6). School-owned:
 * a school invites its own former learners (from its leaver records) and
 * outside volunteers, records each person's consent, vets them (status,
 * who checked, expiry) and books vetted people into group activity sessions
 * as career talk speakers or job-shadow hosts.
 *
 * Pool accounts get no school membership and no role: they reach only the
 * pool member endpoints, never learner records. Vetting history and
 * bookings are retained, so the rollback refuses while rows exist.
 * engineering/specs/community/people-pools.md.
 */
return new class extends Migration
{
    private const TABLES = ['people_pool_bookings', 'people_pool_vettings', 'people_pool_members', 'people_pool_operations'];

    private const PERMISSIONS = ['people-pool-manage', 'people-pool-vet'];

    public function up(): void
    {
        Schema::create('people_pool_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->text('receipt')->comment('encrypted record ids only, never names or contact details');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'people_pool_operation_identity');
        });
        Schema::create('people_pool_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users')->comment('a dedicated pool account with no school membership or role');
            $t->string('kind', 16)->comment('alumnus or volunteer');
            $t->foreignId('learner_id')->nullable()->constrained('students')->comment('the school\'s own leaver record, for an alumnus');
            $t->string('occupation', 191)->nullable();
            $t->string('organisation', 191)->nullable();
            $t->boolean('offers_career_talk')->default(false);
            $t->boolean('offers_job_shadow')->default(false);
            $t->string('consent_status', 16)->default('invited')->comment('invited, given or withdrawn');
            $t->string('consent_version', 64)->nullable();
            $t->timestamp('consented_at')->nullable();
            $t->timestamp('withdrawn_at')->nullable();
            $t->string('vetting_status', 16)->default('pending')->comment('pending, cleared, refused or revoked');
            $t->foreignId('vetted_by')->nullable()->constrained('users');
            $t->date('vetted_on')->nullable();
            $t->date('vetting_expires_on')->nullable();
            $t->foreignId('invited_by')->constrained('users');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'user_id'], 'people_pool_member_account');
            $t->unique(['school_id', 'learner_id'], 'people_pool_member_leaver');
            $t->index(['school_id', 'consent_status', 'vetting_status', 'vetting_expires_on'], 'people_pool_member_bookable');
        });
        Schema::create('people_pool_vettings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('pool_member_id');
            $t->string('outcome', 16)->comment('cleared, refused or revoked');
            $t->json('checks')->comment('the checks done, by key; never certificate numbers');
            $t->foreignId('checked_by')->constrained('users');
            $t->date('checked_on');
            $t->date('expires_on')->nullable();
            $t->text('note')->nullable()->comment('encrypted, for the vetting team only');
            $t->timestamp('created_at')->nullable();
            $t->foreign(['school_id', 'pool_member_id'], 'people_pool_vetting_member')->references(['school_id', 'id'])->on('people_pool_members');
            $t->index(['school_id', 'pool_member_id', 'id'], 'people_pool_vetting_history');
        });
        Schema::create('people_pool_bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('pool_member_id');
            $t->foreignId('activity_group_id')->constrained('activity_groups');
            $t->foreignId('activity_session_id')->constrained('activity_sessions');
            $t->string('kind', 16)->comment('career_talk or job_shadow');
            $t->string('status', 16)->default('booked')->comment('booked or cancelled');
            $t->string('cancel_reason', 32)->nullable()->comment('school, consent_withdrawn');
            $t->foreignId('booked_by')->constrained('users');
            $t->foreignId('cancelled_by')->nullable()->constrained('users');
            $t->timestamp('cancelled_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->foreign(['school_id', 'pool_member_id'], 'people_pool_booking_member')->references(['school_id', 'id'])->on('people_pool_members');
            $t->unique(['school_id', 'activity_session_id', 'pool_member_id'], 'people_pool_booking_identity');
            $t->index(['school_id', 'pool_member_id', 'status'], 'people_pool_booking_member_status');
        });
        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            foreach (DB::table('roles')->where('name', 'Super Admin')->pluck('id') as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $id, 'role_id' => $role]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Retained pool consent, vetting and booking records prevent destructive rollback. Restore the approved database backup.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

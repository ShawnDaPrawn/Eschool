<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Emergency management (engineering/specs/community/emergency-management.md):
 * critical alerts and lockdowns, the drill log, visitor sign-in at the gate
 * and reunification. Every row belongs to one school.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['emergency-alert-send', 'emergency-respond', 'emergency-manage'];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::create('emergency_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools');
            $t->unsignedSmallInteger('visitor_retention_days')->default(30);
            $t->unsignedInteger('revision')->default(0);
            $t->foreignId('changed_by')->nullable()->constrained('users');
            $t->timestamps();
        });
        Schema::create('emergency_drills', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('type', 16);
            $t->string('status', 16)->default('planned');
            $t->dateTime('planned_at');
            $t->dateTime('started_at')->nullable();
            $t->dateTime('ended_at')->nullable();
            $t->text('notes')->nullable();
            $t->uuid('alert_id')->nullable();
            $t->foreignId('recorded_by')->constrained('users');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'planned_at']);
        });
        Schema::create('emergency_alerts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('kind', 16);
            $t->string('audience', 16);
            $t->boolean('is_drill')->default(false);
            $t->string('status', 16)->default('pending');
            $t->string('title', 120);
            $t->text('message');
            $t->uuid('drill_id')->nullable();
            $t->foreignId('prepared_by')->constrained('users');
            $t->dateTime('prepared_at');
            $t->foreignId('confirmed_by')->nullable()->constrained('users');
            $t->dateTime('activated_at')->nullable();
            $t->foreignId('all_clear_by')->nullable()->constrained('users');
            $t->dateTime('all_clear_at')->nullable();
            $t->text('all_clear_message')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users');
            $t->dateTime('cancelled_at')->nullable();
            $t->foreignId('notification_id')->nullable()->constrained('notifications');
            $t->foreignId('all_clear_notification_id')->nullable()->constrained('notifications');
            $t->unsignedInteger('recipient_count')->default(0);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'status']);
            $t->foreign(['school_id', 'drill_id'])->references(['school_id', 'id'])->on('emergency_drills');
        });
        Schema::table('emergency_drills', function (Blueprint $t) {
            $t->foreign(['school_id', 'alert_id'])->references(['school_id', 'id'])->on('emergency_alerts');
        });
        Schema::create('emergency_roll_calls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('alert_id');
            $t->unsignedBigInteger('learner_id');
            $t->string('status', 16);
            $t->foreignId('marked_by')->constrained('users');
            $t->dateTime('marked_at');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'alert_id', 'learner_id']);
            $t->foreign(['school_id', 'alert_id'])->references(['school_id', 'id'])->on('emergency_alerts');
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students');
        });
        Schema::create('emergency_releases', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('alert_id');
            $t->unsignedBigInteger('learner_id');
            $t->string('released_to', 16);
            $t->foreignId('guardian_user_id')->nullable()->constrained('users');
            $t->uuid('gate_pass_id')->nullable();
            $t->text('collector_name')->nullable();
            $t->string('verification', 16);
            $t->uuid('scan_id')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('released_by')->constrained('users');
            $t->dateTime('released_at');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'alert_id', 'learner_id']);
            $t->foreign(['school_id', 'alert_id'])->references(['school_id', 'id'])->on('emergency_alerts');
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students');
            $t->foreign(['school_id', 'gate_pass_id'])->references(['school_id', 'id'])->on('gate_passes');
            $t->foreign(['school_id', 'scan_id'])->references(['school_id', 'id'])->on('card_scan_logs');
        });
        Schema::create('emergency_visitors', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('method', 8);
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->uuid('scan_id')->nullable();
            $t->text('name')->nullable();
            $t->text('phone')->nullable();
            $t->text('organisation')->nullable();
            $t->text('reason')->nullable();
            $t->text('host')->nullable();
            $t->dateTime('signed_in_at');
            $t->foreignId('signed_in_by')->constrained('users');
            $t->dateTime('signed_out_at')->nullable();
            $t->foreignId('signed_out_by')->nullable()->constrained('users');
            $t->dateTime('redacted_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'scan_id']);
            $t->index(['school_id', 'signed_out_at', 'signed_in_at']);
            $t->index(['school_id', 'redacted_at', 'signed_in_at']);
            $t->foreign(['school_id', 'scan_id'])->references(['school_id', 'id'])->on('card_scan_logs');
        });
        Schema::create('emergency_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('kind', 40);
            $t->char('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'emergency_operation_identity');
        });
        Schema::create('emergency_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('alert_id')->nullable();
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->string('kind', 40);
            $t->string('subject_id', 36)->nullable();
            $t->json('details')->nullable();
            $t->dateTime('created_at');
            $t->index(['school_id', 'alert_id', 'created_at']);
            $t->index(['school_id', 'kind', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['emergency_events', 'emergency_alerts', 'emergency_releases', 'emergency_drills'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Emergency rollback refused: keep the alert, release and drill history.');
            }
        }
        if (Schema::hasTable('emergency_drills')) {
            Schema::table('emergency_drills', fn (Blueprint $t) => $t->dropForeign(['school_id', 'alert_id']));
        }
        foreach (['emergency_events', 'emergency_operations', 'emergency_visitors', 'emergency_releases', 'emergency_roll_calls', 'emergency_alerts', 'emergency_drills', 'emergency_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

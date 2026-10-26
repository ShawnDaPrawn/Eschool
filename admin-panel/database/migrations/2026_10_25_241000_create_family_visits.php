<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $name = 'family-visits-manage';
        DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::create('family_visit_rules', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->unique()->constrained('schools');
            $t->unsignedInteger('max_window_minutes')->default(240);
            $t->unsignedInteger('max_future_days')->default(30);
            $t->unsignedInteger('scan_max_age_seconds')->default(120);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('meeting_slots', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('host_id')->constrained('users');
            $t->foreignId('created_by')->constrained('users');
            $t->text('title');
            $t->text('venue');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->dateTime('booking_closes_at');
            $t->unsignedBigInteger('learner_id')->nullable();
            $t->foreignId('guardian_user_id')->nullable()->constrained('users');
            $t->string('status', 16)->default('open');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'host_id', 'starts_at']);
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students');
        });
        Schema::create('gate_passes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('learner_id');
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->text('collector');
            $t->text('reason');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('status', 16)->default('pending');
            $t->unsignedInteger('revision')->default(1);
            $t->dateTime('collected_at')->nullable();
            $t->uuid('collected_device_id')->nullable();
            $t->uuid('collected_scan_id')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'learner_id', 'status']);
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students');
            $t->foreign(['school_id', 'collected_device_id'])->references(['school_id', 'id'])->on('card_reader_devices');
            $t->foreign(['school_id', 'collected_scan_id'])->references(['school_id', 'id'])->on('card_scan_logs');
        });
        foreach (['family_visit_operations', 'family_visit_events'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->uuid('id')->primary();
                $t->foreignId('school_id')->constrained('schools');
                $t->string('actor_type', 16);
                $t->string('actor_id', 64);
                $t->string('kind', 40);
                if ($table === 'family_visit_operations') {
                    $t->uuid('operation_id');
                    $t->char('request_hash', 64);
                    $t->longText('receipt');
                    $t->unique(['school_id', 'actor_type', 'actor_id', 'operation_id'], 'visit_operation_identity');
                } else {
                    $t->uuid('source_id');
                    $t->longText('snapshot');
                    $t->index(['school_id', 'source_id', 'created_at']);
                }
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        if ((Schema::hasTable('family_visit_events') && DB::table('family_visit_events')->exists()) || (Schema::hasTable('meeting_slots') && DB::table('meeting_slots')->exists()) || (Schema::hasTable('gate_passes') && DB::table('gate_passes')->exists())) {
            throw new RuntimeException('Visit rollback refused: retain family appointment and collection histories.');
        }
        foreach (['family_visit_events', 'family_visit_operations', 'gate_passes', 'meeting_slots', 'family_visit_rules'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

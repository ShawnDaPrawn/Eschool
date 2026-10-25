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
        foreach (['cards-manage', 'cards-readers-manage'] as $name) {
            DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $permission = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
            foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::create('cards', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users');
            $t->string('type', 16);
            $t->string('status', 16)->default('active');
            $t->unsignedInteger('revision')->default(1);
            $t->dateTime('issued_at');
            $t->dateTime('frozen_at')->nullable();
            $t->uuid('replaced_by')->nullable();
            $t->string('active_identity', 100)->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'active_identity']);
            $t->index(['school_id', 'user_id', 'status']);
        });
        Schema::create('card_keys', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('public_key', 64);
            $t->text('secret_key');
            $t->dateTime('starts_at');
            $t->dateTime('sign_until');
            $t->dateTime('verify_until');
            $t->dateTime('revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
        });
        Schema::create('card_portraits', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('uploaded_by')->constrained('users');
            $t->string('path', 512);
            $t->char('digest', 64);
            $t->unsignedInteger('size');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'user_id']);
        });
        Schema::create('card_reader_devices', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('issued_by')->constrained('users');
            $t->string('name', 100);
            $t->string('kind', 16);
            $t->string('status', 16)->default('active');
            $t->char('secret_hash', 64);
            $t->string('public_key', 64);
            $t->unsignedInteger('revision')->default(1);
            $t->dateTime('expires_at');
            $t->dateTime('revoked_at')->nullable();
            $t->foreignId('teacher_user_id')->nullable()->constrained('users');
            $t->unsignedBigInteger('session_year_id')->nullable();
            $t->unsignedBigInteger('class_section_id')->nullable();
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years');
            $t->foreign(['school_id', 'class_section_id'])->references(['school_id', 'id'])->on('class_sections');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'status']);
        });
        Schema::create('card_chip_profiles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('card_id');
            $t->uuid('encoder_device_id');
            $t->text('uid');
            $t->char('uid_hash', 64);
            $t->text('metadata_key');
            $t->text('file_read_key');
            $t->unsignedInteger('key_version')->default(1);
            $t->integer('last_counter')->default(-1);
            $t->longText('encoding_package');
            $t->dateTime('encoded_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'card_id']);
            $t->unique(['school_id', 'uid_hash']);
            $t->foreign(['school_id', 'card_id'])->references(['school_id', 'id'])->on('cards');
            $t->foreign(['school_id', 'encoder_device_id'])->references(['school_id', 'id'])->on('card_reader_devices');
        });
        Schema::create('card_qr_uses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('card_id');
            $t->uuid('device_id');
            $t->uuid('key_id');
            $t->char('nonce_hash', 64);
            $t->dateTime('expires_at');
            $t->dateTime('used_at');
            $t->unique(['school_id', 'nonce_hash']);
            $t->index(['school_id', 'expires_at']);
            $t->foreign(['school_id', 'card_id'])->references(['school_id', 'id'])->on('cards');
            $t->foreign(['school_id', 'device_id'])->references(['school_id', 'id'])->on('card_reader_devices');
            $t->foreign(['school_id', 'key_id'])->references(['school_id', 'id'])->on('card_keys');
        });
        Schema::create('card_scan_logs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('device_id');
            $t->uuid('card_id')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->string('method', 16);
            $t->string('outcome', 24);
            $t->dateTime('scanned_at');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'scanned_at']);
            $t->foreign(['school_id', 'card_id'])->references(['school_id', 'id'])->on('cards');
            $t->foreign(['school_id', 'device_id'])->references(['school_id', 'id'])->on('card_reader_devices');
        });
        Schema::create('card_classroom_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('device_id');
            $t->uuid('card_id');
            $t->uuid('scan_id');
            $t->foreignId('user_id')->constrained('users');
            $t->char('secret_hash', 64);
            $t->dateTime('expires_at');
            $t->dateTime('revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'expires_at']);
            $t->foreign(['school_id', 'device_id'])->references(['school_id', 'id'])->on('card_reader_devices');
            $t->foreign(['school_id', 'card_id'])->references(['school_id', 'id'])->on('cards');
            $t->foreign(['school_id', 'scan_id'])->references(['school_id', 'id'])->on('card_scan_logs');
        });
        Schema::create('card_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('card_id');
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->string('action', 40);
            $t->text('reason')->nullable();
            $t->unsignedInteger('revision');
            $t->dateTime('occurred_at');
            $t->foreign(['school_id', 'card_id'])->references(['school_id', 'id'])->on('cards');
            $t->index(['school_id', 'card_id', 'id']);
        });
        Schema::create('card_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('actor_kind', 10);
            $t->string('actor_ref', 40);
            $t->uuid('operation_id');
            $t->string('kind', 40);
            $t->char('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_kind', 'actor_ref', 'operation_id'], 'card_operation_identity');
        });
    }

    public function down(): void
    {
        foreach (['cards', 'card_keys', 'card_portraits', 'card_reader_devices'] as $name) {
            if (Schema::hasTable($name) && DB::table($name)->exists()) {
                throw new RuntimeException('Issued card identities, portraits, keys and registered readers must be retained; rollback refused.');
            }
        }
        foreach (['card_operations', 'card_events', 'card_classroom_sessions', 'card_scan_logs', 'card_qr_uses', 'card_chip_profiles', 'card_reader_devices', 'card_portraits', 'card_keys', 'cards'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};

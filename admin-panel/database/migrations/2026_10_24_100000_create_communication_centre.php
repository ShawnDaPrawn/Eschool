<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const TABLES = ['message_reads', 'message_recipients', 'messages', 'communication_operations', 'communication_role_rules', 'audiences'];

    public function up(): void
    {
        Schema::create('audiences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('created_by')->constrained('users');
            $t->text('name');
            $t->longText('definition');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'active', 'created_by']);
        });
        Schema::create('communication_role_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $t->string('role_label', 191);
            $t->boolean('allow_school')->default(false);
            $t->boolean('requires_approval')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'role_id']);
        });
        Schema::create('communication_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('kind', 40);
            $t->string('request_hash', 64);
            $t->text('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'communication_operation_identity');
        });
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('author_id')->constrained('users');
            $t->text('title');
            $t->longText('markdown');
            $t->longText('audience');
            $t->json('channels');
            $t->string('state', 20)->default('draft');
            $t->boolean('requires_approval')->default(true);
            $t->dateTime('scheduled_at')->nullable();
            $t->dateTime('submitted_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->dateTime('approved_at')->nullable();
            $t->text('decision_reason')->nullable();
            $t->string('blocked_code', 64)->nullable();
            $t->uuid('publication_id')->nullable();
            $t->dateTime('published_at')->nullable();
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->foreignId('review_notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->unsignedBigInteger('review_after_user_id')->default(0);
            $t->unsignedInteger('review_completed_revision')->default(0);
            $t->unsignedBigInteger('delivery_after_user_id')->default(0);
            $t->unsignedInteger('recipient_count')->default(0);
            $t->dateTime('sent_at')->nullable();
            $t->dateTime('withdrawn_at')->nullable();
            $t->text('withdrawal_reason')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'state', 'scheduled_at'], 'communication_due_messages');
            $t->index(['school_id', 'author_id', 'id']);
        });
        Schema::create('message_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('message_id')->constrained('messages');
            $t->foreignId('user_id')->constrained('users');
            $t->text('access_reason');
            $t->dateTime('processed_at')->nullable();
            $t->boolean('notification_queued')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'message_id', 'user_id'], 'communication_recipient_identity');
            $t->index(['school_id', 'user_id', 'message_id']);
        });
        Schema::create('message_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('message_id')->constrained('messages');
            $t->foreignId('user_id')->constrained('users');
            $t->dateTime('read_at');
            $t->timestamps();
            $t->unique(['school_id', 'message_id', 'user_id'], 'communication_read_identity');
        });
        DB::table('permissions')->insertOrIgnore(['name' => 'communication-manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', 'communication-manage')->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained communication data prevents rollback. Restore the prior database backup instead.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        $ids = DB::table('permissions')->where('name', 'communication-manage')->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

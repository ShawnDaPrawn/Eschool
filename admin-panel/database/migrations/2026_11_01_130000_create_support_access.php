<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Audited support access (Action Plan SA.9): each request a Navigate Ed staff
 * member makes to open one school, the school's decision or an Owner's
 * emergency override, the session window, and every page viewed in it (route
 * name and record id, never content). Both tables belong to the school, which
 * sees them in its panel. Specification: engineering/specs/platform/support-access.md.
 */
return new class extends Migration
{
    private const TABLES = ['support_access_views', 'support_access_grants'];

    public function up(): void
    {
        Schema::create('support_access_grants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            // The requester's platform role when they asked (config/platform_roles.php).
            $t->string('requester_role', 32);
            $t->string('reason', 500);
            // What the session may do; only "read" exists (config/support_access.php).
            $t->string('scope', 16)->default('read');
            $t->unsignedSmallInteger('minutes');
            // pending, active, declined, withdrawn or ended; an active grant past expires_at is expired.
            $t->string('status', 12)->default('pending');
            $t->timestamp('requested_at');
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->boolean('emergency')->default(false);
            $t->string('emergency_reason', 500)->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            // Who ended it early: school or staff.
            $t->string('ended_side', 8)->nullable();
            $t->timestamps();
            $t->index(['school_id', 'status'], 'support_access_grants_school_status_index');
            $t->index(['requested_by', 'status'], 'support_access_grants_requester_index');
        });
        Schema::create('support_access_views', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('grant_id')->constrained('support_access_grants')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('route_name', 191);
            // The record the page showed, from its route: parameter name and id. Never content.
            $t->string('record_type', 32)->nullable();
            $t->unsignedBigInteger('record_id')->nullable();
            // The ids of the accounts a list or search showed (never the search words).
            $t->json('listed')->nullable();
            $t->timestamp('viewed_at');
            $t->index(['grant_id', 'viewed_at'], 'support_access_views_grant_index');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain the support access history.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform staff roles (Action Plan SA.1): each HQ admin grant carries one
 * role (config/platform_roles.php), and Owners invite staff by email.
 * Existing grants become Owner, so nobody loses access they already had.
 * engineering/specs/platform/platform-roles.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_admin_grants', function (Blueprint $table) {
            $table->string('role', 20)->default('owner')->after('active');
            $table->unsignedBigInteger('granted_by')->nullable()->after('expires_at');
            $table->string('reason', 500)->nullable()->after('granted_by');
        });

        Schema::create('platform_staff_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->char('email_hash', 64)->comment('SHA-256 of the address it was sent to: a changed email voids the link');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_staff_invitations');
        Schema::table('platform_admin_grants', function (Blueprint $table) {
            $table->dropColumn(['role', 'granted_by', 'reason']);
        });
    }
};

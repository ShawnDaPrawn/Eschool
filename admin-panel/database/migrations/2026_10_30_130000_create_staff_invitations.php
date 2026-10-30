<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Email invitations for a school's first Super Admin and its staff: a private,
 * one-use, expiring link to choose a password, instead of a password someone
 * has to pass on. Specification: engineering/specs/platform/staff-invitations.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_invitations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('purpose', 32);
            $t->char('token_hash', 64)->unique();
            $t->text('encrypted_token');
            $t->char('email_hash', 64);
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'user_id', 'purpose'], 'staff_invitations_school_user_index');
        });
    }

    public function down(): void
    {
        if (DB::table('staff_invitations')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain invitation history.');
        }
        Schema::dropIfExists('staff_invitations');
    }
};

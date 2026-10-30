<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Invitations for a person who already has a NavigateEd account to join
 * another school with a role there (Action Plan 1.3). Nothing is attached to
 * the account until its owner signs in and accepts. Specification:
 * engineering/specs/platform/existing-account-invitations.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_join_invitations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            // The school may delete a role; the invitation then opens nothing.
            $t->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $t->string('purpose', 32);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // What the school typed, shown back only to the school.
            $t->text('encrypted_name');
            $t->text('encrypted_email');
            $t->string('qualification', 191)->nullable();
            $t->char('token_hash', 64)->unique();
            $t->text('encrypted_token');
            $t->char('email_hash', 64);
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('declined_at')->nullable();
            $t->timestamp('withdrawn_at')->nullable();
            $t->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            // Never sent: the account may not join a school this way (the school is not told).
            $t->timestamp('withheld_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'user_id', 'used_at'], 'school_join_invitations_school_user_index');
        });
    }

    public function down(): void
    {
        if (DB::table('school_join_invitations')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain invitation history.');
        }
        Schema::dropIfExists('school_join_invitations');
    }
};

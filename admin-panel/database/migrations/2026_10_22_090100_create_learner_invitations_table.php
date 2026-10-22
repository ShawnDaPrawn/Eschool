<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per way of setting a learner's password: an emailed link for a
 * guardian, or a set-up code the school prints. Only hashes of the link token
 * and the code are kept. A row is used once; using any row closes the
 * learner's other open rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_invitations', function (Blueprint $table) {
            $table->id();
            // Every school-owned table carries school_id; Phase 1 adds the schools table and its key.
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->foreignId('learner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guardian_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 16)->comment('link (emailed to a guardian) or code (printed by the school)');
            $table->char('token_hash', 64)->nullable()->unique();
            $table->string('code_hash')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['learner_user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_invitations');
    }
};

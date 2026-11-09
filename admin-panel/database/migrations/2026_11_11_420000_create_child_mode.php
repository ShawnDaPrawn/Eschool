<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Child mode in the parent app (Action Plan 4.2). School-owned:
 * - child_mode_pins: the PIN an approving guardian sets for one child at
 *   this school, stored only as a hash, with its wrong-attempt count and
 *   lockout. Its version changes with every new PIN, which ends every child
 *   session started with the old one.
 * - child_mode_sessions: one row per child session (who started it, for
 *   which child, from which parent-app sign-in, when it started, when it
 *   expires and why it ended). Times and record ids only, never anything
 *   the child opened, typed or said.
 *
 * Session rows are the audit trail of child sessions, so the rollback
 * refuses while any exist. engineering/specs/platform/child-mode.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_mode_pins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->string('pin_hash', 255)->comment('one-way hash of the PIN; the PIN itself is never stored');
            $t->unsignedInteger('version')->default(1)->comment('changes with every new PIN; sessions started with an older one end');
            $t->foreignId('set_by')->constrained('users');
            $t->unsignedTinyInteger('failed_attempts')->default(0);
            $t->timestamp('locked_until')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'learner_id'], 'child_mode_pin_learner');
        });
        Schema::create('child_mode_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('guardian_user_id')->constrained('users');
            $t->unsignedBigInteger('token_id')->nullable()->comment('the child token; it is deleted when the session ends, this row stays');
            $t->unsignedBigInteger('issued_from_token_id')->nullable()->comment('the guardian\'s parent-app sign-in that started it');
            $t->unsignedInteger('pin_version');
            $t->timestamp('started_at');
            $t->timestamp('expires_at');
            $t->timestamp('ended_at')->nullable();
            $t->string('end_reason', 40)->nullable();
            $t->foreignId('ended_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->unique('token_id', 'child_mode_session_token');
            $t->index(['school_id', 'learner_id', 'ended_at'], 'child_mode_session_learner');
            $t->index(['guardian_user_id', 'ended_at'], 'child_mode_session_guardian');
            $t->index('issued_from_token_id', 'child_mode_session_parent_token');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('child_mode_sessions') && DB::table('child_mode_sessions')->exists()) {
            throw new RuntimeException('Retained child session records prevent destructive rollback. Restore the approved database backup.');
        }
        Schema::dropIfExists('child_mode_sessions');
        Schema::dropIfExists('child_mode_pins');
    }
};

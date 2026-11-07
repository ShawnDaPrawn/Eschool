<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Interests and strengths mirror (Action Plan 4.8). The mirror itself is
 * worked out on every read and never stored. A share is the learner's own
 * choice to show one named guardian or teacher a copy of it: the copy
 * (snapshot) is encrypted, read-only and ends when the learner revokes it or
 * it expires. engineering/specs/learning/strengths-mirror.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strengths_mirror_shares', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            // The learner's own account: only it creates or revokes the share.
            $t->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('viewer_user_id')->constrained('users')->restrictOnDelete();
            // guardian (an approving guardian link) or teacher (teaches the learner).
            $t->string('viewer_kind', 16);
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->json('sections');
            // Encrypted copy of the mirror on the day it was shared.
            $t->longText('snapshot');
            $t->date('snapshot_on');
            $t->timestamp('expires_at');
            $t->timestamp('revoked_at')->nullable();
            $t->timestamp('last_viewed_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'owner_user_id', 'operation_id'], 'strengths_share_operation');
            $t->index(['school_id', 'learner_id', 'revoked_at'], 'strengths_share_learner');
            $t->index(['school_id', 'viewer_user_id', 'expires_at'], 'strengths_share_viewer');
        });
    }

    public function down(): void
    {
        if (DB::table('strengths_mirror_shares')->exists()) {
            throw new RuntimeException('Strengths mirror shares are the learner\'s audited choices: export or let them expire before rolling back.');
        }
        Schema::dropIfExists('strengths_mirror_shares');
    }
};

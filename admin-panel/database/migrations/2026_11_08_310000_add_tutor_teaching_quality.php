<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tutor teaching quality (Action Plan 3.10): each turn records the prompt
 * version it was built for, its turn type (an ordinary question or "explain
 * it back") and an optional home-language explanation. tutor_memories holds
 * only the learner's "clear my tutor memory" mark; the memory itself is
 * derived from completed, unflagged turns inside the retention window.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_messages', function (Blueprint $t) {
            $t->string('prompt_key', 64)->nullable()->after('request_hash');
            $t->string('turn_type', 16)->default('ask')->after('prompt_key');
            $t->string('explain_language', 8)->nullable()->after('turn_type');
        });
        Schema::create('tutor_memories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->timestamp('cleared_at')->nullable();
            $t->foreignId('cleared_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('cleared_by_role', 16)->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'learner_id'], 'tutor_memory_learner');
        });
    }

    public function down(): void
    {
        if (DB::table('tutor_memories')->exists() || DB::table('tutor_messages')->where(fn ($q) => $q->whereNotNull('prompt_key')
            ->orWhere('turn_type', '!=', 'ask')->orWhereNotNull('explain_language'))->exists()) {
            throw new RuntimeException('Retain Tutor teaching-quality records before rollback.');
        }
        Schema::dropIfExists('tutor_memories');
        Schema::table('tutor_messages', function (Blueprint $t) {
            $t->dropColumn(['prompt_key', 'turn_type', 'explain_language']);
        });
    }
};

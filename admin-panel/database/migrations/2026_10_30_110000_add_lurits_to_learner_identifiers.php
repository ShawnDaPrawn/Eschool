<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The learner's LURITS number (the Department of Basic Education's Learner
 * Unit Record Information and Tracking System number) on the human's global
 * identity, one per person across schools. Encrypted, with a keyed hash for
 * uniqueness and lookup. A number another person already holds is never
 * written: it opens a conflict for review instead.
 * Specification: engineering/specs/platform/lurits.md (ADR 0007).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learner_identifiers', function (Blueprint $t) {
            $t->char('lurits_hash', 64)->nullable()->unique()->after('student_number');
            $t->text('lurits_number')->nullable()->after('lurits_hash');
            $t->string('lurits_source', 32)->nullable()->after('lurits_number');
            $t->timestamp('lurits_recorded_at')->nullable()->after('lurits_source');
            $t->foreignId('lurits_recorded_by')->nullable()->after('lurits_recorded_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('lurits_conflicts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->char('lurits_hash', 64)->index();
            $t->foreignId('holder_user_id')->constrained('users');
            $t->string('source', 32);
            $t->string('status', 16)->default('open');
            $t->foreignId('raised_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('resolved_at')->nullable();
            $t->string('resolution', 32)->nullable();
            $t->timestamps();
            $t->index(['learner_id', 'lurits_hash', 'status']);
        });

        // The row LuritsNumbers::record() locks to serialise claims, present
        // before the first claim so claims never have to insert it.
        DB::table('platform_counters')->insertOrIgnore(['name' => 'lurits_claims', 'value' => 0]);
    }

    public function down(): void
    {
        DB::table('platform_counters')->where('name', 'lurits_claims')->delete();
        Schema::dropIfExists('lurits_conflicts');
        Schema::table('learner_identifiers', function (Blueprint $t) {
            $t->dropConstrainedForeignId('lurits_recorded_by');
            $t->dropUnique(['lurits_hash']);
            $t->dropColumn(['lurits_hash', 'lurits_number', 'lurits_source', 'lurits_recorded_at']);
        });
    }
};

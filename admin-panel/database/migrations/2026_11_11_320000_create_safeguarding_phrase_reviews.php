<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Professional reviews of the safeguarding phrase lists (Action Plan 3.2).
 * One row per review recorded in HQ: the language, the outcome, the exact
 * list it covered (sha256 of the list), who reviewed it, when, and a note.
 * A language counts as reviewed only while its newest row says so for the
 * list as it is now; a changed list is draft again. Append-only history.
 * Platform table: no school_id, written only from /saas/safeguarding-phrases.
 * The phrases themselves live in resources/ai/safeguarding and are never
 * stored here. Specification: engineering/specs/ai/safeguarding-phrases.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safeguarding_phrase_reviews', function (Blueprint $t) {
            $t->id();
            $t->string('language', 8);
            // reviewed or changes_needed.
            $t->string('outcome', 16);
            $t->char('list_sha256', 64);
            // The professional who reviewed the list: name and role or organisation.
            $t->string('reviewer', 160);
            $t->date('reviewed_on');
            $t->string('note', 1000);
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['language', 'id'], 'safeguarding_phrase_reviews_language_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('safeguarding_phrase_reviews') && DB::table('safeguarding_phrase_reviews')->exists()) {
            throw new RuntimeException('Safeguarding phrase reviews are kept: restore a tested backup instead of rolling back.');
        }
        Schema::dropIfExists('safeguarding_phrase_reviews');
    }
};

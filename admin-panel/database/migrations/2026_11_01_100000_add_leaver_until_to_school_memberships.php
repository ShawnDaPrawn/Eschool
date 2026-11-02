<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The leaver state (Action Plan 1.21): a learner who leaves at the year
 * switch, and a guardian whose only children at the school left, keep
 * read-only access until leaver_until, instead of being deactivated. Such a
 * membership has status "leaver". Specification:
 * engineering/specs/academics/new-year-structures.md, "Leaver state".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_memberships', function (Blueprint $table) {
            $table->string('status', 16)->default('active')->comment('active, leaver (read-only until leaver_until), suspended or ended')->change();
            $table->date('leaver_until')->nullable()->after('ends_on')->comment('the last day a leaver keeps read-only access');
            $table->index(['school_id', 'status', 'leaver_until']);
        });
    }

    public function down(): void
    {
        // Rolling back would turn every leaver into an active member again.
        if (DB::table('school_memberships')->whereNotNull('leaver_until')->exists()) {
            throw new RuntimeException('school_memberships holds leavers; end or restore them before rolling back.');
        }
        Schema::table('school_memberships', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'status', 'leaver_until']);
            $table->dropColumn('leaver_until');
            $table->string('status', 16)->default('active')->comment('active, suspended or ended')->change();
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Action Plan 4.7 "Every child teaches something": a supervised peer session
 * can teach a recognised activity skill (a captaincy this school year) instead
 * of a mastery topic. Exactly one of topic_id and activity_group_id is set;
 * skill_name keeps the group's name as it was when the session was scheduled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peer_tutoring_sessions', function (Blueprint $t) {
            $t->unsignedBigInteger('topic_id')->nullable()->change();
            $t->foreignId('activity_group_id')->nullable()->after('topic_id')->constrained('activity_groups');
            $t->string('skill_name', 191)->nullable()->after('activity_group_id');
            $t->index(['school_id', 'status', 'starts_at'], 'peer_status_calendar');
        });
    }

    public function down(): void
    {
        if (DB::table('peer_tutoring_sessions')->whereNotNull('activity_group_id')->exists()) {
            throw new RuntimeException('Peer tutoring rollback refused: preserve supervised activity-skill sessions and their service history.');
        }
        Schema::table('peer_tutoring_sessions', function (Blueprint $t) {
            $t->dropIndex('peer_status_calendar');
            $t->dropConstrainedForeignId('activity_group_id');
            $t->dropColumn('skill_name');
            $t->unsignedBigInteger('topic_id')->nullable(false)->change();
        });
    }
};

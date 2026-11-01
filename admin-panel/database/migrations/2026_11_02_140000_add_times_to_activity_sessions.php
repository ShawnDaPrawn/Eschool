<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optional start and end times for activity sessions, so they can sit in the
 * learner and family week view beside lessons (Action Plan 1.6). Existing
 * sessions keep no time and show as all-day. engineering/specs/academics/timetable.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_sessions', function (Blueprint $t) {
            $t->time('starts_at')->nullable()->after('on_date');
            $t->time('ends_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        if (DB::table('activity_sessions')->whereNotNull('starts_at')->orWhereNotNull('ends_at')->exists()) {
            throw new RuntimeException('Retain saved session times before rolling back.');
        }
        Schema::table('activity_sessions', function (Blueprint $t) {
            $t->dropColumn(['starts_at', 'ends_at']);
        });
    }
};

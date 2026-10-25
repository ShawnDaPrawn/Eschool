<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wellbeing_events', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'wellbeing_event_school_identity'));
        Schema::table('support_cases', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'wellbeing_case_school_identity'));
        Schema::create('wellbeing_notice_intents', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('event_id');
            $t->foreignId('recipient_user_id')->constrained('users');
            $t->string('kind', 24);
            $t->unsignedBigInteger('incident_report_id')->nullable();
            $t->unsignedBigInteger('support_case_id')->nullable();
            $t->foreignId('notification_id')->nullable()->constrained('notifications');
            $t->timestamp('queued_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'event_id', 'recipient_user_id', 'kind'], 'wellbeing_notice_once');
            $t->index(['school_id', 'recipient_user_id', 'notification_id'], 'wellbeing_notice_reader');
            $t->foreign(['school_id', 'event_id'], 'wellbeing_notice_event_scope')->references(['school_id', 'id'])->on('wellbeing_events');
            $t->foreign(['school_id', 'incident_report_id'], 'wellbeing_notice_report_scope')->references(['school_id', 'id'])->on('incident_reports');
            $t->foreign(['school_id', 'support_case_id'], 'wellbeing_notice_case_scope')->references(['school_id', 'id'])->on('support_cases');
        });
    }

    public function down(): void
    {
        if (DB::table('wellbeing_notice_intents')->exists()) {
            throw new RuntimeException('Retained private wellbeing notice intents prevent destructive rollback.');
        }
        Schema::dropIfExists('wellbeing_notice_intents');
        // MySQL may replace the original implicit school-FK index with our
        // wider unique key. Restore that supporting index before dropping it.
        foreach (['support_cases', 'wellbeing_events'] as $table) {
            $index = $table.'_school_id_foreign';
            if (! Schema::hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $t) => $t->index('school_id', $index));
            }
        }
        Schema::table('support_cases', fn (Blueprint $t) => $t->dropUnique('wellbeing_case_school_identity'));
        Schema::table('wellbeing_events', fn (Blueprint $t) => $t->dropUnique('wellbeing_event_school_identity'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_band_states', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->string('period_key', 40);
            $t->string('audience', 12);
            $t->string('band', 32);
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'subject_id', 'grade_level_id', 'session_year_id', 'period_key', 'audience'], 'progress_band_scope');
        });
        Schema::create('progress_band_alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('state_id')->constrained('progress_band_states')->restrictOnDelete();
            $t->unsignedInteger('state_revision');
            $t->foreignId('snapshot_id')->constrained('mark_snapshots')->restrictOnDelete();
            $t->foreignId('release_id')->nullable()->constrained('mark_releases')->restrictOnDelete();
            $t->foreignId('group_id')->nullable()->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->restrictOnDelete();
            $t->string('band', 32);
            $t->unsignedInteger('settings_revision');
            $t->timestamp('created_at');
            $t->unique(['school_id', 'state_id', 'state_revision'], 'progress_band_alert_revision');
        });
    }

    public function down(): void
    {
        if (DB::table('progress_band_alerts')->exists() || DB::table('progress_band_states')->exists()) {
            throw new LogicException('Retain progress-band alert history.');
        }
        Schema::dropIfExists('progress_band_alerts');
        Schema::dropIfExists('progress_band_states');
    }
};

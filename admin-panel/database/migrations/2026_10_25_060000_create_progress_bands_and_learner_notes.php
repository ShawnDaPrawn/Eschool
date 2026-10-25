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
        Schema::create('progress_band_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools');
            $t->unsignedSmallInteger('high_bp')->default(8000);
            $t->unsignedSmallInteger('secure_bp')->default(6500);
            $t->unsignedSmallInteger('minimum_coverage_bp')->default(2500);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('learner_note_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('staff_user_id')->constrained('users');
            $t->foreignId('assigned_by')->constrained('users');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'staff_user_id'], 'learner_note_caseload');
        });
        Schema::create('learner_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('subject_id')->nullable()->constrained('subjects');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('author_id')->constrained('users');
            $t->string('visibility', 16);
            $t->longText('body');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'learner_id', 'visibility'], 'learner_note_reader');
        });
        Schema::create('learner_note_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_note_id')->constrained('learner_notes');
            $t->foreignId('author_id')->constrained('users');
            $t->unsignedInteger('revision');
            $t->longText('body');
            $t->boolean('archived')->default(false);
            $t->timestamp('created_at');
            $t->unique(['school_id', 'learner_note_id', 'revision'], 'learner_note_revision_identity');
        });
    }

    public function down(): void
    {
        if (DB::table('learner_notes')->exists() || DB::table('learner_note_assignments')->exists()) {
            throw new RuntimeException('Retained learner notes and assignments block destructive rollback.');
        }
        foreach (['learner_note_revisions', 'learner_notes', 'learner_note_assignments', 'progress_band_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

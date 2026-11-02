<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Early years tools, first slice (Action Plan 4.3): the school's development
 * areas, the daily diary, observations that build a learner's portfolio, and
 * the link from an observation to the intervention its early concern became.
 * Notes are encrypted; photographs stay in the private gallery.
 * engineering/specs/learning/early-years.md.
 */
return new class extends Migration
{
    private const TABLES = ['early_years_concerns', 'early_years_observations', 'early_years_diary_entries', 'early_years_development_areas'];

    public function up(): void
    {
        Schema::create('early_years_development_areas', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('name', 80);
            $t->string('description', 255)->nullable();
            $t->unsignedSmallInteger('position')->default(0);
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'name']);
            $t->index(['school_id', 'active', 'position'], 'early_years_areas_order');
        });
        Schema::create('early_years_diary_entries', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('class_section_id')->constrained('class_sections')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->date('entry_date');
            $t->string('meals', 12)->nullable();
            $t->string('rest', 12)->nullable();
            $t->string('mood', 12)->nullable();
            $t->string('toileting', 12)->nullable();
            $t->text('note')->nullable()->comment('Encrypted');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'entry_date'], 'early_years_diary_day');
            $t->index(['school_id', 'class_section_id', 'entry_date'], 'early_years_diary_class_day');
        });
        Schema::create('early_years_observations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('class_section_id')->constrained('class_sections')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('development_area_id')->constrained('early_years_development_areas')->restrictOnDelete();
            $t->date('observed_on');
            $t->text('note')->nullable()->comment('Encrypted');
            $t->foreignId('gallery_photo_id')->nullable()->unique()->constrained('gallery_photos')->restrictOnDelete();
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->dateTime('withdrawn_at')->nullable();
            $t->foreignId('withdrawn_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'recorded_by', 'operation_id'], 'early_years_observation_operation');
            $t->index(['school_id', 'learner_id', 'withdrawn_at', 'observed_on'], 'early_years_portfolio');
        });
        Schema::create('early_years_concerns', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('observation_id')->unique()->constrained('early_years_observations')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('intervention_id')->unique()->constrained('interventions')->restrictOnDelete();
            $t->foreignId('raised_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'learner_id']);
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new LogicException('Retain early years records: '.$table.' holds rows.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

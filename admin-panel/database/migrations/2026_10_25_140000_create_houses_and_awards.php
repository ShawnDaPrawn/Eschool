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
        Schema::create('houses', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('name', 120);
            $t->string('emblem', 32);
            $t->char('primary_colour', 7);
            $t->char('secondary_colour', 7);
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('house_memberships', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('house_id')->constrained('houses');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->boolean('active')->default(true);
            $t->longText('reason');
            $t->unsignedInteger('revision')->default(1);
            $t->index(['school_id', 'learner_id', 'session_year_id'], 'house_member_period');
            $t->timestamps();
        });
        Schema::create('point_categories', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('name', 120);
            $t->integer('default_points');
            $t->integer('minimum_points');
            $t->integer('maximum_points');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('recognition_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedInteger('daily_staff_cap')->default(50);
            $t->boolean('public_points_allowed')->default(false);
            $t->boolean('principal_approval_required')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->unique('school_id', 'recognition_school_settings');
            $t->timestamps();
        });
        Schema::create('house_point_limits', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('staff_id')->constrained('users');
            $t->date('on_date');
            $t->unsignedBigInteger('spent')->default(0);
            $t->unique(['school_id', 'staff_id', 'on_date'], 'point_staff_daily_limit');
            $t->timestamps();
        });
        Schema::create('house_points', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('house_id')->constrained('houses');
            $t->foreignId('point_category_id')->constrained('point_categories');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('staff_id')->constrained('users');
            $t->string('house_name', 120);
            $t->string('category_name', 120);
            $t->bigInteger('points');
            $t->date('on_date');
            $t->longText('reason');
            $t->boolean('public')->default(false);
            $t->string('scope_kind', 32);
            $t->unsignedBigInteger('scope_id')->nullable();
            $t->foreignId('reverses_id')->nullable()->constrained('house_points');
            $t->unique(['school_id', 'reverses_id'], 'point_one_reversal');
            $t->index(['school_id', 'session_year_id', 'house_id'], 'house_point_counter');
            $t->index(['school_id', 'learner_id', 'id'], 'house_point_learner_history');
            $t->timestamps();
        });
        Schema::create('award_categories', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('parent_id')->nullable()->constrained('award_categories');
            $t->string('name', 160);
            $t->string('scope_kind', 16);
            $t->foreignId('grade_level_id')->nullable()->constrained('grade_levels');
            $t->foreignId('subject_id')->nullable()->constrained('subjects');
            $t->foreignId('activity_group_id')->nullable()->constrained('activity_groups');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('award_category_admins', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('award_category_id')->constrained('award_categories');
            $t->foreignId('user_id')->constrained('users');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->index(['school_id', 'user_id', 'award_category_id'], 'award_category_administrators');
            $t->timestamps();
        });
        Schema::create('award_windows', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('award_category_id')->constrained('award_categories');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->date('opens_on');
            $t->date('closes_on');
            $t->string('status', 16)->default('draft');
            $t->unsignedInteger('revision')->default(1);
            $t->unique(['school_id', 'award_category_id', 'session_year_id'], 'award_year_window');
            $t->timestamps();
        });
        Schema::create('award_definitions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('award_category_id')->constrained('award_categories');
            $t->string('name', 160);
            $t->text('criteria');
            $t->string('frequency', 16)->default('annual');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('award_nominations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('award_category_id')->constrained('award_categories');
            $t->foreignId('award_definition_id')->constrained('award_definitions');
            $t->foreignId('award_window_id')->constrained('award_windows');
            $t->foreignId('nominated_by')->constrained('users');
            $t->longText('reason');
            $t->string('state', 16)->default('pending');
            $t->string('scope_kind', 32);
            $t->unsignedBigInteger('scope_id')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users');
            $t->longText('review_reason')->nullable();
            $t->string('definition_name', 160);
            $t->text('definition_criteria');
            $t->string('definition_frequency', 16);
            $t->unsignedInteger('definition_revision');
            $t->unsignedInteger('revision')->default(1);
            $t->index(['school_id', 'session_year_id', 'award_category_id', 'state'], 'award_nomination_queue');
            $t->timestamps();
        });
        Schema::create('award_nomination_versions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('award_nomination_id')->constrained('award_nominations');
            $t->foreignId('actor_id')->constrained('users');
            $t->unsignedInteger('revision');
            $t->longText('snapshot');
            $t->unique(['school_id', 'award_nomination_id', 'revision'], 'award_nomination_version');
            $t->timestamps();
        });
        Schema::create('award_presentations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->foreignId('grade_level_id')->constrained('grade_levels');
            $t->foreignId('award_category_id')->nullable()->constrained('award_categories');
            $t->foreignId('award_definition_id')->nullable()->constrained('award_definitions');
            $t->foreignId('award_nomination_id')->nullable()->constrained('award_nominations');
            $t->foreignId('presented_by')->constrained('users');
            $t->date('presented_on');
            $t->string('title', 160);
            $t->text('criteria');
            $t->string('event', 191)->nullable();
            $t->string('outside_organisation', 160)->nullable();
            $t->string('source_kind', 16);
            $t->string('state', 16)->default('active');
            $t->unsignedInteger('revision')->default(1);
            $t->char('dedupe_key', 64)->nullable();
            $t->foreignId('voided_by')->nullable()->constrained('users');
            $t->longText('void_reason')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->unique(['school_id', 'dedupe_key'], 'award_annual_presentation');
            $t->unique(['school_id', 'award_nomination_id'], 'award_nomination_presented_once');
            $t->index(['school_id', 'learner_id', 'session_year_id'], 'award_permanent_profile');
            $t->timestamps();
        });
        Schema::create('recognition_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->string('kind', 32);
            $t->unsignedBigInteger('record_id');
            $t->unsignedInteger('revision');
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'recognition_exact_operation');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        $tables = ['recognition_operations', 'award_presentations', 'award_nomination_versions', 'award_nominations', 'award_definitions', 'award_windows', 'award_category_admins', 'award_categories', 'house_points', 'house_point_limits', 'recognition_settings', 'point_categories', 'house_memberships', 'houses'];
        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained house and award records prevent rollback.');
            }
        }
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records shared between schools (engineering/specs/platform/shared-records.md)
 * and their first use, inter-school fixtures and competitions
 * (engineering/specs/activities/inter-school-fixtures.md).
 *
 * The shared rows (shared_competitions, shared_fixtures) name no school:
 * schools are linked to them only through their own shared_participants
 * row. Everything private to one school (its group, team sheet, notes and
 * its own score report) is in school-owned rows.
 */
return new class extends Migration
{
    private const TABLES = ['shared_fixture_selections', 'shared_fixture_sides', 'shared_participants', 'shared_fixtures', 'shared_competitions', 'shared_record_operations'];

    public function up(): void
    {
        Schema::create('shared_record_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->string('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'shared_operation_identity');
        });
        Schema::create('shared_competitions', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 24)->default('sport');
            $t->string('name', 191);
            $t->string('discipline', 100);
            $t->string('season', 100);
            $t->unsignedTinyInteger('points_win')->default(3);
            $t->unsignedTinyInteger('points_draw')->default(1);
            $t->unsignedTinyInteger('points_loss')->default(0);
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('shared_fixtures', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 24)->default('sport');
            $t->foreignId('shared_competition_id')->nullable()->constrained('shared_competitions');
            $t->string('external_opponent', 191)->nullable();
            $t->boolean('host_is_home')->default(true);
            $t->string('discipline', 100);
            $t->date('on_date');
            $t->time('starts_at')->nullable();
            $t->string('venue', 191);
            $t->string('field', 100)->nullable();
            $t->string('status', 16)->default('scheduled');
            $t->string('result_state', 16)->default('none');
            $t->unsignedSmallInteger('host_score')->nullable();
            $t->unsignedSmallInteger('guest_score')->nullable();
            $t->timestamp('result_confirmed_at')->nullable();
            $t->unsignedInteger('result_revision')->default(0);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['shared_competition_id', 'result_state'], 'shared_fixture_standings');
        });
        Schema::create('shared_participants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('record_type', 24);
            $t->unsignedBigInteger('record_id');
            $t->string('role', 16);
            $t->string('state', 16);
            $t->foreignId('decided_by')->nullable()->constrained('users');
            $t->timestamp('decided_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'record_type', 'record_id'], 'shared_participant_identity');
            $t->index(['record_type', 'record_id', 'state'], 'shared_participant_record');
        });
        // A side's group is always its own school's: a composite foreign key to
        // activity_groups (school_id, id), unique since the family consent tables.
        Schema::create('shared_fixture_sides', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('shared_fixture_id')->constrained('shared_fixtures');
            $t->unsignedBigInteger('activity_group_id');
            $t->string('team_label', 100);
            $t->boolean('sheet_shared')->default(false);
            $t->text('notes')->nullable();
            $t->unsignedSmallInteger('reported_host_score')->nullable();
            $t->unsignedSmallInteger('reported_guest_score')->nullable();
            $t->foreignId('reported_by')->nullable()->constrained('users');
            $t->timestamp('reported_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'shared_fixture_id'], 'shared_fixture_side_identity');
            $t->unique(['school_id', 'id'], 'shared_fixture_side_school_identity');
            $t->foreign(['school_id', 'activity_group_id'], 'shared_fixture_side_group')->references(['school_id', 'id'])->on('activity_groups');
        });
        Schema::create('shared_fixture_selections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('shared_fixture_side_id');
            $t->unsignedBigInteger('learner_id');
            $t->string('sheet_label', 60);
            $t->string('position', 40)->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'shared_fixture_side_id', 'learner_id'], 'shared_fixture_selection_identity');
            $t->foreign(['school_id', 'shared_fixture_side_id'], 'shared_fixture_selection_side')->references(['school_id', 'id'])->on('shared_fixture_sides');
            $t->foreign(['school_id', 'learner_id'], 'shared_fixture_selection_learner')->references(['school_id', 'id'])->on('students');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            abort_if(Schema::hasTable($table) && DB::table($table)->exists(), 409, "Refusing to drop {$table}: it holds shared records.");
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

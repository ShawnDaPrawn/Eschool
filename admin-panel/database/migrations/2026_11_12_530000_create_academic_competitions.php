<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Academic competitions and debating between schools
 * (engineering/specs/activities/academic-competitions.md), built on the
 * shared fixture of records shared between schools.
 *
 * The shared fixture gains the facts every accepted school may see: the
 * motion or topic, the format, the number of rounds, which stance the host
 * takes in a debate and the winning side both schools confirmed. Each side's
 * own report of the winner joins its score report. Everything else is
 * school-owned: the school's own adjudicators, its adjudicators' result
 * (a draft until it is submitted), the AI feedback on reasoning an
 * adjudicator asked for, and the awards the school linked to the result.
 */
return new class extends Migration
{
    private const TABLES = ['academic_award_links', 'academic_reasoning_reviews', 'academic_results', 'academic_adjudicators'];

    public function up(): void
    {
        Schema::table('shared_fixtures', function (Blueprint $t) {
            $t->string('motion', 500)->nullable()->after('discipline');
            $t->string('format', 60)->nullable()->after('motion');
            $t->unsignedTinyInteger('rounds')->nullable()->after('format');
            $t->string('host_stance', 12)->nullable()->after('rounds');
            $t->string('winner', 8)->nullable()->after('guest_score');
        });
        Schema::table('shared_fixture_sides', function (Blueprint $t) {
            $t->string('reported_winner', 8)->nullable()->after('reported_guest_score');
        });
        Schema::create('academic_adjudicators', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('shared_fixture_id')->constrained('shared_fixtures');
            // One of the school's own staff, or an adjudicator from outside named by the school.
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->string('name', 120)->nullable();
            $t->foreignId('added_by')->constrained('users');
            $t->timestamps();
            $t->index(['school_id', 'shared_fixture_id'], 'academic_adjudicator_fixture');
            $t->index(['school_id', 'user_id'], 'academic_adjudicator_user');
        });
        Schema::create('academic_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('shared_fixture_id')->constrained('shared_fixtures');
            $t->string('state', 12);
            $t->unsignedSmallInteger('host_score')->nullable();
            $t->unsignedSmallInteger('guest_score')->nullable();
            $t->string('winner', 8)->nullable();
            // Encrypted: the school's own entrants' scores and places, and the adjudicators' written reasons.
            $t->longText('entrants')->nullable();
            $t->text('reasons')->nullable();
            $t->foreignId('recorded_by')->constrained('users');
            $t->timestamp('submitted_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'shared_fixture_id'], 'academic_result_identity');
        });
        Schema::create('academic_reasoning_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('shared_fixture_id')->constrained('shared_fixtures');
            $t->foreignId('requested_by')->constrained('users');
            $t->uuid('operation_id');
            $t->string('source', 12);
            $t->string('stance', 12)->nullable();
            $t->string('from_side', 8);
            $t->string('state', 16);
            // Encrypted and already redacted; cleared once the feedback is written or refused.
            $t->longText('input')->nullable();
            $t->string('input_sha256', 64);
            $t->longText('feedback')->nullable();
            $t->string('failure_code', 64)->nullable();
            $t->unsignedBigInteger('shared_learner_id')->nullable();
            $t->text('shared_feedback')->nullable();
            $t->foreignId('shared_by')->nullable()->constrained('users');
            $t->timestamp('shared_at')->nullable();
            $t->uuid('shared_operation')->nullable();
            $t->timestamp('quarantine_purged_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'operation_id'], 'academic_review_operation');
            $t->index(['school_id', 'shared_fixture_id'], 'academic_review_fixture');
            $t->foreign(['school_id', 'shared_learner_id'], 'academic_review_learner')->references(['school_id', 'id'])->on('students');
        });
        Schema::create('academic_award_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('shared_fixture_id')->constrained('shared_fixtures');
            $t->unsignedBigInteger('learner_id');
            $t->string('basis', 16);
            $t->foreignId('award_presentation_id')->constrained('award_presentations');
            $t->foreignId('linked_by')->constrained('users');
            $t->timestamps();
            $t->unique(['school_id', 'shared_fixture_id', 'learner_id', 'basis'], 'academic_award_link_identity');
            $t->foreign(['school_id', 'learner_id'], 'academic_award_link_learner')->references(['school_id', 'id'])->on('students');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            abort_if(Schema::hasTable($table) && DB::table($table)->exists(), 409, "Refusing to drop {$table}: it holds competition records.");
        }
        abort_if(DB::table('shared_fixtures')->where(fn ($q) => $q->whereNotNull('motion')->orWhereNotNull('winner')->orWhere('kind', '!=', 'sport'))->exists(), 409,
            'Refusing to drop the competition facts: academic competitions exist.');
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('shared_fixture_sides', fn (Blueprint $t) => $t->dropColumn('reported_winner'));
        Schema::table('shared_fixtures', fn (Blueprint $t) => $t->dropColumn(['motion', 'format', 'rounds', 'host_stance', 'winner']));
    }
};

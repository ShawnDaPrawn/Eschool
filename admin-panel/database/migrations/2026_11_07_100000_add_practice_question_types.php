<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Question types and teacher marking for daily practice (Action Plan 3.11):
 *  - practice_reviews: an answer may wait for the learner's teacher (short
 *    written answers that match no accepted answer, spoken answers), so its
 *    result, memory schedule and mark per part are filled when it is marked,
 *    by the server or by the teacher, once;
 *  - practice_audio_answers: a learner's recorded spoken answer, encrypted in
 *    the school's private storage, kept until its retention date;
 *  - practice_diagrams: a school's diagram pictures for labelling questions;
 *  - library_diagrams: the platform library's diagram pictures (no school).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_reviews', function (Blueprint $t) {
            $t->boolean('correct')->nullable()->change();
            $t->json('schedule')->nullable()->change();
            $t->string('state', 24)->default('marked')->after('correct');
            $t->unsignedSmallInteger('achieved_units')->nullable()->after('state');
            $t->unsignedSmallInteger('maximum_units')->nullable()->after('achieved_units');
            $t->longText('parts')->nullable()->after('maximum_units');
            $t->unsignedTinyInteger('rating')->nullable()->after('parts');
            $t->string('marked_how', 16)->default('automatic')->after('rating');
            $t->foreignId('marked_by')->nullable()->after('marked_how')->constrained('users')->restrictOnDelete();
            $t->dateTime('marked_at')->nullable()->after('marked_by');
            $t->index(['school_id', 'state'], 'practice_reviews_waiting');
        });
        DB::table('practice_reviews')->update(['achieved_units' => DB::raw('CASE WHEN correct THEN 1 ELSE 0 END'), 'maximum_units' => 1, 'marked_at' => DB::raw('created_at')]);

        Schema::create('practice_audio_answers', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('practice_review_id')->unique()->constrained('practice_reviews')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->string('path', 255)->nullable();
            $t->string('mime', 40);
            $t->unsignedInteger('bytes');
            $t->char('sha256', 64);
            $t->date('purge_after');
            $t->dateTime('purged_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'purged_at', 'purge_after'], 'practice_audio_retention');
        });
        Schema::create('practice_diagrams', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->string('path', 255);
            $t->char('sha256', 64);
            $t->unsignedInteger('bytes');
            $t->unsignedSmallInteger('width');
            $t->unsignedSmallInteger('height');
            $t->timestamps();
            $t->index(['school_id', 'uploaded_by']);
        });
        Schema::create('library_diagrams', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->string('path', 255);
            $t->char('sha256', 64);
            $t->unsignedInteger('bytes');
            $t->unsignedSmallInteger('width');
            $t->unsignedSmallInteger('height');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('practice_reviews')->where('state', '!=', 'marked')->orWhereNull('correct')->orWhere('marked_how', '!=', 'automatic')->exists()
            || DB::table('practice_audio_answers')->exists() || DB::table('practice_diagrams')->exists() || DB::table('library_diagrams')->exists()) {
            throw new RuntimeException('Practice answers use the new question types: export them before rolling back.');
        }
        Schema::dropIfExists('library_diagrams');
        Schema::dropIfExists('practice_diagrams');
        Schema::dropIfExists('practice_audio_answers');
        // MySQL silently drops the school_id foreign key's own index once the
        // (school_id, state) index can serve it, and then refuses to drop that
        // index while the key needs it. Detach the key, drop the index, and
        // re-add the key, which recreates its own index on both MySQL and MariaDB.
        Schema::table('practice_reviews', fn (Blueprint $t) => $t->dropForeign(['school_id']));
        Schema::table('practice_reviews', function (Blueprint $t) {
            $t->dropIndex('practice_reviews_waiting');
            $t->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
        });
        Schema::table('practice_reviews', function (Blueprint $t) {
            $t->dropConstrainedForeignId('marked_by');
            $t->dropColumn(['state', 'achieved_units', 'maximum_units', 'parts', 'rating', 'marked_how', 'marked_at']);
        });
        Schema::table('practice_reviews', function (Blueprint $t) {
            $t->boolean('correct')->nullable(false)->change();
            $t->json('schedule')->nullable(false)->change();
        });
    }
};

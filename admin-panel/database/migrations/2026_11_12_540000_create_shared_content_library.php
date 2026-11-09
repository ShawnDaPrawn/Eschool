<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The shared content library, teacher to teacher (Action Plan 5.4). A teacher
 * offers one of their own approved practice questions to the platform
 * library; Navigate Ed reviews it like any library item; once published,
 * teachers at other schools browse it, copy it into their own banks and rate
 * it. engineering/specs/learning/shared-content-library.md.
 *
 *  - library_contributions (school-owned): the school's record of what it
 *    offered, who offered it, the credit and licence they chose, a leader's
 *    approval, and the library item it became. Holds no learner data.
 *  - library_ratings (school-owned): one teacher's rating and short comment of
 *    a shared item. Other schools only ever see an average and a count.
 *  - library_school_settings: the school's own sharing switch (off by
 *    default) and whether a leader approves each contribution (on by default).
 *  - library_sources: a contributed item's source names the contributing
 *    school and teacher, the credit they chose and the date (for attribution).
 *  - library_items: withdrawn by its school, or unpublished by a reviewer.
 *  - practice_items: the library version a school's question was copied from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_contributions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('practice_item_id')->constrained('practice_items')->restrictOnDelete();
            $t->foreignId('practice_item_version_id')->constrained('practice_item_versions')->restrictOnDelete();
            $t->foreignId('library_topic_id')->constrained('library_topics')->restrictOnDelete();
            $t->string('title', 191);
            $t->string('credit', 16);
            $t->string('licence', 64);
            $t->foreignId('contributed_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('rights_confirmed_at');
            $t->string('state', 24);
            $t->foreignId('decided_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->string('decline_reason', 500)->nullable();
            $t->foreignId('library_item_id')->nullable()->constrained('library_items')->restrictOnDelete();
            $t->timestamp('shared_at')->nullable();
            $t->foreignId('withdrawn_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('withdrawn_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'practice_item_version_id'], 'library_contribution_version');
            $t->index(['school_id', 'state']);
            $t->index(['school_id', 'contributed_by']);
        });
        Schema::create('library_ratings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('library_item_id')->constrained('library_items')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedTinyInteger('stars');
            $t->string('comment', 500)->nullable();
            $t->timestamps();
            // Once per item per teacher, wherever they teach.
            $t->unique(['library_item_id', 'user_id'], 'library_rating_once');
            $t->index(['school_id', 'library_item_id']);
        });
        Schema::table('library_school_settings', function (Blueprint $t) {
            $t->boolean('sharing_enabled')->default(false)->after('enabled');
            $t->boolean('leader_approval')->default(true)->after('sharing_enabled');
            $t->foreignId('sharing_changed_by')->nullable()->after('leader_approval')->constrained('users')->restrictOnDelete();
            $t->timestamp('sharing_changed_at')->nullable()->after('sharing_changed_by');
        });
        Schema::table('library_sources', function (Blueprint $t) {
            $t->foreignId('contributor_school_id')->nullable()->after('url')->constrained('schools')->restrictOnDelete();
            $t->foreignId('contributor_user_id')->nullable()->after('contributor_school_id')->constrained('users')->restrictOnDelete();
            $t->string('credit', 16)->nullable()->after('contributor_user_id');
            $t->timestamp('contributed_at')->nullable()->after('credit');
        });
        Schema::table('library_items', function (Blueprint $t) {
            $t->timestamp('withdrawn_at')->nullable()->after('revision');
            $t->timestamp('unpublished_at')->nullable()->after('withdrawn_at');
            $t->foreignId('unpublished_by')->nullable()->after('unpublished_at')->constrained('users')->restrictOnDelete();
            $t->string('unpublish_reason', 1000)->nullable()->after('unpublished_by');
        });
        Schema::table('practice_items', function (Blueprint $t) {
            $t->foreignId('library_item_version_id')->nullable()->after('topic_id')->constrained('library_item_versions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('library_contributions')->exists() || DB::table('library_ratings')->exists() || DB::table('library_sources')->whereNotNull('contributor_school_id')->exists()
            || DB::table('practice_items')->whereNotNull('library_item_version_id')->exists() || DB::table('library_items')->whereNotNull('withdrawn_at')->orWhereNotNull('unpublished_at')->exists()) {
            throw new RuntimeException('Shared library contributions, ratings or copies exist: export them before rolling back.');
        }
        Schema::table('practice_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('library_item_version_id'));
        Schema::table('library_items', function (Blueprint $t) {
            $t->dropConstrainedForeignId('unpublished_by');
            $t->dropColumn(['withdrawn_at', 'unpublished_at', 'unpublish_reason']);
        });
        Schema::table('library_sources', function (Blueprint $t) {
            $t->dropConstrainedForeignId('contributor_user_id');
            $t->dropConstrainedForeignId('contributor_school_id');
            $t->dropColumn(['credit', 'contributed_at']);
        });
        Schema::table('library_school_settings', function (Blueprint $t) {
            $t->dropConstrainedForeignId('sharing_changed_by');
            $t->dropColumn(['sharing_enabled', 'leader_approval', 'sharing_changed_at']);
        });
        Schema::dropIfExists('library_ratings');
        Schema::dropIfExists('library_contributions');
    }
};

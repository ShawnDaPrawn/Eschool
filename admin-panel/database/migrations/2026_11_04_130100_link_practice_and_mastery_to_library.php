<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a school's own practice and mastery rows point at the platform
 * library (Action Plan 3.3), never the other way round:
 *  - mastery_topics: a node may mirror a library topic instead of one of the
 *    school's curriculum topics (TopicGraph::sync's library overlay);
 *  - practice_set_entries and practice_memories: an entry or memory may be
 *    for a published library item version instead of a school item;
 *  - mastery_evidence: records whether the evidence came from school material
 *    or the library, and which library item version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mastery_topics', function (Blueprint $t) {
            $t->unsignedBigInteger('curriculum_topic_id')->nullable()->change();
            $t->foreignId('library_topic_id')->nullable()->after('curriculum_topic_id')->constrained('library_topics')->restrictOnDelete();
        });
        Schema::table('mastery_evidence', function (Blueprint $t) {
            $t->string('content_source', 16)->default('school')->after('source_key');
            $t->foreignId('library_item_version_id')->nullable()->after('content_source')->constrained('library_item_versions')->restrictOnDelete();
        });
        Schema::table('practice_set_entries', function (Blueprint $t) {
            $t->unsignedBigInteger('practice_item_version_id')->nullable()->change();
            $t->foreignId('library_item_version_id')->nullable()->after('practice_item_version_id')->constrained('library_item_versions')->restrictOnDelete();
            $t->unique(['practice_daily_set_id', 'library_item_version_id'], 'practice_set_unique_library_version');
        });
        Schema::table('practice_memories', function (Blueprint $t) {
            $t->unsignedBigInteger('practice_item_id')->nullable()->change();
            $t->foreignId('library_item_id')->nullable()->after('practice_item_id')->constrained('library_items')->restrictOnDelete();
            $t->unique(['learner_id', 'library_item_id'], 'practice_memory_library_item');
        });
    }

    public function down(): void
    {
        if (DB::table('mastery_topics')->whereNotNull('library_topic_id')->exists() || DB::table('mastery_evidence')->where('content_source', '!=', 'school')->exists()
            || DB::table('practice_set_entries')->whereNotNull('library_item_version_id')->exists() || DB::table('practice_memories')->whereNotNull('library_item_id')->exists()) {
            throw new RuntimeException('Practice or mastery rows use the content library: export them before rolling back.');
        }
        Schema::table('practice_memories', function (Blueprint $t) {
            $t->dropUnique('practice_memory_library_item');
            $t->dropConstrainedForeignId('library_item_id');
            $t->unsignedBigInteger('practice_item_id')->nullable(false)->change();
        });
        Schema::table('practice_set_entries', function (Blueprint $t) {
            $t->dropUnique('practice_set_unique_library_version');
            $t->dropConstrainedForeignId('library_item_version_id');
            $t->unsignedBigInteger('practice_item_version_id')->nullable(false)->change();
        });
        Schema::table('mastery_evidence', function (Blueprint $t) {
            $t->dropConstrainedForeignId('library_item_version_id');
            $t->dropColumn('content_source');
        });
        Schema::table('mastery_topics', function (Blueprint $t) {
            $t->dropConstrainedForeignId('library_topic_id');
            $t->unsignedBigInteger('curriculum_topic_id')->nullable(false)->change();
        });
    }
};

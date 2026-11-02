<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mastery model v2 (Action Plan 3.8): a per-school model choice, one stored
 * record per model version (so a recent20-v1 row never changes meaning), and
 * the answered item on each piece of evidence (for item difficulty).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mastery_settings', function (Blueprint $t) {
            $t->string('model_version', 32)->default('recent20-v1')->after('family_marks_enabled');
        });
        // recent20-v1 was the only model so far: any unlabelled record is one.
        DB::table('mastery_records')->where('policy_version', '')->update(['policy_version' => 'recent20-v1']);
        // Add the versioned identity before dropping the old one: school_id's
        // foreign key needs an index that starts with it at every moment.
        Schema::table('mastery_records', function (Blueprint $t) {
            $t->unique(['school_id', 'learner_id', 'topic_id', 'audience', 'policy_version'], 'mastery_record_version_identity');
        });
        Schema::table('mastery_records', function (Blueprint $t) {
            $t->dropUnique('mastery_record_identity');
        });
        Schema::table('mastery_evidence', function (Blueprint $t) {
            $t->string('item_key', 64)->nullable()->after('source_key');
            $t->index(['school_id', 'subject_id', 'item_key'], 'mastery_evidence_item');
        });
        $this->backfillItemKeys();
    }

    /** Existing evidence learns its item: the canonical question, or the practice item. */
    private function backfillItemKeys(): void
    {
        DB::table('mastery_evidence')->whereNull('item_key')->orderBy('id')->select(['id', 'source_type', 'source_key'])
            ->chunkById(500, function ($rows) {
                $reviews = $checkpoints = [];
                foreach ($rows as $row) {
                    if ($row->source_type === 'practice_review' && preg_match('/^practice:(\d+)$/', $row->source_key, $m)) {
                        $reviews[(int) $row->id] = (int) $m[1];
                    } elseif ($row->source_type === 'tutor_checkpoint' && preg_match('/^tutor_checkpoint:(\d+)$/', $row->source_key, $m)) {
                        $checkpoints[(int) $row->id] = (int) $m[1];
                    }
                }
                $reviewItems = $reviews === [] ? collect() : DB::table('practice_reviews')
                    ->join('practice_set_entries', 'practice_set_entries.id', '=', 'practice_reviews.practice_set_entry_id')
                    ->join('practice_item_versions', 'practice_item_versions.id', '=', 'practice_set_entries.practice_item_version_id')
                    ->whereIn('practice_reviews.id', array_values($reviews))->pluck('practice_item_versions.practice_item_id', 'practice_reviews.id');
                $checkpointItems = $checkpoints === [] ? collect() : DB::table('tutor_checkpoints')
                    ->join('practice_item_versions', 'practice_item_versions.id', '=', 'tutor_checkpoints.practice_item_version_id')
                    ->whereIn('tutor_checkpoints.id', array_values($checkpoints))->pluck('practice_item_versions.practice_item_id', 'tutor_checkpoints.id');
                foreach ($rows as $row) {
                    $key = null;
                    if (in_array($row->source_type, ['question_mark', 'released_question_mark'], true) && preg_match('/:q:(\d+)$/', $row->source_key, $m)) {
                        $key = 'question:'.$m[1];
                    } elseif (isset($reviews[(int) $row->id]) && $reviewItems->has($reviews[(int) $row->id])) {
                        $key = 'practice_item:'.$reviewItems->get($reviews[(int) $row->id]);
                    } elseif (isset($checkpoints[(int) $row->id]) && $checkpointItems->has($checkpoints[(int) $row->id])) {
                        $key = 'practice_item:'.$checkpointItems->get($checkpoints[(int) $row->id]);
                    }
                    if ($key !== null) {
                        DB::table('mastery_evidence')->where('id', $row->id)->update(['item_key' => $key]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (DB::table('mastery_records')->where('policy_version', '!=', 'recent20-v1')->exists()
            || DB::table('mastery_settings')->where('model_version', '!=', 'recent20-v1')->exists()) {
            throw new RuntimeException('Retained mastery v2 records or a school\'s v2 choice block destructive rollback.');
        }
        Schema::table('mastery_evidence', function (Blueprint $t) {
            $t->dropIndex('mastery_evidence_item');
            $t->dropColumn('item_key');
        });
        Schema::table('mastery_records', function (Blueprint $t) {
            $t->unique(['school_id', 'learner_id', 'topic_id', 'audience'], 'mastery_record_identity');
        });
        Schema::table('mastery_records', function (Blueprint $t) {
            $t->dropUnique('mastery_record_version_identity');
        });
        Schema::table('mastery_settings', function (Blueprint $t) {
            $t->dropColumn('model_version');
        });
    }
};

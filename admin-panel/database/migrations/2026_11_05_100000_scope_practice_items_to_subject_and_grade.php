<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teachers' question banks (Action Plan 3.6). A practice question belongs to a
 * subject and grade in its school, has an author and a visibility (only me,
 * my groups' teachers, or every teacher of that subject and grade), and is
 * used in any number of teaching groups. It no longer dies with the group it
 * was first written for.
 *
 * Existing questions keep working exactly as before: each takes its subject
 * and grade from the group it was written for, its author from whoever
 * approved its first version, the visibility "groups", and one use in that
 * same group. engineering/specs/learning/question-banks.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_items', function (Blueprint $t) {
            $t->foreignId('subject_id')->nullable()->after('teaching_group_id')->constrained('subjects')->restrictOnDelete();
            $t->foreignId('grade_level_id')->nullable()->after('subject_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('author_id')->nullable()->after('grade_level_id')->constrained('users')->restrictOnDelete();
            $t->string('visibility', 16)->default('groups')->after('author_id');
        });

        Schema::create('practice_item_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('practice_item_id')->constrained('practice_items')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('added_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['practice_item_id', 'teaching_group_id'], 'practice_item_group_once');
            $t->index(['school_id', 'teaching_group_id'], 'practice_item_groups_group');
        });

        DB::table('practice_items')->orderBy('id')->chunkById(500, function ($items) {
            $groups = DB::table('teaching_groups')->whereIn('id', $items->pluck('teaching_group_id'))->get(['id', 'school_id', 'subject_id', 'grade_level_id'])->keyBy('id');
            $authors = DB::table('practice_item_versions')->whereIn('practice_item_id', $items->pluck('id'))->orderBy('revision')->orderBy('id')
                ->get(['practice_item_id', 'approved_by'])->unique('practice_item_id')->pluck('approved_by', 'practice_item_id');
            $uses = [];
            foreach ($items as $item) {
                $group = $groups[$item->teaching_group_id];
                DB::table('practice_items')->where('id', $item->id)->update([
                    'subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id, 'author_id' => $authors[$item->id] ?? null,
                ]);
                $uses[] = ['school_id' => $item->school_id, 'practice_item_id' => $item->id, 'teaching_group_id' => $item->teaching_group_id,
                    'added_by' => $authors[$item->id] ?? null, 'created_at' => $item->created_at, 'updated_at' => $item->created_at];
            }
            DB::table('practice_item_groups')->insert($uses);
        });

        Schema::table('practice_items', function (Blueprint $t) {
            $t->unsignedBigInteger('subject_id')->nullable(false)->change();
            $t->unsignedBigInteger('grade_level_id')->nullable(false)->change();
            $t->index(['school_id', 'subject_id', 'grade_level_id', 'active'], 'practice_items_bank');
        });
    }

    public function down(): void
    {
        if (DB::table('practice_item_groups')->exists()) {
            throw new RuntimeException('Question banks are in use: export them before rollback.');
        }
        Schema::dropIfExists('practice_item_groups');
        Schema::table('practice_items', function (Blueprint $t) {
            $t->dropIndex('practice_items_bank');
            $t->dropConstrainedForeignId('subject_id');
            $t->dropConstrainedForeignId('grade_level_id');
            $t->dropConstrainedForeignId('author_id');
            $t->dropColumn('visibility');
        });
    }
};

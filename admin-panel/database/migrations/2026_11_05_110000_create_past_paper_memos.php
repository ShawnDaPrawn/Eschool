<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Structured memos (Action Plan 3.15): the mark points of one exam coach paper
 * version, captured by its teachers, and whether a teacher has reviewed them.
 * The mark points are encrypted like the memo text they come from. Only a
 * reviewed memo is used downstream. engineering/specs/ai/structured-memos.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('past_paper_memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->uuid('past_paper_version_id');
            $table->string('state', 16)->default('draft')->comment('draft or reviewed');
            $table->unsignedInteger('revision')->default(1);
            $table->longText('mark_points')->comment('Encrypted: question number => mark points');
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('past_paper_version_id')->references('id')->on('past_paper_versions')->restrictOnDelete();
            $table->unique(['school_id', 'past_paper_version_id'], 'past_paper_memo_version');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('past_paper_memos') && DB::table('past_paper_memos')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain the reviewed memos.');
        }
        Schema::dropIfExists('past_paper_memos');
    }
};

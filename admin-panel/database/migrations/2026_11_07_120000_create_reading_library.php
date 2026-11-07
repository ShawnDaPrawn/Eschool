<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The reading library (Action Plan 4.4). Books, their rights decisions, the
 * cached texts, shelves and comprehension questions are Navigate Ed's own
 * catalogue (global, written only from /saas/reading, apart from the text
 * cache, which only the server's fetcher writes). Each school's hidden books
 * and shelves, badge settings, and its learners' progress, reading days and
 * answers are school-owned. engineering/specs/learning/reading-library.md.
 */
return new class extends Migration
{
    private const TABLES = ['reading_answers', 'reading_days', 'reading_progress', 'reading_settings', 'reading_hidden_items',
        'reading_questions', 'reading_shelf_books', 'reading_shelves', 'reading_book_texts', 'reading_rights_decisions', 'reading_books'];

    public function up(): void
    {
        Schema::create('reading_books', function (Blueprint $t): void {
            $t->id();
            $t->unsignedInteger('gutenberg_id')->unique();
            $t->string('title', 255);
            $t->string('author', 191);
            $t->smallInteger('author_birth_year')->nullable();
            $t->smallInteger('author_death_year')->nullable();
            $t->string('language', 8);
            $t->json('subjects');
            $t->string('reading_level', 16);
            $t->string('summary', 1000)->nullable();
            $t->unsignedInteger('word_count')->nullable();
            // undecided until HQ records a decision; only public_domain_za is ever served.
            $t->string('rights_status', 24)->default('undecided');
            $t->foreignId('rights_decision_id')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index('rights_status');
        });
        Schema::create('reading_rights_decisions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('reading_book_id')->constrained('reading_books')->restrictOnDelete();
            $t->string('decision', 24);
            $t->smallInteger('author_death_year')->nullable();
            $t->string('basis', 2000);
            $t->boolean('translator_checked')->default(false);
            $t->foreignId('decided_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('decided_at');
            $t->timestamps();
            $t->index(['reading_book_id', 'id']);
        });
        Schema::table('reading_books', fn (Blueprint $t) => $t->foreign('rights_decision_id')->references('id')->on('reading_rights_decisions')->restrictOnDelete());
        Schema::create('reading_book_texts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('reading_book_id')->unique()->constrained('reading_books')->restrictOnDelete();
            $t->string('state', 16)->default('missing');
            $t->string('path', 255)->nullable();
            $t->char('sha256', 64)->nullable();
            $t->unsignedInteger('bytes')->nullable();
            $t->unsignedInteger('page_count')->nullable();
            $t->unsignedInteger('word_count')->nullable();
            $t->unsignedInteger('removed_lines')->default(0);
            $t->timestamp('fetched_at')->nullable();
            $t->string('last_error', 64)->nullable();
            $t->timestamp('last_failed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('reading_shelves', function (Blueprint $t): void {
            $t->id();
            $t->string('name', 120);
            $t->string('description', 500)->nullable();
            // Grade sort orders (R = 0 to 12) of the learners who see the shelf.
            $t->unsignedTinyInteger('grade_from');
            $t->unsignedTinyInteger('grade_to');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['active', 'grade_from', 'grade_to']);
        });
        Schema::create('reading_shelf_books', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('reading_shelf_id')->constrained('reading_shelves')->restrictOnDelete();
            $t->foreignId('reading_book_id')->constrained('reading_books')->restrictOnDelete();
            $t->unsignedSmallInteger('position');
            $t->timestamps();
            $t->unique(['reading_shelf_id', 'reading_book_id'], 'reading_shelf_book_once');
        });
        Schema::create('reading_questions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('reading_book_id')->constrained('reading_books')->restrictOnDelete();
            // The check unlocks once the learner reaches this page; null is the end of the book.
            $t->unsignedInteger('after_page')->nullable();
            $t->string('prompt', 500);
            $t->json('options');
            $t->unsignedTinyInteger('answer');
            $t->string('explanation', 1000)->nullable();
            $t->boolean('active')->default(true);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['reading_book_id', 'active']);
        });
        Schema::create('reading_hidden_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('kind', 8);
            $t->unsignedBigInteger('item_id');
            $t->foreignId('hidden_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'kind', 'item_id'], 'reading_hidden_once');
        });
        Schema::create('reading_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('finished_badge')->default(false);
            $t->boolean('days_badge')->default(false);
            $t->unsignedTinyInteger('days_required')->default(4);
            $t->foreignId('switched_on_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('reading_progress', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('reading_book_id')->constrained('reading_books')->restrictOnDelete();
            $t->unsignedInteger('page')->default(1);
            $t->unsignedInteger('furthest_page')->default(1);
            $t->unsignedInteger('seconds_read')->default(0);
            $t->timestamp('finished_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'reading_book_id'], 'reading_progress_once');
        });
        Schema::create('reading_days', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->date('read_on');
            $t->unsignedInteger('seconds')->default(0);
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'read_on'], 'reading_day_once');
        });
        Schema::create('reading_answers', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('reading_question_id')->constrained('reading_questions')->restrictOnDelete();
            $t->unsignedTinyInteger('choice');
            $t->boolean('correct');
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'reading_question_id'], 'reading_answer_once');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('The reading library holds rows: export them before rolling back.');
            }
        }
        if (Schema::hasTable('reading_books')) {
            Schema::table('reading_books', fn (Blueprint $t) => $t->dropForeign(['rights_decision_id']));
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

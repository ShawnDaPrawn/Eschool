<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The platform-owned content library (Action Plan 3.3) and its review
 * records (SA.16). Every library table is global: none has a school_id and
 * no school writes to them. library_school_settings is the one school-owned
 * table here: each school's switch for reading the library (off by default).
 * engineering/specs/learning/content-library.md.
 */
return new class extends Migration
{
    private const TABLES = ['library_school_settings', 'library_reviewers', 'library_reviews', 'library_item_versions', 'library_items',
        'library_topic_prerequisites', 'library_topics', 'library_sources', 'library_frameworks'];

    public function up(): void
    {
        Schema::create('library_frameworks', function (Blueprint $t) {
            $t->id();
            $t->string('code', 16)->unique();
            $t->string('name', 191);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('library_sources', function (Blueprint $t) {
            $t->id();
            $t->string('title', 191);
            $t->string('kind', 24);
            $t->string('licence', 191);
            $t->string('attribution', 500);
            $t->string('url', 2048)->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('library_topics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('library_framework_id')->constrained('library_frameworks')->restrictOnDelete();
            $t->string('subject_code', 32);
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->uuid('topic_key')->unique();
            $t->string('name', 191);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['library_framework_id', 'subject_code', 'grade_level_id', 'name'], 'library_topic_identity');
        });
        Schema::create('library_topic_prerequisites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('library_topic_id')->constrained('library_topics')->restrictOnDelete();
            $t->foreignId('prerequisite_id')->constrained('library_topics')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['library_topic_id', 'prerequisite_id'], 'library_edge_identity');
        });
        Schema::create('library_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('library_topic_id')->constrained('library_topics')->restrictOnDelete();
            $t->foreignId('library_source_id')->constrained('library_sources')->restrictOnDelete();
            $t->string('kind', 24);
            $t->string('title', 191);
            $t->unsignedBigInteger('published_version_id')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['library_topic_id', 'kind']);
        });
        Schema::create('library_item_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('library_item_id')->constrained('library_items')->restrictOnDelete();
            $t->unsignedInteger('number');
            $t->string('state', 16)->default('draft');
            $t->json('content');
            $t->unsignedSmallInteger('estimated_seconds')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('submitted_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('submitted_at')->nullable();
            $t->foreignId('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['library_item_id', 'number']);
            $t->index('state');
        });
        Schema::table('library_items', fn (Blueprint $t) => $t->foreign('published_version_id')->references('id')->on('library_item_versions')->restrictOnDelete());
        Schema::create('library_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('library_item_version_id')->constrained('library_item_versions')->restrictOnDelete();
            $t->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $t->string('decision', 16);
            $t->string('reason', 1000)->nullable();
            $t->timestamps();
        });
        Schema::create('library_reviewers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('library_framework_id')->constrained('library_frameworks')->restrictOnDelete();
            $t->string('subject_code', 32);
            $t->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'library_framework_id', 'subject_code'], 'library_reviewer_identity');
        });
        Schema::create('library_school_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('enabled')->default(false);
            $t->foreignId('changed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('The content library holds rows: export them before rolling back.');
            }
        }
        if (Schema::hasTable('library_items')) {
            Schema::table('library_items', fn (Blueprint $t) => $t->dropForeign(['published_version_id']));
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

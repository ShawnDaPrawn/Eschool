<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('topic_id')->constrained('mastery_topics')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['school_id', 'teaching_group_id', 'active']);
        });
        Schema::create('practice_item_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('practice_item_id')->constrained('practice_items')->restrictOnDelete();
            $t->foreignId('content_version_id')->constrained('content_versions')->restrictOnDelete();
            $t->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->unsignedSmallInteger('estimated_seconds');
            $t->longText('definition');
            $t->timestamps();
            $t->unique(['practice_item_id', 'revision']);
        });
        Schema::create('practice_daily_sets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->constrained('teaching_groups')->restrictOnDelete();
            $t->date('practice_on');
            $t->dateTime('offline_until');
            $t->dateTime('completed_at')->nullable();
            $t->date('completed_on')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'teaching_group_id', 'practice_on'], 'practice_one_daily_set');
        });
        Schema::create('practice_set_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('practice_daily_set_id')->constrained('practice_daily_sets')->restrictOnDelete();
            $t->foreignId('practice_item_version_id')->constrained('practice_item_versions')->restrictOnDelete();
            $t->unsignedSmallInteger('position');
            $t->timestamps();
            $t->unique(['practice_daily_set_id', 'position']);
            $t->unique(['practice_daily_set_id', 'practice_item_version_id'], 'practice_set_unique_version');
        });
        Schema::create('practice_memories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('practice_item_id')->constrained('practice_items')->restrictOnDelete();
            $t->json('state');
            $t->date('reviewed_on');
            $t->date('due_on');
            $t->timestamps();
            $t->unique(['learner_id', 'practice_item_id']);
        });
        Schema::create('practice_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('practice_set_entry_id')->constrained('practice_set_entries')->restrictOnDelete();
            $t->longText('answer');
            $t->boolean('correct');
            $t->json('schedule');
            $t->date('reviewed_on');
            $t->timestamps();
            $t->unique('practice_set_entry_id');
        });
        Schema::create('practice_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->longText('result');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'practice_operation_identity');
        });
    }

    public function down(): void
    {
        foreach (['practice_operations', 'practice_reviews', 'practice_memories', 'practice_set_entries', 'practice_daily_sets', 'practice_item_versions', 'practice_items'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Practice history must be exported before rollback.');
            }
        }
        foreach (['practice_operations', 'practice_reviews', 'practice_memories', 'practice_set_entries', 'practice_daily_sets', 'practice_item_versions', 'practice_items'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

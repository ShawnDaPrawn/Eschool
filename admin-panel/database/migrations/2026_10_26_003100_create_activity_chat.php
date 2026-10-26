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
        Schema::create('activity_chat_policies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools');
            $t->boolean('staff_learners')->default(true);
            $t->boolean('staff_guardians')->default(true);
            $t->boolean('staff_staff')->default(true);
            $t->boolean('learner_shared_replies')->default(false);
            $t->boolean('guardian_shared_replies')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('activity_chat_threads', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('activity_group_id')->constrained('activity_groups');
            $t->foreignId('created_by')->constrained('users');
            $t->string('audience', 16);
            $t->boolean('private');
            $t->foreignId('owner_id')->nullable()->constrained('users');
            $t->string('owner_persona', 16)->nullable();
            $t->text('title')->nullable();
            $t->boolean('closed')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->dateTime('opened_at');
            $t->dateTime('last_message_at');
            $t->dateTime('redacted_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'activity_group_id', 'last_message_at'], 'activity_chat_thread_lookup');
        });
        Schema::create('activity_chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignUuid('thread_id')->constrained('activity_chat_threads');
            $t->foreignId('sender_id')->constrained('users');
            $t->string('sender_persona', 16);
            $t->foreignId('private_user_id')->nullable()->constrained('users');
            $t->string('visibility', 16);
            $t->foreignId('reply_to')->nullable()->constrained('activity_chat_messages');
            $t->text('body')->nullable();
            $t->string('language', 20);
            $t->string('source_hash', 64);
            $t->unsignedInteger('revision')->default(1);
            $t->dateTime('sent_at');
            $t->dateTime('removed_at')->nullable();
            $t->foreignId('removed_by')->nullable()->constrained('users');
            $t->text('removal_reason')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'thread_id', 'id'], 'activity_chat_message_lookup');
        });
        Schema::create('activity_chat_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignUuid('thread_id')->constrained('activity_chat_threads');
            $t->foreignId('user_id')->constrained('users');
            $t->string('persona', 16);
            $t->foreignId('last_message_id')->constrained('activity_chat_messages');
            $t->timestamps();
            $t->unique(['school_id', 'thread_id', 'user_id', 'persona'], 'activity_chat_reader');
        });
    }

    public function down(): void
    {
        foreach (['activity_chat_reads', 'activity_chat_messages', 'activity_chat_threads', 'activity_chat_policies'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Retained group conversations require an explicit export and retention decision before rollback.');
            }
        }
        foreach (['activity_chat_reads', 'activity_chat_messages', 'activity_chat_threads', 'activity_chat_policies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The learner's private diary (engineering/specs/learning/learner-diary.md).
 * Text columns hold DiaryCipher ciphertext, readable only through the owner's
 * per-learner key; rows are deleted outright, never soft-deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('operation_id');
            $table->string('kind', 8);
            $table->text('title');
            $table->longText('body')->nullable();
            $table->date('entry_on')->nullable();
            $table->dateTime('remind_at')->nullable();
            $table->dateTime('done_at')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
            $table->unique(['school_id', 'user_id', 'operation_id']);
            $table->index(['school_id', 'user_id', 'entry_on']);
        });

        Schema::create('diary_item_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 16);
            $table->unsignedBigInteger('source_id');
            $table->dateTime('done_at')->nullable();
            $table->dateTime('remind_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'user_id', 'source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_item_states');
        Schema::dropIfExists('diary_entries');
    }
};

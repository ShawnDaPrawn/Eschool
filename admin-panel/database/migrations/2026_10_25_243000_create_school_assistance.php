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
        Schema::create('school_knowledge_documents', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->uuid('document_key');
            $t->unsignedInteger('revision');
            $t->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $t->string('title', 191);
            $t->longText('markdown');
            $t->string('content_hash', 64);
            $t->string('source_reference', 2048);
            $t->boolean('identifiers_reviewed');
            $t->string('state', 16)->default('published');
            $t->date('expires_on')->nullable();
            $t->dateTime('published_at');
            $t->timestamps();
            $t->unique(['school_id', 'document_key', 'revision'], 'school_knowledge_version');
            $t->index(['school_id', 'state', 'expires_on'], 'school_knowledge_visibility');
        });
        Schema::create('school_support_threads', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('parent_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('assigned_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('state', 16)->default('open');
            $t->unsignedInteger('revision')->default(1);
            $t->dateTime('closed_at')->nullable();
            $t->dateTime('expires_at');
            $t->dateTime('purged_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'parent_user_id', 'created_at'], 'school_support_parent_order');
            $t->index(['school_id', 'assigned_user_id', 'state'], 'school_support_office_queue');
        });
        Schema::create('school_support_messages', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignUuid('thread_id')->constrained('school_support_threads')->restrictOnDelete();
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->string('author_kind', 16);
            $t->longText('text')->nullable();
            $t->string('content_hash', 64);
            $t->string('language', 16);
            $t->dateTime('sent_at');
            $t->dateTime('purged_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'thread_id', 'sent_at'], 'school_support_message_order');
        });
        Schema::create('school_assistance_operations', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('kind', 48);
            $t->string('request_hash', 64);
            $t->longText('response');
            $t->timestamps();
            $t->unique(['school_id', 'user_id', 'operation_id'], 'school_assistance_exact_operation');
        });
    }

    public function down(): void
    {
        foreach (['school_knowledge_documents', 'school_support_threads', 'school_support_messages', 'school_assistance_operations'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain school-assistance records before rollback.');
            }
        }
        Schema::dropIfExists('school_assistance_operations');
        Schema::dropIfExists('school_support_messages');
        Schema::dropIfExists('school_support_threads');
        Schema::dropIfExists('school_knowledge_documents');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who changed what and when (Build Bible 3.1, Audit; 5.1 data protection).
 * Rows are only ever inserted: the AuditLog model refuses updates and
 * deletes, and later retention jobs are the only thing that may remove rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index()->comment('users.id of whoever made the change');
            $table->string('actor_type', 20)->comment('user, system (console, queue, schedule) or guest');
            $table->string('action', 100);
            $table->string('subject_type', 191)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('request_id', 128)->nullable()->index();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};

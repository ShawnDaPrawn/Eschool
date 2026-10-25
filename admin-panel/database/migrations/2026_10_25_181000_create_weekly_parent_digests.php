<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digest_language_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('language', 10);
            $t->unsignedInteger('version');
            $t->text('title_template');
            $t->text('body_template');
            $t->text('action_templates');
            $t->text('notice_prompt');
            $t->string('content_hash', 64);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'language', 'version'], 'digest_language_version');
        });
        Schema::create('digest_preferences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('language', 10);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'user_id'], 'digest_parent_preference');
        });
        Schema::create('weekly_parent_digests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('template_id')->constrained('digest_language_templates')->restrictOnDelete();
            $t->unsignedInteger('template_version');
            $t->string('language', 10);
            $t->date('week_start');
            $t->date('week_end');
            $t->text('title');
            $t->text('summary');
            $t->longText('snapshot');
            $t->text('home_action');
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->restrictOnDelete();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('expires_at');
            $t->timestamps();
            $t->unique(['school_id', 'learner_id', 'recipient_id', 'week_start'], 'digest_one_child_parent_week');
            $t->index(['school_id', 'recipient_id', 'expires_at'], 'digest_parent_retention');
        });
        Schema::create('digest_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('kind', 40);
            $t->string('intent_hash', 64);
            $t->text('result');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'digest_exact_operation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_operations');
        Schema::dropIfExists('weekly_parent_digests');
        Schema::dropIfExists('digest_preferences');
        Schema::dropIfExists('digest_language_templates');
    }
};

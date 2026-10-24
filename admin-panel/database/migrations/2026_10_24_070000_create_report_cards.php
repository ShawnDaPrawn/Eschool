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
        Schema::create('report_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('name', 191);
            $t->json('design');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('report_comment_banks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->restrictOnDelete();
            $t->string('scope', 16);
            $t->string('label', 191);
            $t->text('text');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('report_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('report_template_id')->constrained('report_templates')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('term_id')->nullable()->constrained('terms')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->foreignId('class_section_id')->constrained('class_sections')->restrictOnDelete();
            $t->string('mode', 16);
            $t->string('state', 24)->default('draft');
            $t->unsignedInteger('revision')->default(1);
            $t->json('selection');
            $t->json('design');
            $t->json('signature_data')->nullable();
            $t->char('source_fingerprint', 64);
            $t->char('fingerprint', 64);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('grade_signed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('grade_signed_at')->nullable();
            $t->foreignId('principal_signed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('principal_signed_at')->nullable();
            $t->foreignId('released_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('released_at')->nullable();
            $t->timestamps();
        });
        Schema::create('report_cards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('report_batch_id')->constrained('report_batches')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->longText('payload');
            $t->string('pdf_path', 255)->nullable();
            $t->char('pdf_sha256', 64)->nullable();
            $t->char('generated_fingerprint', 64)->nullable();
            $t->timestamp('generated_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'report_batch_id', 'learner_id'], 'report_card_learner');
        });
        Schema::create('report_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('report_card_id')->constrained('report_cards')->restrictOnDelete();
            $t->string('scope_key', 64);
            $t->text('text');
            $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'report_card_id', 'scope_key'], 'report_comment_scope');
        });
        Schema::create('report_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('report_card_id')->constrained('report_cards')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->restrictOnDelete();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'report_card_id', 'user_id'], 'report_recipient_once');
        });
    }

    public function down(): void
    {
        if (DB::table('report_cards')->exists()) {
            throw new RuntimeException('Restore a tested backup to preserve report evidence, signatures and private artifacts.');
        }
        foreach (['report_recipients', 'report_comments', 'report_cards', 'report_batches', 'report_comment_banks', 'report_templates'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

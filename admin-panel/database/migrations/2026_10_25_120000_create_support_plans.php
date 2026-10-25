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
        Schema::create('support_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('assigned_by')->constrained('users');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->text('reason');
            $t->text('revoke_reason')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'learner_id', 'user_id', 'active'], 'support_caseload');
        });
        Schema::create('support_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('created_by')->constrained('users');
            $t->date('started_on');
            $t->date('review_due_on')->nullable();
            $t->string('state', 24)->default('active');
            $t->unsignedInteger('revision')->default(1);
            $t->unsignedInteger('accommodation_revision')->default(1);
            $t->longText('contents');
            $t->timestamps();
            $t->index(['school_id', 'learner_id', 'state']);
        });
        Schema::create('support_plan_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('support_plan_id')->constrained('support_plans');
            $t->foreignId('author_id')->constrained('users');
            $t->unsignedInteger('revision');
            $t->longText('snapshot');
            $t->timestamps();
            $t->unique(['support_plan_id', 'revision']);
            $t->index(['school_id', 'support_plan_id']);
        });
        Schema::create('support_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('support_plan_id')->constrained('support_plans');
            $t->foreignId('author_id')->constrained('users');
            $t->date('reviewed_on');
            $t->date('next_review_on')->nullable();
            $t->string('kind', 24)->default('review');
            $t->longText('outcome');
            $t->timestamps();
            $t->index(['school_id', 'support_plan_id', 'reviewed_on']);
        });
        Schema::create('support_disclosures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('support_plan_id')->constrained('support_plans');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('approved_by')->constrained('users');
            $t->unsignedInteger('source_accommodation_revision');
            $t->date('expires_on');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->longText('accommodations');
            $t->text('reason');
            $t->text('revoke_reason')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'user_id', 'active', 'expires_on'], 'support_teacher_disclosures');
        });
        Schema::create('support_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users');
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->string('result_kind', 32);
            $t->unsignedBigInteger('result_id');
            $t->timestamps();
            $t->unique(['school_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        $tables = ['support_operations', 'support_disclosures', 'support_reviews', 'support_plan_versions', 'support_plans', 'support_assignments'];
        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained support history prevents rollback.');
            }
        }
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};

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
        Schema::create('intakes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->uuid('public_id')->unique();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->string('name', 160);
            $t->string('state', 20)->default('draft');
            $t->dateTime('opens_at');
            $t->dateTime('closes_at');
            $t->unsignedInteger('application_fee_cents')->default(0);
            $t->unsignedSmallInteger('keep_for_days');
            $t->json('required_documents');
            $t->json('field_ids');
            $t->json('restricted_field_ids');
            $t->char('form_fingerprint', 64);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('intake_grades', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('intake_id')->constrained('intakes')->restrictOnDelete();
            $t->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $t->unsignedInteger('places');
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['intake_id', 'grade_level_id']);
        });
        Schema::create('applications', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->uuid('public_id')->unique();
            $t->foreignId('intake_id')->constrained('intakes')->restrictOnDelete();
            $t->foreignId('intake_grade_id')->constrained('intake_grades')->restrictOnDelete();
            $t->string('state', 24)->default('draft');
            $t->longText('answers');
            $t->longText('parent_contact');
            $t->char('email_hash', 64);
            $t->char('draft_key_hash', 64);
            $t->char('creation_hash', 64);
            $t->dateTime('email_verified_at')->nullable();
            $t->dateTime('submitted_at')->nullable();
            $t->dateTime('waitlisted_at')->nullable();
            $t->dateTime('terminal_at')->nullable();
            $t->dateTime('enrolled_at')->nullable();
            $t->foreignId('learner_id')->nullable()->constrained('students')->restrictOnDelete();
            $t->unsignedInteger('office_fee_cents')->default(0);
            $t->boolean('office_fee_paid')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'state', 'updated_at'], 'admission_retention');
            $t->index(['intake_grade_id', 'state', 'waitlisted_at'], 'admission_waiting_order');
        });
        Schema::create('application_documents', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->uuid('handle')->unique();
            $t->uuid('operation_id');
            $t->char('source_digest', 64);
            $t->unique(['application_id', 'operation_id']);
            $t->string('category', 64);
            $t->string('path', 255);
            $t->string('mime', 80);
            $t->unsignedInteger('bytes');
            $t->char('sha256', 64);
            $t->string('scanner', 120);
            $t->dateTime('scanned_at');
            $t->timestamps();
        });
        Schema::create('offers', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('intake_grade_id')->constrained('intake_grades')->restrictOnDelete();
            $t->foreignId('issued_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('state', 20)->default('offered');
            $t->dateTime('expires_at');
            $t->dateTime('accepted_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['intake_grade_id', 'state', 'expires_at'], 'offer_reservation');
        });
        Schema::create('application_links', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->char('token_hash', 64)->unique();
            $t->char('email_hash', 64);
            $t->dateTime('expires_at');
            $t->dateTime('used_at')->nullable();
            $t->timestamps();
        });
        Schema::create('application_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('kind', 64);
            $t->unsignedInteger('revision');
            $t->longText('details');
            $t->timestamps();
        });
        Schema::create('admission_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('kind', 40);
            $t->char('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'admission_operation_identity');
        });
    }

    public function down(): void
    {
        foreach (['intakes', 'applications', 'offers', 'admission_operations'] as $table) {
            if (DB::table($table)->exists()) {
                throw new LogicException('Retain admissions history.');
            }
        }
        foreach (['admission_operations', 'application_events', 'application_links', 'offers', 'application_documents', 'applications', 'intake_grades', 'intakes'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

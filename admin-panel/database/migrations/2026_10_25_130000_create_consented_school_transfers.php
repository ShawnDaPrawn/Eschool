<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_packages', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('public_id')->unique();
            $t->foreignId('to_school_id')->nullable()->constrained('schools');
            $t->string('external_school', 160)->nullable();
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('guardian_id')->constrained('users');
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('released_by')->nullable()->constrained('users');
            $t->foreignId('session_year_id')->constrained('session_years');
            $t->date('last_attended_on');
            $t->date('starts_on');
            $t->string('state', 24);
            $t->unsignedInteger('revision')->default(1);
            $t->char('source_state_hash', 64);
            $t->char('fingerprint', 64);
            $t->longText('contents');
            $t->longText('manifest');
            $t->text('signature');
            $t->string('artifact_path', 255);
            $t->char('artifact_sha256', 64);
            $t->char('cipher_sha256', 64);
            $t->unsignedBigInteger('artifact_bytes');
            $t->timestamp('released_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('expires_at');
            $t->timestamps();
            $t->index(['school_id', 'state', 'id'], 'transfer_source_queue');
            $t->index(['to_school_id', 'state', 'id'], 'transfer_destination_queue');
        });
        Schema::create('transfer_consents', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('transfer_package_id')->constrained('transfer_packages');
            $t->foreignId('guardian_id')->constrained('users');
            $t->string('decision', 16);
            $t->string('version', 64);
            $t->char('fingerprint', 64);
            $t->timestamps();
            $t->index(['school_id', 'transfer_package_id', 'id'], 'transfer_consent_history');
        });
        Schema::create('transfer_stages', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->uuid('public_id')->unique();
            $t->string('path', 255);
            $t->timestamp('expires_at');
            $t->boolean('adopted')->default(false);
            $t->timestamps();
            $t->index(['school_id', 'actor_id', 'operation_id'], 'transfer_stage_retry');
        });
        Schema::create('transfer_receipts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('transfer_package_id')->constrained('transfer_packages');
            $t->foreignId('learner_id')->constrained('students');
            $t->foreignId('accepted_by')->constrained('users');
            $t->foreignId('source_school_id')->constrained('schools');
            $t->date('starts_on');
            $t->longText('contents');
            $t->longText('manifest');
            $t->text('signature');
            $t->string('artifact_path', 255);
            $t->char('artifact_sha256', 64);
            $t->char('cipher_sha256', 64);
            $t->unsignedBigInteger('artifact_bytes');
            $t->timestamps();
            $t->unique(['school_id', 'transfer_package_id'], 'transfer_accepted_once');
        });
        Schema::create('transfer_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->string('kind', 24);
            $t->unsignedBigInteger('record_id');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'transfer_operation_retry');
        });
    }

    public function down(): void
    {
        foreach (['transfer_operations', 'transfer_receipts', 'transfer_stages', 'transfer_consents', 'transfer_packages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

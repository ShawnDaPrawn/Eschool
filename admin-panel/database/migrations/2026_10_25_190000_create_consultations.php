<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('session_year_id');
            $t->foreignId('created_by')->constrained('users');
            $t->string('type', 16);
            $t->text('title');
            $t->text('instructions')->nullable();
            $t->longText('audience');
            $t->boolean('anonymous');
            $t->string('results_visibility', 20);
            $t->string('status', 16)->default('draft');
            $t->dateTime('opens_at');
            $t->dateTime('closes_at');
            $t->dateTime('ended_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'id'], 'consultation_school_identity');
            $t->index(['school_id', 'status', 'opens_at', 'closes_at'], 'consultation_clock');
            $t->foreign(['school_id', 'session_year_id'], 'consultation_year_scope')->references(['school_id', 'id'])->on('session_years');
        });
        Schema::create('consultation_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('consultation_id');
            $t->unsignedSmallInteger('position');
            $t->string('kind', 16);
            $t->text('label');
            $t->boolean('required');
            $t->unsignedSmallInteger('max_choices')->default(1);
            $t->timestamps();
            $t->unique(['school_id', 'consultation_id', 'id'], 'consultation_question_identity');
            $t->unique(['school_id', 'consultation_id', 'position'], 'consultation_question_order');
            $t->foreign(['school_id', 'consultation_id'], 'consultation_question_scope')->references(['school_id', 'id'])->on('consultations');
        });
        Schema::create('consultation_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('consultation_id');
            $t->unsignedBigInteger('question_id');
            $t->unsignedSmallInteger('position');
            $t->text('label');
            $t->timestamps();
            $t->unique(['school_id', 'consultation_id', 'id'], 'consultation_option_identity');
            $t->unique(['school_id', 'question_id', 'position'], 'consultation_option_order');
            $t->foreign(['school_id', 'consultation_id', 'question_id'], 'consultation_option_scope')->references(['school_id', 'consultation_id', 'id'])->on('consultation_questions');
        });
        Schema::create('consultation_candidates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('consultation_id');
            $t->unsignedBigInteger('option_id');
            $t->text('statement')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'option_id'], 'consultation_candidate_once');
            $t->foreign(['school_id', 'consultation_id', 'option_id'], 'consultation_candidate_scope')->references(['school_id', 'consultation_id', 'id'])->on('consultation_options');
        });
        Schema::create('consultation_participation', function (Blueprint $t) {
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('consultation_id');
            $t->foreignId('user_id')->constrained('users');
            // Intentionally no sequence, timestamp, receipt, operation or response reference.
            $t->primary(['school_id', 'consultation_id', 'user_id'], 'consultation_one_person');
            $t->foreign(['school_id', 'consultation_id'], 'consultation_participation_scope')->references(['school_id', 'id'])->on('consultations');
        });
        Schema::create('consultation_responses', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('consultation_id');
            $t->foreignId('respondent_user_id')->nullable()->constrained('users');
            $t->longText('answers');
            $t->unique(['school_id', 'consultation_id', 'respondent_user_id'], 'consultation_named_once');
            // Anonymous rows have no identity, sequence or timestamp to pair with participation.
            $t->foreign(['school_id', 'consultation_id'], 'consultation_response_scope')->references(['school_id', 'id'])->on('consultations');
        });
        Schema::create('consultation_configuration_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('consultation_id');
            $t->foreignId('actor_id')->nullable()->constrained('users');
            $t->string('action', 32);
            $t->unsignedInteger('revision');
            $t->longText('snapshot');
            $t->timestamps();
            $t->foreign(['school_id', 'consultation_id'], 'consultation_audit_scope')->references(['school_id', 'id'])->on('consultations');
        });
        Schema::create('consultation_operations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('actor_id')->constrained('users');
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'consultation_config_retry');
        });
        DB::table('permissions')->insertOrIgnore(['name' => 'consultations-manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', 'consultations-manage')->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permission, 'role_id' => $role]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tables = ['consultation_operations', 'consultation_configuration_events', 'consultation_responses', 'consultation_participation', 'consultation_candidates', 'consultation_options', 'consultation_questions', 'consultations'];
        foreach ($tables as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retained consultation configuration/ballots prevent destructive rollback.');
            }
        }
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        // Retain permissions that custom school roles may reference.
    }
};

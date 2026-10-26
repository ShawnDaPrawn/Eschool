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
        Schema::create('school_assistance_turns', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->string('purpose', 16);
            $t->boolean('office')->default(false);
            $t->longText('source_request')->nullable();
            $t->longText('source_manifest')->nullable();
            $t->longText('approved_input')->nullable();
            $t->longText('citations')->nullable();
            $t->longText('response')->nullable();
            $t->string('state', 16)->default('prepared');
            $t->dateTime('approved_at')->nullable();
            $t->dateTime('expires_at');
            $t->dateTime('purged_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'user_id', 'operation_id'], 'school_assistance_turn_intent');
            $t->index(['school_id', 'expires_at'], 'school_assistance_turn_expiry');
        });
    }

    public function down(): void
    {
        if (DB::table('school_assistance_turns')->exists()) {
            throw new RuntimeException('Retain assistance turn records before rollback.');
        }
        Schema::dropIfExists('school_assistance_turns');
    }
};

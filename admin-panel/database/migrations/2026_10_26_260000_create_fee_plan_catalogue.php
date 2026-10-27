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
        Schema::create('fee_plans', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->string('name', 160);
            $t->bigInteger('amount_cents');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->string('scope', 12)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('target_id')->nullable();
            $t->string('state', 12)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->softDeletes();
            $t->index(['school_id', 'session_year_id', 'state']);
        });
        DB::statement("ALTER TABLE fee_plans ADD CONSTRAINT fee_plan_rules CHECK (
            amount_cents BETWEEN 0 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$'
            AND state IN ('draft','active','archived') AND revision > 0
            AND ((scope = 'all' AND target_id IS NULL) OR (scope IN ('grade','class') AND target_id IS NOT NULL AND target_id > 0)))");
        Schema::create('fee_plan_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id')->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->json('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'operation_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('fee_plans')->exists() || DB::table('fee_plan_operations')->exists()) {
            throw new RuntimeException('Retain fee plan configuration and operation history.');
        }
        Schema::dropIfExists('fee_plan_operations');
        Schema::dropIfExists('fee_plans');
    }
};

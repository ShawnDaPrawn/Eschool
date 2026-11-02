<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-managed feature flags (Action Plan SA.14): a feature is off, in
 * beta for chosen schools, or on for everyone; a school can be switched on or
 * off by name; a kill switch turns it off everywhere at once. Written only
 * from /saas. Specification: engineering/specs/platform/feature-flags.md.
 */
return new class extends Migration
{
    private const TABLES = ['platform_feature_flag_schools', 'platform_feature_flags'];

    public function up(): void
    {
        Schema::create('platform_feature_flags', function (Blueprint $t) {
            $t->id();
            $t->string('key', 64)->unique();
            $t->string('description', 500);
            // off, beta (only the schools switched on by name) or everyone.
            $t->string('rollout', 12)->default('off');
            $t->boolean('killed')->default(false);
            $t->string('kill_reason', 500)->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('platform_feature_flag_schools', function (Blueprint $t) {
            $t->id();
            $t->foreignId('flag_id')->constrained('platform_feature_flags')->restrictOnDelete();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->boolean('enabled');
            $t->timestamps();
            $t->unique(['flag_id', 'school_id']);
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain the feature flags and their school switches.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

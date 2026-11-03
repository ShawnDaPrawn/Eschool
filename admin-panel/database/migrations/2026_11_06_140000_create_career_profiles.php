<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Career profiles in each school's pathways catalogue (Action Plan 4.9).
 * School-owned like admission_requirements: each school's pathways-manage
 * holders review and publish their own sourced, dated entries. Revisions are
 * immutable; idempotent writes reuse pathway_operations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_profiles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('status', 16);
            $t->unsignedInteger('revision');
            $t->unsignedBigInteger('current_version_id')->nullable();
            $t->date('expires_on');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'status', 'expires_on']);
        });
        Schema::create('career_profile_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('profile_id');
            $t->unsignedInteger('revision');
            $t->string('status', 16);
            $t->string('title', 191);
            $t->text('summary');
            $t->string('source_title', 240);
            $t->text('source_url');
            $t->unsignedSmallInteger('source_year');
            $t->date('reviewed_on');
            $t->date('expires_on');
            $t->json('details');
            $t->foreignId('reviewed_by')->constrained('users');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'profile_id', 'id'], 'career_version_identity');
            $t->unique(['school_id', 'profile_id', 'revision'], 'career_source_revision');
            $t->foreign(['school_id', 'profile_id'])->references(['school_id', 'id'])->on('career_profiles');
        });
        Schema::table('career_profiles', function (Blueprint $t) {
            $t->foreign(['school_id', 'id', 'current_version_id'], 'career_current_version')->references(['school_id', 'profile_id', 'id'])->on('career_profile_versions');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('career_profile_versions') && DB::table('career_profile_versions')->exists()) {
            throw new RuntimeException('Career profile rollback refused: preserve published source history.');
        }
        if (Schema::hasTable('career_profiles')) {
            Schema::table('career_profiles', fn (Blueprint $t) => $t->dropForeign('career_current_version'));
        }
        Schema::dropIfExists('career_profile_versions');
        Schema::dropIfExists('career_profiles');
    }
};

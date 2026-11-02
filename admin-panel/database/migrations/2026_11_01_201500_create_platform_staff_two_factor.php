<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two-factor sign-in for platform staff (Action Plan SA.2): one row per
 * platform staff member with their authenticator app secret (encrypted with
 * the app key), when they confirmed it, the last time step they used, and the
 * keyed hashes of their recovery codes. A platform table with no school.
 * Specification: engineering/specs/platform/hq-two-factor.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_staff_two_factor', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            // Laravel's encrypted cast: ciphertext only, never the Base32 secret.
            $t->text('secret');
            // Null until the person confirms a code from their app.
            $t->timestamp('confirmed_at')->nullable();
            // The newest 30-second step a code was accepted for: a code works once.
            $t->unsignedBigInteger('last_used_step')->nullable();
            // HMAC-SHA256 of each unused recovery code; a used one is removed.
            $t->json('recovery_codes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_staff_two_factor') && DB::table('platform_staff_two_factor')->exists()) {
            throw new RuntimeException('Platform staff two-factor enrolments are retained; rollback refused.');
        }
        Schema::dropIfExists('platform_staff_two_factor');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Server-side sign-out for platform staff whose two-factor sign-in is reset
 * (SA.2): a counter on the platform staff identity, raised by every reset.
 * Each browser session records the counter when it signs in; a session with
 * an older value is signed out on its next /saas request, whatever the
 * session driver (file, database, redis or cookie).
 * Specification: engineering/specs/platform/hq-two-factor.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_staff_identities', function (Blueprint $t) {
            $t->unsignedInteger('two_factor_generation')->default(0)->after('active');
        });
    }

    public function down(): void
    {
        if (DB::table('platform_staff_identities')->where('two_factor_generation', '>', 0)->exists()) {
            throw new RuntimeException('Two-factor resets have been recorded; rollback would let older sessions back in. Rollback refused.');
        }
        Schema::table('platform_staff_identities', function (Blueprint $t) {
            $t->dropColumn('two_factor_generation');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The explicit, expiring grant behind the platform admin area (/saas). The
 * platform staff identity alone only excludes a person from school data; this
 * is the separate, time-limited permission to manage schools, set from the
 * operator console and never from a web page. One row per person; an expired
 * or inactive row grants nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admin_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->boolean('active')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('platform_admin_grants')->exists()) {
            throw new RuntimeException('Retain the platform admin grant history.');
        }
        Schema::dropIfExists('platform_admin_grants');
    }
};

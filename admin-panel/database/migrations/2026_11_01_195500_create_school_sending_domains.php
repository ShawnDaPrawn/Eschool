<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * School sending domains (Action Plan 2.2): the one domain a school sends its
 * notices from, its Amazon SES identity, the DKIM tokens and MAIL FROM domain
 * the school adds to its DNS, and whether SES has verified it. One domain per
 * school, and a domain belongs to one school across the platform.
 * Specification: engineering/specs/platform/sending-domains.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_sending_domains', function (Blueprint $t) {
            $t->id();
            // Unique: one domain per school (the index also serves tenancy).
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            // Lower case, no trailing dot; unique across every school.
            $t->string('domain', 253)->unique();
            // The SES email identity's name (SES v2 does not return an ARN).
            $t->string('ses_identity', 253);
            $t->json('dkim_tokens')->nullable();
            $t->string('mail_from_domain', 253);
            // pending, verified or failed.
            $t->string('status', 12)->default('pending');
            // As SES last reported them: PENDING, SUCCESS, FAILED, TEMPORARY_FAILURE, NOT_STARTED.
            $t->string('dkim_status', 20)->nullable();
            $t->string('mail_from_status', 20)->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamp('last_checked_at')->nullable();
            // A short code for the last problem (for example dkim_failed, ses_unreachable); never a message from SES.
            $t->string('last_error', 64)->nullable();
            $t->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'last_checked_at'], 'school_sending_domains_poll_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('school_sending_domains') && DB::table('school_sending_domains')->exists()) {
            throw new RuntimeException('Disconnect every school sending domain (deleting its SES identity) before rolling back.');
        }
        Schema::dropIfExists('school_sending_domains');
    }
};

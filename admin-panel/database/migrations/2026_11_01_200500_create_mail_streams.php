<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SES streams (Action Plan 2.3): the Amazon SES configuration set each school
 * sends each stream of mail through (account and bulk), and the platform's
 * own pair (no school). Holds what SES last said about each set and the last
 * tenant sending status SES published for the school. Specification:
 * engineering/specs/platform/ses-streams.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_streams', function (Blueprint $t) {
            $t->id();
            // Null for the platform's own pair (mail sent for no school).
            $t->unsignedBigInteger('school_id')->nullable()->index();
            // account or bulk.
            $t->string('stream', 10);
            // <prefix>-s<school id>-<stream> or <prefix>-platform-<stream>; unique in the SES account.
            $t->string('configuration_set', 64)->unique();
            // pending (not set up in SES yet), ready, or failed (last_error says why).
            $t->string('status', 12)->default('pending');
            // As SES last reported the set: whether it may send, and its VDM options.
            $t->boolean('sending_enabled')->nullable();
            $t->boolean('engagement_tracking')->default(false);
            $t->boolean('optimized_delivery')->default(false);
            $t->boolean('event_destination')->default(false);
            // The last tenant sending status event SES published for the school: ENABLED, DISABLED or REINSTATED.
            $t->string('tenant_status', 12)->nullable();
            $t->string('tenant_status_origin', 20)->nullable();
            $t->string('tenant_status_cause', 191)->nullable();
            $t->timestamp('tenant_status_at')->nullable();
            // A short code for the last problem (for example ses_unreachable); never a message from SES.
            $t->string('last_error', 64)->nullable();
            $t->timestamp('ready_at')->nullable();
            $t->timestamp('checked_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'checked_at'], 'mail_streams_poll_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('mail_streams') && DB::table('mail_streams')->exists()) {
            throw new RuntimeException('mail_streams records SES configuration sets in use: delete them in SES and empty the table before rolling back.');
        }
        Schema::dropIfExists('mail_streams');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SES streams (Action Plan 2.3): which stream a mail went out on (account or
 * bulk) and the SES configuration set it named, so bounce and complaint rates
 * can be read per school and stream. engineering/specs/platform/ses-streams.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $t) {
            $t->string('mail_stream', 10)->nullable()->after('channel');
            $t->string('configuration_set', 64)->nullable()->after('provider_message_id');
            $t->index(['mail_stream', 'created_at'], 'notification_deliveries_stream_index');
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $t) {
            $t->dropIndex('notification_deliveries_stream_index');
            $t->dropColumn(['mail_stream', 'configuration_set']);
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links each delivery attempt to the school notice it carried, so a sender can
 * see per-message delivery (Action Plan 2.4, delivery report). Older rows and
 * messages that are not school notices (password and invitation emails) stay
 * null. engineering/specs/communication/delivery-report.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->unsignedBigInteger('notification_id')->nullable()->after('delivery_key');
            $table->index(['school_id', 'notification_id', 'recipient_user_id'], 'notification_deliveries_notice_recipient');
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->dropIndex('notification_deliveries_notice_recipient');
            $table->dropColumn('notification_id');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SMS and WhatsApp for urgent notices (Action Plan 2.7). Each school's own
 * opt-in (off until the school switches a channel on), and on each texted
 * delivery the segments and Twilio's price, in integer cents, for the
 * pass-through report. engineering/specs/platform/sms-whatsapp.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_messaging_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('sms_enabled')->default(false);
            $t->boolean('whatsapp_enabled')->default(false);
            $t->json('sms_types')->nullable();
            $t->json('whatsapp_types')->nullable();
            $t->timestamp('whatsapp_consent_confirmed_at')->nullable();
            $t->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });

        Schema::table('notification_deliveries', function (Blueprint $t): void {
            $t->unsignedSmallInteger('segments')->nullable()->after('provider_message_id');
            $t->unsignedInteger('price_cents')->nullable()->after('segments')->comment('what the provider charged, rounded to whole cents');
            $t->char('price_currency', 3)->nullable()->after('price_cents');
            $t->index(['school_id', 'channel', 'sent_at'], 'notification_deliveries_school_channel_sent');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('school_messaging_settings') && DB::table('school_messaging_settings')->exists()) {
            throw new RuntimeException('Schools have SMS and WhatsApp settings: restore a tested backup instead of rolling back.');
        }
        if (Schema::hasColumn('notification_deliveries', 'price_cents') && DB::table('notification_deliveries')->whereNotNull('price_cents')->exists()) {
            throw new RuntimeException('Texted deliveries carry prices: restore a tested backup instead of rolling back.');
        }
        Schema::table('notification_deliveries', function (Blueprint $t): void {
            $t->dropIndex('notification_deliveries_school_channel_sent');
            $t->dropColumn(['segments', 'price_cents', 'price_currency']);
        });
        Schema::dropIfExists('school_messaging_settings');
    }
};

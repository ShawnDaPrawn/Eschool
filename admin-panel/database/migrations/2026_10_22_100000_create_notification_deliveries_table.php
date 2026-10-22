<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per attempt to deliver one mail or push message (Build Bible 3.1,
 * Notifier). Rows of the same message share delivery_key. The recipient is a
 * user id where there is one, otherwise a SHA-256 hash of the address, token
 * or topic: raw addresses and device tokens are never stored here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->uuid('delivery_key');
            $table->string('type', 100);
            $table->string('channel', 20)->comment('mail, push');
            $table->unsignedBigInteger('recipient_user_id')->nullable()->index();
            $table->char('recipient_hash', 64)->nullable();
            $table->string('status', 20)->comment('queued, sent, failed, delivered, bounced, complained');
            $table->string('provider', 30)->nullable()->comment('mailer or fcm');
            $table->string('provider_message_id', 191)->nullable()->index();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->string('error_summary', 500)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('feedback_at')->nullable()->comment('when the provider reported delivered, bounced or complained');
            $table->timestamps();

            $table->index(['delivery_key', 'attempt']);
            $table->index(['channel', 'status', 'created_at']);
        });

        // Bounce and complaint notifications are processed once per provider
        // message id (SNS MessageId), whatever the provider retries.
        Schema::create('webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 191);
            $table->string('event_type', 100)->nullable();
            $table->timestamp('received_at')->useCurrent();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_receipts');
        Schema::dropIfExists('notification_deliveries');
    }
};

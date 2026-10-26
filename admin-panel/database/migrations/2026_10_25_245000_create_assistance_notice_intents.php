<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_assistance_notice_intents', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('recipient_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignUuid('thread_id')->constrained('school_support_threads')->restrictOnDelete();
            $t->foreignUuid('message_id')->nullable()->constrained('school_support_messages')->restrictOnDelete();
            $t->string('type', 64);
            $t->boolean('queue_only')->default(false);
            $t->string('source_key', 191);
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->dateTime('expires_at');
            $t->timestamps();
            $t->unique(['school_id', 'source_key'], 'assistance_notice_source');
            $t->index(['school_id', 'recipient_user_id', 'expires_at'], 'assistance_notice_recipient');
        });
    }

    public function down(): void
    {
        if (DB::table('school_assistance_notice_intents')->exists()) {
            throw new RuntimeException('Retain assistance notice intents before rollback.');
        }
        Schema::dropIfExists('school_assistance_notice_intents');
    }
};

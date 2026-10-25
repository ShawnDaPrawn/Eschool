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
        Schema::create('discipline_notice_intents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('record_id')->constrained('discipline_records')->restrictOnDelete();
            $table->unsignedInteger('record_revision');
            $table->foreignId('detention_session_id')->nullable()->constrained('detention_sessions')->restrictOnDelete();
            $table->unsignedInteger('detention_revision')->nullable();
            $table->string('source_key', 160);
            $table->string('type', 70);
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'user_id', 'source_key', 'type'], 'discipline_notice_once');
            $table->index(['school_id', 'notification_id'], 'discipline_notice_source');
        });
    }

    public function down(): void
    {
        if (DB::table('discipline_notice_intents')->exists()) {
            throw new RuntimeException('Retained discipline notice receipts must be exported before rollback.');
        }
        Schema::dropIfExists('discipline_notice_intents');
    }
};

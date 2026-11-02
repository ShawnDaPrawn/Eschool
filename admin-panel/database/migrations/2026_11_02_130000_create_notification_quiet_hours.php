<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A person's quiet hours for push notifications (Action Plan 1.5). Global,
 * like notification_preferences: one human chooses them once, for every school.
 * engineering/specs/platform/quiet-hours.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_quiet_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->time('starts_at')->comment('Local time quiet hours start');
            $table->time('ends_at')->comment('Local time they end; before starts_at means overnight');
            $table->string('timezone', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_quiet_hours');
    }
};

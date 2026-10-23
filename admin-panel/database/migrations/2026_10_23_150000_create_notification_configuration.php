<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('portal', 16);
            $table->string('type', 80);
            $table->string('channel', 16);
            $table->string('mode', 16);
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['school_id', 'portal', 'type', 'channel'], 'notification_rule_unique');
        });
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('type', 80);
            $table->string('channel', 16);
            $table->boolean('enabled');
            $table->timestamps();
            $table->unique(['user_id', 'type', 'channel'], 'notification_preference_unique');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->boolean('preferences_enforced')->default(false);
            $table->json('delivery_payload')->nullable();
            $table->string('source_key', 100)->nullable();
            $table->unique(['school_id', 'source_key'], 'notification_source_unique');
        });
        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('channel', 16);
            $table->timestamps();
            $table->unique(['school_id', 'notification_id', 'user_id', 'channel'], 'notification_dispatch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dispatches');
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique('notification_source_unique');
            $table->dropColumn(['preferences_enforced', 'delivery_payload', 'source_key']);
        });
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_rules');
    }
};

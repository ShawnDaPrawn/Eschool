<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A learner's calendar feed, subscribed to from a phone or desktop calendar
 * through a secret link (Action Plan 1.10). Only the SHA-256 hash of the
 * link's token is stored; the owner can revoke it at any time.
 * engineering/specs/academics/calendar-feed.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('user_id')->constrained('users')->comment('The learner or guardian who made the link');
            $table->unsignedBigInteger('learner_id');
            $table->char('token_hash', 64)->unique();
            $table->boolean('include_lessons')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_feeds');
    }
};

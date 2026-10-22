<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The outbox (Build Bible 3.1): domain events written in the same transaction
 * as the change they describe, then handed to their listeners by the
 * DispatchOutbox job. available_at holds back an event whose listener failed
 * until its backoff has passed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->string('type', 100)->index();
            $table->json('payload');
            $table->timestamp('occurred_at', 6);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error', 500)->nullable();

            $table->index(['dispatched_at', 'available_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_events');
    }
};

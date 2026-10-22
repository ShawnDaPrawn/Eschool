<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API v2 writes that carry an Idempotency-Key: the key, who sent it, the
 * route, a hash of the request, and the response, kept for 24 hours so a
 * retry gets the same answer instead of doing the work twice. See
 * App\Http\Middleware\HandleIdempotencyKey.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Keys are up to 255 characters; the unique index is on their hash.
            $table->string('key', 255);
            $table->char('key_hash', 64);
            $table->string('method', 10);
            $table->string('route', 255);
            $table->char('request_hash', 64);
            // Null while the first request is still running.
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['user_id', 'key_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};

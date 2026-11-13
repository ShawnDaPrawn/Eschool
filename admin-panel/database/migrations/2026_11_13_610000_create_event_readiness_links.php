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
        Schema::table('events', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'events_readiness_identity'));
        Schema::create('event_readiness_links', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->unsignedBigInteger('event_id');
            $t->uuid('consent_request_id')->nullable();
            $t->unsignedBigInteger('ticket_event_id')->nullable();
            $t->text('transport_notes')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('updated_by')->constrained('users');
            $t->timestamps();
            $t->unique(['school_id', 'event_id']);
            foreach (['event_id' => 'events', 'consent_request_id' => 'consent_requests', 'ticket_event_id' => 'ticket_events'] as $column => $table) {
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('event_readiness_links') && DB::table('event_readiness_links')->exists()) {
            throw new RuntimeException('Event readiness associations exist. Retain their school audit history.');
        }
        Schema::dropIfExists('event_readiness_links');
        Schema::table('events', fn (Blueprint $t) => $t->dropUnique('events_readiness_identity'));
    }
};

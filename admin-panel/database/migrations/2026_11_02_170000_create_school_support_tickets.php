<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The support desk (Action Plan SA.17): a school's office opens a ticket to
 * Navigate Ed from the panel, HQ answers it from /saas. Messages are the
 * school's and HQ's free text, encrypted at rest (the model's encrypted
 * cast); HQ's internal notes are messages the school never reads.
 * platform_support_views keeps when each HQ staff member last looked at the
 * queue, for "new since you last looked". Specification:
 * engineering/specs/platform/support-desk.md.
 */
return new class extends Migration
{
    private const TABLES = ['platform_support_views', 'school_support_ticket_messages', 'school_support_tickets'];

    public function up(): void
    {
        Schema::create('school_support_tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->string('subject', 150);
            // question, problem, request or billing.
            $t->string('category', 16);
            // normal or urgent.
            $t->string('priority', 8);
            // open, waiting_on_school, resolved or closed.
            $t->string('status', 20)->default('open');
            $t->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('closed_at')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'priority', 'created_at'], 'school_support_tickets_queue_index');
        });
        Schema::create('school_support_ticket_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('ticket_id')->constrained('school_support_tickets')->restrictOnDelete();
            // school or hq: who wrote it.
            $t->string('side', 8);
            // An HQ note the school never sees.
            $t->boolean('internal')->default(false);
            // Encrypted at rest.
            $t->text('body');
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['ticket_id', 'created_at'], 'school_support_ticket_messages_thread_index');
        });
        Schema::create('platform_support_views', function (Blueprint $t) {
            $t->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $t->dateTime('seen_at');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if ($table !== 'platform_support_views' && Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain support tickets.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

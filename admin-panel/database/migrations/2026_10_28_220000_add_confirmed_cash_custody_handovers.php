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
        Schema::create('cash_handovers', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->bigInteger('available_before_cents');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->text('private_evidence');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->index(['school_id', 'sender_id', 'currency']);
            $t->index(['school_id', 'recipient_id', 'currency']);
        });
        Schema::create('cash_handover_outcomes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('handover_id');
            $t->foreign(['school_id', 'handover_id'])->references(['school_id', 'id'])->on('cash_handovers')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('status', 12);
            $t->date('effective_on');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->text('private_evidence');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'handover_id']);
            $t->unique(['school_id', 'operation_id']);
        });
        DB::statement("ALTER TABLE cash_handovers ADD CONSTRAINT cash_handover_shape CHECK (sender_id<>recipient_id AND currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND available_before_cents>=amount_cents AND CHAR_LENGTH(private_evidence)>0)");
        DB::statement("ALTER TABLE cash_handover_outcomes ADD CONSTRAINT cash_handover_outcome_shape CHECK (status IN ('accepted','cancelled','rejected') AND CHAR_LENGTH(private_evidence)>0)");
        DB::unprepared("CREATE TRIGGER cash_handover_outcome_actor BEFORE INSERT ON cash_handover_outcomes FOR EACH ROW BEGIN IF NOT EXISTS (SELECT 1 FROM cash_handovers h WHERE h.school_id=NEW.school_id AND h.id=NEW.handover_id AND ((NEW.status='cancelled' AND h.sender_id=NEW.actor_id) OR (NEW.status IN ('accepted','rejected') AND h.recipient_id=NEW.actor_id))) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only the original cash sender or recipient can record this outcome'; END IF; END");
        foreach (['cash_handovers', 'cash_handover_outcomes'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $event) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($event).' BEFORE '.$event.' ON '.$table." FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cash custody history is immutable'");
            }
        }
    }

    public function down(): void
    {
        if (DB::table('cash_handovers')->exists() || DB::table('cash_handover_outcomes')->exists()) {
            throw new RuntimeException('Retain original cash custody handovers and outcomes.');
        }
        Schema::drop('cash_handover_outcomes');
        Schema::drop('cash_handovers');
    }
};

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
        Schema::create('ledger_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('reference', 128)->collation('utf8mb4_bin');
            $t->string('type', 24)->collation('utf8mb4_bin');
            $t->string('module', 24)->collation('utf8mb4_bin');
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->string('normal_direction', 6)->collation('utf8mb4_bin');
            $t->bigInteger('balance_cents')->default(0);
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'reference']);
            $t->unique(['school_id', 'id']);
            $t->index(['school_id', 'module']);
        });
        Schema::create('ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('idempotency_key', 128)->collation('utf8mb4_bin');
            $t->string('source', 128)->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->string('description', 255)->collation('utf8mb4_bin');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            // A draft exists only inside the posting transaction. Finalization
            // is the MySQL/MariaDB cross-row invariant enforcement point.
            $t->dateTime('posted_at', 6)->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'idempotency_key']);
            $t->unique(['school_id', 'source']);
            $t->unique(['school_id', 'id']);
        });
        Schema::create('ledger_postings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('entry_id');
            $t->unsignedBigInteger('account_id');
            $t->bigInteger('cents');
            $t->string('direction', 6)->collation('utf8mb4_bin');
            $t->foreign(['school_id', 'entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->foreign(['school_id', 'account_id'])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
            $t->index(['school_id', 'account_id', 'entry_id'], 'ledger_account_history');
        });

        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (
            type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk')
            AND currency REGEXP '^[A-Z]{3}$'
            AND normal_direction = CASE WHEN type IN ('fee_receivable','cash_desk','clearing') THEN 'debit' ELSE 'credit' END
            AND module = CASE type WHEN 'wallet' THEN 'wallet' WHEN 'vendor' THEN 'vendors' ELSE 'fees' END
            AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000
            AND (type <> 'wallet' OR balance_cents >= 0)
            AND (type NOT IN ('clearing','due_to_school') OR balance_cents = 0))");
        DB::statement("ALTER TABLE ledger_postings ADD CONSTRAINT ledger_posting_rules CHECK (
            cents BETWEEN 1 AND 9000000000000000 AND direction IN ('debit','credit'))");
        DB::unprepared("CREATE TRIGGER ledger_entry_insert BEFORE INSERT ON ledger_entries FOR EACH ROW
            BEGIN
                IF NEW.posted_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger entries must finalize after their postings'; END IF;
            END");
        DB::unprepared("CREATE TRIGGER ledger_entry_finalize BEFORE UPDATE ON ledger_entries FOR EACH ROW
            BEGIN
                DECLARE posting_count BIGINT;
                DECLARE net_cents DECIMAL(65,0);
                IF OLD.posted_at IS NOT NULL OR NEW.posted_at IS NULL
                    OR NOT (NEW.school_id <=> OLD.school_id) OR NEW.id <> OLD.id
                    OR BINARY NEW.idempotency_key <> BINARY OLD.idempotency_key OR BINARY NEW.source <> BINARY OLD.source
                    OR BINARY NEW.request_hash <> BINARY OLD.request_hash OR BINARY NEW.description <> BINARY OLD.description
                    OR BINARY NEW.currency <> BINARY OLD.currency OR NEW.created_at <> OLD.created_at
                    THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger entries are immutable'; END IF;
                SELECT COUNT(*), COALESCE(SUM(CASE direction WHEN 'debit' THEN cents ELSE -cents END),0)
                    INTO posting_count, net_cents FROM ledger_postings WHERE entry_id = OLD.id AND school_id = OLD.school_id;
                IF posting_count < 2 OR net_cents <> 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger entry must balance'; END IF;
            END");
        DB::unprepared("CREATE TRIGGER ledger_posting_insert BEFORE INSERT ON ledger_postings FOR EACH ROW
            BEGIN
                DECLARE entry_posted DATETIME(6);
                DECLARE entry_currency CHAR(3);
                DECLARE account_currency CHAR(3);
                DECLARE account_type VARCHAR(24);
                SELECT posted_at, currency INTO entry_posted, entry_currency FROM ledger_entries
                    WHERE school_id = NEW.school_id AND id = NEW.entry_id FOR UPDATE;
                SELECT currency, type INTO account_currency, account_type FROM ledger_accounts
                    WHERE school_id = NEW.school_id AND id = NEW.account_id;
                IF entry_posted IS NOT NULL OR entry_currency IS NULL OR account_currency IS NULL
                    OR entry_currency <> account_currency OR account_type IN ('clearing','due_to_school')
                    THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger posting context is invalid or sealed'; END IF;
            END");
        foreach (['ledger_entries' => 'entry', 'ledger_postings' => 'posting'] as $table => $label) {
            DB::unprepared("CREATE TRIGGER ledger_{$label}_delete BEFORE DELETE ON {$table} FOR EACH ROW
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger history cannot be deleted'");
        }
        DB::unprepared("CREATE TRIGGER ledger_posting_update BEFORE UPDATE ON ledger_postings FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger postings are immutable'");
        DB::unprepared("CREATE TRIGGER ledger_account_identity BEFORE UPDATE ON ledger_accounts FOR EACH ROW
            BEGIN
                IF NEW.id <> OLD.id OR NEW.school_id <> OLD.school_id OR BINARY NEW.reference <> BINARY OLD.reference
                    OR BINARY NEW.type <> BINARY OLD.type OR BINARY NEW.module <> BINARY OLD.module OR BINARY NEW.currency <> BINARY OLD.currency
                    OR BINARY NEW.normal_direction <> BINARY OLD.normal_direction OR NOT (NEW.owner_user_id <=> OLD.owner_user_id)
                    OR NEW.created_at <> OLD.created_at
                    THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ledger account identity is immutable'; END IF;
            END");
    }

    public function down(): void
    {
        if (DB::table('ledger_entries')->exists() || DB::table('ledger_accounts')->where('balance_cents', '<>', 0)->exists()) {
            throw new RuntimeException('Retain and reconcile ledger history before rollback.');
        }
        Schema::dropIfExists('ledger_postings');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};

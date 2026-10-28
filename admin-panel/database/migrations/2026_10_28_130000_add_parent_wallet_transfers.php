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
        Schema::create('wallet_transfer_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->string('child_returns', 40);
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        DB::statement("ALTER TABLE wallet_transfer_policies ADD CONSTRAINT wallet_transfer_policy_shape CHECK (child_returns IN ('disabled','current_approving_guardian') AND revision>0)");
        Schema::create('wallet_transfers', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            foreach (['from_wallet_id', 'to_wallet_id'] as $key) {
                $t->unsignedBigInteger($key);
                $t->foreign(['school_id', $key])->references(['school_id', 'id'])->on('wallet_accounts')->restrictOnDelete();
            }
            $t->foreignId('from_owner_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('to_owner_id')->constrained('users')->restrictOnDelete();
            $t->string('kind', 12);
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->unsignedInteger('policy_revision')->nullable();
            $t->unsignedBigInteger('ledger_entry_id');
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'ledger_entry_id']);
        });
        DB::statement("ALTER TABLE wallet_transfers ADD CONSTRAINT wallet_transfer_shape CHECK (operation_id REGEXP '^[0-9a-fA-F-]{36}$' AND currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND from_wallet_id<>to_wallet_id AND from_owner_id<>to_owner_id AND ((kind='to_child' AND from_owner_id=actor_id) OR (kind='to_parent' AND to_owner_id=actor_id AND policy_revision IS NOT NULL)))");
        DB::unprepared("CREATE TRIGGER wallet_transfer_insert BEFORE INSERT ON wallet_transfers FOR EACH ROW BEGIN
            DECLARE amount_debit DECIMAL(65,0); DECLARE amount_credit DECIMAL(65,0); DECLARE posted_count INT;
            SELECT COALESCE(SUM(CASE WHEN p.direction='debit' AND p.account_id=f.ledger_account_id THEN p.cents ELSE 0 END),0), COALESCE(SUM(CASE WHEN p.direction='credit' AND p.account_id=t.ledger_account_id THEN p.cents ELSE 0 END),0), COUNT(*) INTO amount_debit,amount_credit,posted_count FROM ledger_postings p JOIN ledger_entries e ON e.school_id=p.school_id AND e.id=p.entry_id JOIN wallet_accounts f ON f.school_id=NEW.school_id AND f.id=NEW.from_wallet_id AND f.user_id=NEW.from_owner_id AND f.currency=NEW.currency JOIN wallet_accounts t ON t.school_id=NEW.school_id AND t.id=NEW.to_wallet_id AND t.user_id=NEW.to_owner_id AND t.currency=NEW.currency WHERE p.school_id=NEW.school_id AND p.entry_id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency=NEW.currency AND e.source=CONCAT('wallet-transfer:',NEW.operation_id);
            IF amount_debit<>NEW.amount_cents OR amount_credit<>NEW.amount_cents OR posted_count<>2 OR (NEW.policy_revision IS NOT NULL AND NOT EXISTS(SELECT 1 FROM wallet_transfer_policies WHERE school_id=NEW.school_id AND revision=NEW.policy_revision AND (NEW.kind='to_child' OR child_returns='current_approving_guardian'))) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet transfer retains its exact personal owners, postings and return policy'; END IF;
        END");
        DB::unprepared("CREATE TRIGGER wallet_transfer_policy_update BEFORE UPDATE ON wallet_transfer_policies FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet transfer policy retains school and ordered revisions'; END IF; END");
        foreach (['wallet_transfers' => ['UPDATE', 'DELETE'], 'wallet_transfer_policies' => ['DELETE']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet transfers and policy history are retained'");
            }
        }
    }

    public function down(): void
    {
        foreach (['wallet_transfers', 'wallet_transfer_policies'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain parent wallet transfers and policy history.');
            }
        }
        Schema::dropIfExists('wallet_transfers');
        Schema::dropIfExists('wallet_transfer_policies');
    }
};

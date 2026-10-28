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
        Schema::create('wallet_deposit_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('cash_allowed');
            $t->boolean('eft_allowed');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        DB::statement('ALTER TABLE wallet_deposit_policies ADD CONSTRAINT wallet_deposit_policy_shape CHECK (revision>0 AND cash_allowed IN (0,1) AND eft_allowed IN (0,1))');
        DB::unprepared("CREATE TRIGGER wallet_deposit_policy_update BEFORE UPDATE ON wallet_deposit_policies FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Parent deposit policy retains school and ordered revisions'; END IF; END");
        DB::unprepared("CREATE TRIGGER wallet_deposit_policy_no_delete BEFORE DELETE ON wallet_deposit_policies FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Parent deposit policy history is retained'");
        Schema::create('wallet_deposit_claims', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('wallet_account_id');
            $t->foreign(['school_id', 'wallet_account_id'])->references(['school_id', 'id'])->on('wallet_accounts')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('requested_cents');
            $t->string('method', 12);
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->date('paid_on');
            $t->unsignedInteger('policy_revision');
            $t->text('evidence');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
        });
        DB::statement("ALTER TABLE wallet_deposit_claims ADD CONSTRAINT wallet_deposit_claim_shape CHECK (requested_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$' AND method IN ('cash','eft') AND operation_id REGEXP '^[0-9a-fA-F-]{36}$' AND source_reference REGEXP '^[A-Z0-9][A-Z0-9 ._:/-]{0,95}$' AND source_reference=UPPER(TRIM(source_reference)) AND LOCATE('  ',source_reference)=0 AND policy_revision>0 AND CHAR_LENGTH(evidence)>0)");
        Schema::create('wallet_parent_deposits', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('claim_id');
            $t->foreign(['school_id', 'claim_id'])->references(['school_id', 'id'])->on('wallet_deposit_claims')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->string('method', 12);
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->date('received_on');
            $t->string('provider', 16);
            $t->string('collected_by', 16);
            $t->bigInteger('fee_cents');
            $t->bigInteger('net_cents');
            $t->string('settlement_reference', 96)->nullable();
            $t->unsignedBigInteger('asset_account_id');
            $t->foreign(['school_id', 'asset_account_id'])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
            $t->unsignedBigInteger('ledger_entry_id');
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->unsignedInteger('policy_revision');
            $t->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('cash_operator_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('evidence');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'claim_id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'ledger_entry_id']);
        });
        DB::statement("ALTER TABLE wallet_parent_deposits ADD CONSTRAINT wallet_parent_deposit_shape CHECK (amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$' AND operation_id REGEXP '^[0-9a-fA-F-]{36}$' AND method IN ('cash','eft') AND provider='manual' AND collected_by='school' AND fee_cents=0 AND net_cents=amount_cents AND source_reference REGEXP '^[A-Z0-9][A-Z0-9 ._:/-]{0,95}$' AND source_reference=UPPER(TRIM(source_reference)) AND LOCATE('  ',source_reference)=0 AND policy_revision>0 AND CHAR_LENGTH(evidence)>0 AND ((method='cash' AND cash_operator_id IS NOT NULL AND cash_operator_id=verified_by AND settlement_reference IS NULL) OR (method='eft' AND cash_operator_id IS NULL AND settlement_reference IS NOT NULL AND settlement_reference=source_reference)))");
        Schema::create('wallet_deposit_actions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('claim_id');
            $t->foreign(['school_id', 'claim_id'])->references(['school_id', 'id'])->on('wallet_deposit_claims')->restrictOnDelete();
            $t->string('status', 12);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('deposit_id')->nullable();
            $t->foreign(['school_id', 'deposit_id'])->references(['school_id', 'id'])->on('wallet_parent_deposits')->restrictOnDelete();
            $t->string('reason_code', 24);
            $t->text('private_note')->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'claim_id']);
        });
        DB::statement("ALTER TABLE wallet_deposit_actions ADD CONSTRAINT wallet_deposit_action_shape CHECK ((status='verified' AND deposit_id IS NOT NULL AND reason_code='verified') OR (status='cancelled' AND deposit_id IS NULL AND reason_code='withdrawn') OR (status='rejected' AND deposit_id IS NULL AND reason_code IN ('not_verified','duplicate','request_incorrect') AND private_note IS NOT NULL AND CHAR_LENGTH(private_note)>0))");
        DB::unprepared("CREATE TRIGGER wallet_deposit_claim_insert BEFORE INSERT ON wallet_deposit_claims FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM wallet_accounts WHERE school_id=NEW.school_id AND id=NEW.wallet_account_id AND user_id=NEW.actor_id AND currency=NEW.currency) OR NOT EXISTS(SELECT 1 FROM wallet_deposit_policies WHERE school_id=NEW.school_id AND revision=NEW.policy_revision AND IF(NEW.method='cash',cash_allowed,eft_allowed)=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Parent deposit requests retain original personal owner and explicit receipt method'; END IF; END");
        DB::unprepared("CREATE TRIGGER wallet_parent_deposit_insert BEFORE INSERT ON wallet_parent_deposits FOR EACH ROW BEGIN
            DECLARE amount_debit DECIMAL(65,0); DECLARE amount_credit DECIMAL(65,0); DECLARE posted_count INT;
            SELECT COALESCE(SUM(CASE WHEN p.direction='debit' AND a.id=NEW.asset_account_id AND a.type=IF(NEW.method='cash','cash_desk','wallet_bank') AND a.module='wallet' AND a.owner_user_id IS NULL AND a.reference=CONCAT('wallet:',NEW.method,':',NEW.currency) THEN p.cents ELSE 0 END),0),COALESCE(SUM(CASE WHEN p.direction='credit' AND a.id=w.ledger_account_id AND a.type='wallet' AND a.module='wallet' AND a.owner_user_id=c.actor_id THEN p.cents ELSE 0 END),0),COUNT(*) INTO amount_debit,amount_credit,posted_count FROM ledger_postings p JOIN ledger_accounts a ON a.school_id=p.school_id AND a.id=p.account_id JOIN ledger_entries e ON e.school_id=p.school_id AND e.id=p.entry_id JOIN wallet_deposit_claims c ON c.school_id=NEW.school_id AND c.id=NEW.claim_id AND c.currency=NEW.currency AND c.actor_id<>NEW.verified_by JOIN wallet_accounts w ON w.school_id=c.school_id AND w.id=c.wallet_account_id AND w.user_id=c.actor_id AND w.currency=c.currency WHERE p.school_id=NEW.school_id AND p.entry_id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency=NEW.currency AND e.source=CONCAT('wallet-parent-deposit:',NEW.operation_id);
            IF amount_debit<>NEW.amount_cents OR amount_credit<>NEW.amount_cents OR posted_count<>2 OR EXISTS(SELECT 1 FROM wallet_deposit_actions WHERE school_id=NEW.school_id AND claim_id=NEW.claim_id) OR NOT EXISTS(SELECT 1 FROM wallet_deposit_policies WHERE school_id=NEW.school_id AND revision=NEW.policy_revision AND IF(NEW.method='cash',cash_allowed,eft_allowed)=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Verified parent deposits retain exact personal backing, original holder and independent verifier'; END IF;
        END");
        DB::unprepared("CREATE TRIGGER wallet_deposit_action_insert BEFORE INSERT ON wallet_deposit_actions FOR EACH ROW BEGIN
            IF (NEW.status='verified' AND NOT EXISTS(SELECT 1 FROM wallet_parent_deposits WHERE school_id=NEW.school_id AND claim_id=NEW.claim_id AND id=NEW.deposit_id AND verified_by=NEW.actor_id)) OR (NEW.status<>'verified' AND EXISTS(SELECT 1 FROM wallet_parent_deposits WHERE school_id=NEW.school_id AND claim_id=NEW.claim_id)) OR (NEW.status='cancelled' AND NOT EXISTS(SELECT 1 FROM wallet_deposit_claims WHERE school_id=NEW.school_id AND id=NEW.claim_id AND actor_id=NEW.actor_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Deposit outcomes retain original request, personal owner and verified receipt'; END IF;
        END");
        $this->receiptRegistry(true);
        DB::unprepared("CREATE TRIGGER wallet_parent_deposit_external_identity AFTER INSERT ON wallet_parent_deposits FOR EACH ROW INSERT INTO financial_receipts(school_id,method,source_reference,domain,record_id,ledger_entry_id,amount_cents,currency) VALUES(NEW.school_id,NEW.method,NEW.source_reference,'parent',NEW.id,NEW.ledger_entry_id,NEW.amount_cents,NEW.currency)");
        foreach (['wallet_deposit_claims', 'wallet_parent_deposits', 'wallet_deposit_actions'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Parent deposit requests, evidence and verified financial history are retained'");
            }
        }
    }

    private function receiptRegistry(bool $parent): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS financial_receipt_insert');
        DB::statement('ALTER TABLE financial_receipts DROP CONSTRAINT financial_receipt_shape');
        $domains = $parent ? "'fees','wallet','parent'" : "'fees','wallet'";
        DB::statement("ALTER TABLE financial_receipts ADD CONSTRAINT financial_receipt_shape CHECK (method IN ('cash','eft') AND domain IN ($domains) AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$')");
        $extra = $parent ? " OR (NEW.domain='parent' AND NOT EXISTS(SELECT 1 FROM wallet_parent_deposits WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency))" : '';
        DB::unprepared("CREATE TRIGGER financial_receipt_insert BEFORE INSERT ON financial_receipts FOR EACH ROW BEGIN IF (NEW.domain='fees' AND NOT EXISTS(SELECT 1 FROM payments WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency)) OR (NEW.domain='wallet' AND NOT EXISTS(SELECT 1 FROM wallet_fundings WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency)) $extra THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='External receipt identity retains its original immutable domain receipt'; END IF; END");
    }

    public function down(): void
    {
        foreach (['wallet_deposit_claims', 'wallet_parent_deposits', 'wallet_deposit_actions', 'wallet_deposit_policies'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain parent deposit requests and financial history.');
            }
        }
        $this->receiptRegistry(false);
        foreach (['wallet_deposit_actions', 'wallet_parent_deposits', 'wallet_deposit_claims', 'wallet_deposit_policies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

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
        $this->ledgerTypes(true);
        Schema::create('wallet_funding_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->boolean('cash_allowed');
            $t->boolean('eft_allowed');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique('school_id');
        });
        DB::statement('ALTER TABLE wallet_funding_policies ADD CONSTRAINT wallet_funding_policy_shape CHECK (revision>0 AND cash_allowed IN (0,1) AND eft_allowed IN (0,1) AND (cash_allowed=1 OR eft_allowed=1))');
        Schema::create('wallet_fundings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->string('method', 12);
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->date('received_on');
            $t->string('ownership', 12);
            $t->string('provider', 16);
            $t->string('collected_by', 16);
            $t->bigInteger('fee_cents');
            $t->bigInteger('net_cents');
            $t->string('settlement_reference', 96)->nullable();
            foreach (['master_account_id', 'asset_account_id'] as $key) {
                $t->unsignedBigInteger($key);
                $t->foreign(['school_id', $key])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
            }
            $t->unsignedBigInteger('ledger_entry_id');
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->unsignedInteger('policy_revision');
            $t->text('evidence');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'ledger_entry_id']);
            $t->unique(['school_id', 'method', 'source_reference'], 'wallet_funding_business_source');
        });
        DB::statement("ALTER TABLE wallet_fundings ADD CONSTRAINT wallet_funding_shape CHECK (currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND method IN ('cash','eft') AND ownership='school' AND provider='manual' AND collected_by='school' AND fee_cents=0 AND net_cents=amount_cents AND policy_revision>0 AND CHAR_LENGTH(evidence)>0 AND source_reference REGEXP '^[A-Z0-9][A-Z0-9 ._:/-]{0,95}$' AND source_reference=UPPER(TRIM(source_reference)) AND LOCATE('  ',source_reference)=0 AND ((method='eft' AND settlement_reference=source_reference) OR (method='cash' AND settlement_reference IS NULL)))");
        Schema::create('financial_receipts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('method', 12);
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->string('domain', 12);
            $t->unsignedBigInteger('record_id');
            $t->unsignedBigInteger('ledger_entry_id');
            $t->bigInteger('amount_cents');
            $t->char('currency', 3);
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->unique(['school_id', 'method', 'source_reference'], 'financial_receipt_source');
            $t->unique(['school_id', 'domain', 'record_id'], 'financial_receipt_record');
            $t->unique(['school_id', 'ledger_entry_id'], 'financial_receipt_entry');
        });
        DB::statement("ALTER TABLE financial_receipts ADD CONSTRAINT financial_receipt_shape CHECK (method IN ('cash','eft') AND domain IN ('fees','wallet') AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$')");
        // Index existing immutable receipts; never repost or edit their money.
        DB::statement("INSERT INTO financial_receipts(school_id,method,source_reference,domain,record_id,ledger_entry_id,amount_cents,currency) SELECT school_id,method,source_reference,'fees',id,ledger_entry_id,amount_cents,currency FROM payments");
        DB::unprepared("CREATE TRIGGER wallet_funding_insert BEFORE INSERT ON wallet_fundings FOR EACH ROW BEGIN
   DECLARE debit_cents DECIMAL(65,0); DECLARE credit_cents DECIMAL(65,0); DECLARE posting_count INT;
   SELECT COALESCE(SUM(CASE WHEN p.direction='debit' AND a.module='wallet' AND p.account_id=NEW.asset_account_id AND a.type=IF(NEW.method='cash','cash_desk','wallet_bank') AND a.owner_user_id IS NULL AND a.reference=CONCAT('wallet:',NEW.method,':',NEW.currency) THEN p.cents ELSE 0 END),0),COALESCE(SUM(CASE WHEN p.direction='credit' AND a.module='wallet' AND p.account_id=NEW.master_account_id AND a.type='wallet' AND a.owner_user_id IS NULL AND a.reference=CONCAT('wallet:master:',NEW.currency) THEN p.cents ELSE 0 END),0),COUNT(*) INTO debit_cents,credit_cents,posting_count
   FROM ledger_postings p JOIN ledger_accounts a ON a.school_id=p.school_id AND a.id=p.account_id JOIN ledger_entries e ON e.school_id=p.school_id AND e.id=p.entry_id WHERE p.school_id=NEW.school_id AND p.entry_id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency=NEW.currency;
   IF debit_cents<>NEW.amount_cents OR credit_cents<>NEW.amount_cents OR posting_count<>2 OR NOT EXISTS(SELECT 1 FROM wallet_settings WHERE school_id=NEW.school_id AND currency=NEW.currency) OR NOT EXISTS(SELECT 1 FROM wallet_funding_policies WHERE school_id=NEW.school_id AND revision=NEW.policy_revision AND IF(NEW.method='cash',cash_allowed,eft_allowed)=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet funding retains exact verified asset, master and policy postings'; END IF;
  END");
        DB::unprepared("CREATE TRIGGER financial_receipt_insert BEFORE INSERT ON financial_receipts FOR EACH ROW BEGIN
   IF (NEW.domain='fees' AND NOT EXISTS(SELECT 1 FROM payments WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency)) OR (NEW.domain='wallet' AND NOT EXISTS(SELECT 1 FROM wallet_fundings WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='External receipt identity retains its original immutable domain receipt'; END IF;
  END");
        foreach (['payments' => 'fees', 'wallet_fundings' => 'wallet'] as $table => $domain) {
            DB::unprepared("CREATE TRIGGER {$table}_external_identity AFTER INSERT ON $table FOR EACH ROW INSERT INTO financial_receipts(school_id,method,source_reference,domain,record_id,ledger_entry_id,amount_cents,currency) VALUES(NEW.school_id,NEW.method,NEW.source_reference,'$domain',NEW.id,NEW.ledger_entry_id,NEW.amount_cents,NEW.currency)");
        }
        DB::unprepared("CREATE TRIGGER wallet_funding_policy_update BEFORE UPDATE ON wallet_funding_policies FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet funding policies retain school and ordered revisions'; END IF; END");
        foreach (['wallet_fundings' => ['UPDATE', 'DELETE'], 'financial_receipts' => ['UPDATE', 'DELETE'], 'wallet_funding_policies' => ['DELETE']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Financial receipt and wallet funding history is immutable'");
            }
        }
        $this->currencyGuard(true);
    }

    private function currencyGuard(bool $funding): void
    {
        DB::unprepared('DROP TRIGGER wallet_settings_update');
        $extra = $funding ? ' OR EXISTS(SELECT 1 FROM wallet_fundings WHERE school_id=OLD.school_id)' : '';
        DB::unprepared("CREATE TRIGGER wallet_settings_update BEFORE UPDATE ON wallet_settings FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 OR (NEW.currency<>OLD.currency AND (EXISTS(SELECT 1 FROM wallet_accounts WHERE school_id=OLD.school_id)$extra)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet settings retain school, revisions and issued currency'; END IF; END");
    }

    private function ledgerTypes(bool $bank): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE ledger_accounts DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' ledger_account_rules');
        $extra = $bank ? ",'wallet_bank'" : '';
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk','fee_income','fee_bank','fee_vat','fee_refund'$extra) AND currency REGEXP '^[A-Z]{3}$' AND normal_direction=CASE WHEN type IN ('fee_receivable','cash_desk','clearing','fee_bank'$extra) THEN 'debit' ELSE 'credit' END AND ((type IN ('wallet'$extra) AND module='wallet') OR (type='vendor' AND module='vendors') OR (type IN ('fee_receivable','family','fee_income','fee_bank','fee_vat','fee_refund') AND module='fees') OR (type IN ('cash_desk','clearing','due_to_school') AND module IN ('fees','wallet','vendors','ticketing'))) AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000 AND (type NOT IN ('wallet','fee_refund') OR balance_cents>=0) AND (type NOT IN ('clearing','due_to_school') OR balance_cents=0))");
    }

    public function down(): void
    {
        foreach (['wallet_fundings', 'wallet_funding_policies', 'financial_receipts'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain wallet funding and financial receipt identity history.');
            }
        }
        if (DB::table('ledger_accounts')->where('type', 'wallet_bank')->exists()) {
            throw new RuntimeException('Retain wallet bank accounts.');
        }
        DB::unprepared('DROP TRIGGER payments_external_identity');
        $this->currencyGuard(false);
        foreach (['financial_receipts', 'wallet_fundings', 'wallet_funding_policies'] as $table) {
            Schema::dropIfExists($table);
        }
        $this->ledgerTypes(false);
    }
};

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
        Schema::create('fee_tax_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('enabled')->default(false);
            $t->char('country_code', 2)->nullable();
            $t->string('tax_registration', 40)->nullable();
            $t->string('seller_name', 160)->nullable();
            $t->text('seller_address')->nullable();
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        DB::statement("ALTER TABLE fee_tax_settings ADD CONSTRAINT fee_tax_settings_rules CHECK (revision>0 AND (enabled=0 OR (country_code IS NOT NULL AND country_code REGEXP '^[A-Z]{2}$' AND tax_registration IS NOT NULL AND CHAR_LENGTH(TRIM(tax_registration))>0 AND seller_name IS NOT NULL AND CHAR_LENGTH(TRIM(seller_name))>0 AND seller_address IS NOT NULL AND CHAR_LENGTH(TRIM(seller_address))>0)))");
        Schema::table('fee_items', fn (Blueprint $t) => $t->unsignedSmallInteger('vat_rate_bp')->default(0));
        Schema::table('fee_charges', function (Blueprint $t): void {
            $t->bigInteger('vat_cents')->default(0);
            $t->unsignedBigInteger('income_account_id')->nullable();
            $t->unsignedBigInteger('vat_account_id')->nullable();
            foreach (['income_account_id', 'vat_account_id'] as $col) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
            }
        });
        Schema::table('fee_credit_notes', function (Blueprint $t): void {
            $t->bigInteger('vat_cents')->default(0);
            $t->unsignedBigInteger('vat_account_id')->nullable();
            $t->foreign(['school_id', 'vat_account_id'])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE fee_charges ADD net_cents BIGINT GENERATED ALWAYS AS (amount_cents-vat_cents) STORED, ADD CONSTRAINT fee_charge_vat_bounds CHECK (vat_cents BETWEEN 0 AND amount_cents AND (vat_cents=0 OR (income_account_id IS NOT NULL AND vat_account_id IS NOT NULL)))');
        DB::statement('ALTER TABLE fee_credit_notes ADD CONSTRAINT fee_credit_vat_bounds CHECK (vat_cents BETWEEN 0 AND amount_cents AND (vat_cents=0 OR vat_account_id IS NOT NULL))');
        $this->rules(true);
        $this->lineGuard(true);
        $this->creditGuard(true);
        DB::unprepared("CREATE TRIGGER fee_charge_tax_finalize BEFORE UPDATE ON fee_charges FOR EACH ROW BEGIN
            DECLARE invoice_entry BIGINT;DECLARE line_vat DECIMAL(65,0);DECLARE income_credit DECIMAL(65,0);DECLARE vat_credit DECIMAL(65,0);DECLARE postings INT;DECLARE income_type VARCHAR(24);DECLARE vat_type VARCHAR(24);DECLARE run_tax VARCHAR(24);
            IF NOT(NEW.vat_cents <=> OLD.vat_cents) OR NOT(NEW.income_account_id <=> OLD.income_account_id) OR NOT(NEW.vat_account_id <=> OLD.vat_account_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Original fee tax metadata is immutable';END IF;
            IF NEW.sealed_at IS NOT NULL THEN
                SELECT i.ledger_entry_id,b.tax_treatment INTO invoice_entry,run_tax FROM invoices i JOIN bill_runs b ON b.school_id=i.school_id AND b.id=i.bill_run_id WHERE i.school_id=NEW.school_id AND i.bill_run_id=NEW.bill_run_id AND i.fee_account_id=NEW.fee_account_id;
                SELECT COALESCE(SUM(vat_cents),0) INTO line_vat FROM invoice_lines WHERE school_id=NEW.school_id AND fee_charge_id=NEW.id;
                SELECT type INTO income_type FROM ledger_accounts WHERE school_id=NEW.school_id AND id=NEW.income_account_id AND module='fees' AND currency=NEW.currency;
                SELECT type INTO vat_type FROM ledger_accounts WHERE school_id=NEW.school_id AND id=NEW.vat_account_id AND module='fees' AND currency=NEW.currency;
                SELECT COALESCE(SUM(CASE WHEN direction='credit' AND account_id=NEW.income_account_id THEN cents ELSE 0 END),0),COALESCE(SUM(CASE WHEN direction='credit' AND account_id=NEW.vat_account_id THEN cents ELSE 0 END),0),COUNT(*) INTO income_credit,vat_credit,postings FROM ledger_postings WHERE school_id=NEW.school_id AND entry_id=invoice_entry;
                IF income_type IS NULL OR income_type<>'fee_income' OR (NEW.vat_cents>0 AND (vat_type IS NULL OR vat_type<>'fee_vat')) OR (NEW.vat_cents=0 AND NEW.vat_account_id IS NOT NULL) OR line_vat<>NEW.vat_cents OR NEW.vat_cents<>COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.vat_cents')) AS SIGNED),-1) OR NEW.amount_cents-NEW.vat_cents<>COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.net_cents')) AS SIGNED),-1) OR NOT(run_tax <=> JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.tax_treatment'))) OR income_credit<>NEW.amount_cents-NEW.vat_cents OR vat_credit<>NEW.vat_cents OR postings<>1+IF(NEW.amount_cents>NEW.vat_cents,1,0)+IF(NEW.vat_cents>0,1,0) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee tax, net income, reviewed lines and original entry must agree';END IF;
            END IF;
        END");
    }

    public function down(): void
    {
        if (DB::table('fee_tax_settings')->exists() || DB::table('fee_items')->where('tax_treatment', '<>', 'no_vat')->orWhere('vat_rate_bp', '<>', 0)->exists() || DB::table('fee_charges')->where('vat_cents', '<>', 0)->exists() || DB::table('fee_credit_notes')->where('vat_cents', '<>', 0)->exists() || DB::table('ledger_accounts')->where('type', 'fee_vat')->exists()) {
            throw new RuntimeException('Retain VAT settings and original financial tax history.');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS fee_charge_tax_finalize');
        $this->lineGuard(false);
        $this->creditGuard(false);
        $this->rules(false);
        $this->dropCheck('fee_charges', 'fee_charge_vat_bounds');
        $this->dropCheck('fee_credit_notes', 'fee_credit_vat_bounds');
        Schema::table('fee_charges', function (Blueprint $t): void {
            foreach (['income_account_id', 'vat_account_id'] as $col) {
                $t->dropForeign(['school_id', $col]);
            }$t->dropColumn(['net_cents', 'vat_cents', 'income_account_id', 'vat_account_id']);
        });
        Schema::table('fee_credit_notes', function (Blueprint $t): void {
            $t->dropForeign(['school_id', 'vat_account_id']);
            $t->dropColumn(['vat_cents', 'vat_account_id']);
        });
        Schema::table('fee_items', fn (Blueprint $t) => $t->dropColumn('vat_rate_bp'));
        Schema::dropIfExists('fee_tax_settings');
    }

    private function rules(bool $vat): void
    {
        foreach (['ledger_accounts' => 'ledger_account_rules', 'fee_items' => 'fee_item_rules', 'invoice_lines' => 'invoice_line_rules', 'bill_runs' => 'bill_run_rules'] as $table => $constraint) {
            $this->dropCheck($table, $constraint);
        }
        $extra = $vat ? ",'fee_vat'" : '';
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk','fee_income','fee_bank'$extra) AND currency REGEXP '^[A-Z]{3}$' AND normal_direction=CASE WHEN type IN ('fee_receivable','cash_desk','clearing','fee_bank') THEN 'debit' ELSE 'credit' END AND ((type='wallet' AND module='wallet') OR (type='vendor' AND module='vendors') OR (type IN ('fee_receivable','family','fee_income','fee_bank'$extra) AND module='fees') OR (type IN ('cash_desk','clearing','due_to_school') AND module IN ('fees','wallet','vendors','ticketing'))) AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000 AND (type<>'wallet' OR balance_cents>=0) AND (type NOT IN ('clearing','due_to_school') OR balance_cents=0))");
        $taxRule = $vat ? "tax_treatment IN ('no_vat','vat_exempt','vat_zero','vat_inclusive','vat_exclusive') AND ((tax_treatment IN ('no_vat','vat_exempt','vat_zero') AND vat_rate_bp=0) OR (tax_treatment IN ('vat_inclusive','vat_exclusive') AND vat_rate_bp BETWEEN 1 AND 10000))" : "tax_treatment='no_vat'";
        DB::statement("ALTER TABLE fee_items ADD CONSTRAINT fee_item_rules CHECK (scope IN ('learner','family') AND state IN ('draft','active','archived') AND $taxRule AND currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND revision>0)");
        $lineRule = $vat ? 'amount_cents>0 AND vat_cents BETWEEN 0 AND amount_cents AND period_start<=period_end AND (fee_charge_id IS NOT NULL OR (plan_revision>0 AND vat_cents=0))' : 'amount_cents>0 AND vat_cents=0 AND plan_revision>0 AND period_start<=period_end';
        DB::statement('ALTER TABLE invoice_lines ADD CONSTRAINT invoice_line_rules CHECK ('.$lineRule.')');
        $runRule = $vat ? "((kind='annual' AND tax_treatment='no_vat') OR (kind='fee_item' AND tax_treatment IN ('no_vat','vat_exempt','vat_zero','vat_inclusive','vat_exclusive')))" : "tax_treatment='no_vat'";
        DB::statement("ALTER TABLE bill_runs ADD CONSTRAINT bill_run_rules CHECK (state IN ('preview','approved','posted') AND revision>0 AND $runRule)");
    }

    private function lineGuard(bool $vat): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS invoice_line_insert');
        $taxMatch = $vat ? " AND COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(line,'$.vat_cents')) AS SIGNED),0)=NEW.vat_cents " : '';
        DB::unprepared("CREATE TRIGGER invoice_line_insert BEFORE INSERT ON invoice_lines FOR EACH ROW BEGIN
            DECLARE seal DATETIME(6);DECLARE invoice_currency CHAR(3);DECLARE run_kind VARCHAR(16);DECLARE invoice_run BIGINT;DECLARE invoice_account BIGINT;DECLARE charge_run BIGINT;DECLARE charge_account BIGINT;DECLARE charge_year BIGINT;DECLARE charge_snapshot JSON;DECLARE idx INT DEFAULT 0;DECLARE line JSON;DECLARE matched INT DEFAULT 0;DECLARE charge_seal DATETIME(6);
            SELECT sealed_at,currency INTO seal,invoice_currency FROM invoices WHERE school_id=NEW.school_id AND id=NEW.invoice_id;
            IF seal IS NOT NULL OR invoice_currency IS NULL OR invoice_currency<>NEW.currency THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Sealed invoice cannot accept lines';END IF;
            SELECT i.bill_run_id,i.fee_account_id,b.kind INTO invoice_run,invoice_account,run_kind FROM invoices i JOIN bill_runs b ON b.school_id=i.school_id AND b.id=i.bill_run_id WHERE i.school_id=NEW.school_id AND i.id=NEW.invoice_id;
            IF (run_kind='annual' AND NEW.fee_charge_id IS NOT NULL) OR (run_kind='fee_item' AND NEW.fee_charge_id IS NULL) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invoice source kind mismatch';END IF;
            IF NEW.fee_charge_id IS NOT NULL THEN
                SELECT bill_run_id,fee_account_id,session_year_id,snapshot,sealed_at INTO charge_run,charge_account,charge_year,charge_snapshot,charge_seal FROM fee_charges WHERE school_id=NEW.school_id AND id=NEW.fee_charge_id;
                WHILE idx<COALESCE(JSON_LENGTH(JSON_EXTRACT(charge_snapshot,'$.lines')),0) DO
                    SET line=JSON_EXTRACT(charge_snapshot,CONCAT('$.lines[',idx,']'));
                    IF CAST(JSON_UNQUOTE(JSON_EXTRACT(line,'$.learner_id')) AS UNSIGNED)=NEW.learner_id AND CAST(JSON_UNQUOTE(JSON_EXTRACT(line,'$.amount_cents')) AS SIGNED)=NEW.amount_cents $taxMatch AND JSON_LENGTH(NEW.holder_shares)=JSON_LENGTH(JSON_EXTRACT(line,'$.holder_shares')) AND JSON_CONTAINS(NEW.holder_shares,JSON_EXTRACT(line,'$.holder_shares')) AND JSON_CONTAINS(JSON_EXTRACT(line,'$.holder_shares'),NEW.holder_shares) THEN SET matched=matched+1;END IF;SET idx=idx+1;
                END WHILE;
                IF charge_run IS NULL OR charge_seal IS NOT NULL OR charge_run<>invoice_run OR charge_account<>invoice_account OR charge_year<>NEW.session_year_id OR matched<>1 OR NEW.description<>JSON_UNQUOTE(JSON_EXTRACT(charge_snapshot,'$.item_name')) OR NEW.period_start<>JSON_UNQUOTE(JSON_EXTRACT(charge_snapshot,'$.charged_on')) OR NEW.period_end<>JSON_UNQUOTE(JSON_EXTRACT(charge_snapshot,'$.charged_on')) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item line must match its reviewed charge';END IF;
            END IF;
        END");
    }

    private function creditGuard(bool $vat): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS credit_note_finalize');
        $columns = ['id', 'school_id', 'fee_account_id', 'invoice_line_id', 'holder_user_id', 'currency', 'amount_cents', 'unpaid_cents', 'released_cents', 'reason_code', 'reason', 'fingerprint', 'snapshot', 'income_account_id', 'receivable_account_id', 'credit_account_id', 'ledger_entry_id', 'created_by', 'issued_on', 'created_at'];
        if ($vat) {
            $columns[] = 'vat_cents';
            $columns[] = 'vat_account_id';
        }
        $identity = implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", $columns));
        $sql = $vat ? <<<'VATSQL'
CREATE TRIGGER credit_note_finalize BEFORE UPDATE ON fee_credit_notes FOR EACH ROW BEGIN
            DECLARE shares JSON; DECLARE share_cents BIGINT DEFAULT 0; DECLARE idx INT DEFAULT 0; DECLARE line_account BIGINT; DECLARE line_currency CHAR(3); DECLARE invoice_seal DATETIME(6); DECLARE run_state VARCHAR(12); DECLARE receivable BIGINT;
            DECLARE prior_credits DECIMAL(65,0); DECLARE paid DECIMAL(65,0); DECLARE prior_released DECIMAL(65,0); DECLARE released DECIMAL(65,0); DECLARE unpaid DECIMAL(65,0); DECLARE debits DECIMAL(65,0); DECLARE receivable_credit DECIMAL(65,0); DECLARE held_credit DECIMAL(65,0); DECLARE posting_count INT;
            DECLARE share_vat BIGINT DEFAULT 0;DECLARE original_income BIGINT;DECLARE original_vat BIGINT;DECLARE prior_vat DECIMAL(65,0);DECLARE expected_vat BIGINT;DECLARE vat_debits DECIMAL(65,0);
            IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR {{IDENTITY}} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credit history is immutable'; END IF;
            SELECT l.holder_shares, i.fee_account_id, l.currency, i.sealed_at, b.state, f.ledger_account_id INTO shares, line_account, line_currency, invoice_seal, run_state, receivable
                FROM invoice_lines l JOIN invoices i ON i.school_id = l.school_id AND i.id = l.invoice_id JOIN bill_runs b ON b.school_id = i.school_id AND b.id = i.bill_run_id JOIN fee_accounts f ON f.school_id = i.school_id AND f.id = i.fee_account_id WHERE l.school_id = NEW.school_id AND l.id = NEW.invoice_line_id;
            WHILE idx < COALESCE(JSON_LENGTH(shares),0) DO
                IF CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].user_id'))) AS UNSIGNED) = NEW.holder_user_id THEN SET share_cents = CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].amount_cents'))) AS SIGNED);SET share_vat=COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(shares,CONCAT('$[',idx,'].vat_cents'))) AS SIGNED),0); END IF; SET idx = idx + 1;
            END WHILE;
            SELECT COALESCE(SUM(amount_cents),0) INTO prior_credits FROM fee_credit_notes WHERE school_id = NEW.school_id AND invoice_line_id = NEW.invoice_line_id AND holder_user_id = NEW.holder_user_id AND sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(a.amount_cents),0) INTO paid FROM payment_allocations a JOIN payments p ON p.school_id = a.school_id AND p.id = a.payment_id JOIN payment_allocation_runs r ON r.school_id = a.school_id AND r.id = a.allocation_run_id WHERE a.school_id = NEW.school_id AND a.invoice_line_id = NEW.invoice_line_id AND p.holder_user_id = NEW.holder_user_id AND r.sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(c.amount_cents),0) INTO prior_released FROM fee_credit_releases c JOIN fee_credit_notes n ON n.school_id = c.school_id AND n.id = c.credit_note_id WHERE c.school_id = NEW.school_id AND n.invoice_line_id = NEW.invoice_line_id AND n.holder_user_id = NEW.holder_user_id AND n.sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(amount_cents),0) INTO released FROM fee_credit_releases WHERE school_id = NEW.school_id AND credit_note_id = NEW.id;
            SELECT c.income_account_id,c.vat_account_id INTO original_income,original_vat FROM invoice_lines l LEFT JOIN fee_charges c ON c.school_id=l.school_id AND c.id=l.fee_charge_id WHERE l.school_id=NEW.school_id AND l.id=NEW.invoice_line_id;
            SELECT COALESCE(SUM(vat_cents),0) INTO prior_vat FROM fee_credit_notes WHERE school_id=NEW.school_id AND invoice_line_id=NEW.invoice_line_id AND holder_user_id=NEW.holder_user_id AND sealed_at IS NOT NULL;
            IF share_cents<=0 OR share_vat<0 OR share_vat>share_cents THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Original holder tax needs reconciliation';END IF;
            SET expected_vat=((CAST(share_vat AS DECIMAL(65,0))*(prior_credits+NEW.amount_cents)+(share_cents DIV 2)) DIV share_cents)-prior_vat;
            SET unpaid = share_cents - prior_credits - paid + prior_released;
            SELECT COALESCE(SUM(CASE WHEN p.direction = 'debit' AND p.account_id = NEW.income_account_id AND a.type = 'fee_income' AND a.module = 'fees' THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = NEW.receivable_account_id THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = NEW.credit_account_id AND a.type = 'family' AND a.module = 'fees' THEN p.cents ELSE 0 END),0), COUNT(*),COALESCE(SUM(CASE WHEN p.direction='debit' AND p.account_id=NEW.vat_account_id AND a.type='fee_vat' AND a.module='fees' THEN p.cents ELSE 0 END),0) INTO debits, receivable_credit, held_credit, posting_count,vat_debits
                FROM ledger_postings p JOIN ledger_entries e ON e.school_id = p.school_id AND e.id = p.entry_id JOIN ledger_accounts a ON a.school_id = p.school_id AND a.id = p.account_id
                WHERE p.school_id = NEW.school_id AND p.entry_id = NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency = NEW.currency;
            IF line_account IS NULL OR line_account <> NEW.fee_account_id OR line_currency <> NEW.currency OR invoice_seal IS NULL OR run_state <> 'posted' OR receivable <> NEW.receivable_account_id OR unpaid < 0 OR NEW.amount_cents + prior_credits > share_cents OR NEW.unpaid_cents <> LEAST(NEW.amount_cents, unpaid) OR released <> NEW.released_cents OR debits <> NEW.amount_cents-NEW.vat_cents OR NEW.vat_cents<>expected_vat OR vat_debits<>NEW.vat_cents OR NOT (NEW.vat_account_id <=> original_vat) OR (original_income IS NOT NULL AND NEW.income_account_id<>original_income) OR receivable_credit <> NEW.unpaid_cents OR held_credit <> NEW.released_cents OR posting_count <> IF(NEW.amount_cents>NEW.vat_cents,1,0)+IF(NEW.vat_cents>0,1,0)+IF(NEW.unpaid_cents>0,1,0)+IF(NEW.released_cents>0,1,0) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credit, original share, releases and posted entry must agree'; END IF; END
VATSQL
        : <<<'OLDPSQL'
CREATE TRIGGER credit_note_finalize BEFORE UPDATE ON fee_credit_notes FOR EACH ROW BEGIN
            DECLARE shares JSON; DECLARE share_cents BIGINT DEFAULT 0; DECLARE idx INT DEFAULT 0; DECLARE line_account BIGINT; DECLARE line_currency CHAR(3); DECLARE invoice_seal DATETIME(6); DECLARE run_state VARCHAR(12); DECLARE receivable BIGINT;
            DECLARE prior_credits DECIMAL(65,0); DECLARE paid DECIMAL(65,0); DECLARE prior_released DECIMAL(65,0); DECLARE released DECIMAL(65,0); DECLARE unpaid DECIMAL(65,0); DECLARE debits DECIMAL(65,0); DECLARE receivable_credit DECIMAL(65,0); DECLARE held_credit DECIMAL(65,0); DECLARE posting_count INT;
            IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR {{IDENTITY}} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credit history is immutable'; END IF;
            SELECT l.holder_shares, i.fee_account_id, l.currency, i.sealed_at, b.state, f.ledger_account_id INTO shares, line_account, line_currency, invoice_seal, run_state, receivable
                FROM invoice_lines l JOIN invoices i ON i.school_id = l.school_id AND i.id = l.invoice_id JOIN bill_runs b ON b.school_id = i.school_id AND b.id = i.bill_run_id JOIN fee_accounts f ON f.school_id = i.school_id AND f.id = i.fee_account_id WHERE l.school_id = NEW.school_id AND l.id = NEW.invoice_line_id;
            WHILE idx < COALESCE(JSON_LENGTH(shares),0) DO
                IF CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].user_id'))) AS UNSIGNED) = NEW.holder_user_id THEN SET share_cents = CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].amount_cents'))) AS SIGNED); END IF; SET idx = idx + 1;
            END WHILE;
            SELECT COALESCE(SUM(amount_cents),0) INTO prior_credits FROM fee_credit_notes WHERE school_id = NEW.school_id AND invoice_line_id = NEW.invoice_line_id AND holder_user_id = NEW.holder_user_id AND sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(a.amount_cents),0) INTO paid FROM payment_allocations a JOIN payments p ON p.school_id = a.school_id AND p.id = a.payment_id JOIN payment_allocation_runs r ON r.school_id = a.school_id AND r.id = a.allocation_run_id WHERE a.school_id = NEW.school_id AND a.invoice_line_id = NEW.invoice_line_id AND p.holder_user_id = NEW.holder_user_id AND r.sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(c.amount_cents),0) INTO prior_released FROM fee_credit_releases c JOIN fee_credit_notes n ON n.school_id = c.school_id AND n.id = c.credit_note_id WHERE c.school_id = NEW.school_id AND n.invoice_line_id = NEW.invoice_line_id AND n.holder_user_id = NEW.holder_user_id AND n.sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(amount_cents),0) INTO released FROM fee_credit_releases WHERE school_id = NEW.school_id AND credit_note_id = NEW.id;
            SET unpaid = share_cents - prior_credits - paid + prior_released;
            SELECT COALESCE(SUM(CASE WHEN p.direction = 'debit' AND p.account_id = NEW.income_account_id AND a.type = 'fee_income' AND a.module = 'fees' THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = NEW.receivable_account_id THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = NEW.credit_account_id AND a.type = 'family' AND a.module = 'fees' THEN p.cents ELSE 0 END),0), COUNT(*) INTO debits, receivable_credit, held_credit, posting_count
                FROM ledger_postings p JOIN ledger_entries e ON e.school_id = p.school_id AND e.id = p.entry_id JOIN ledger_accounts a ON a.school_id = p.school_id AND a.id = p.account_id
                WHERE p.school_id = NEW.school_id AND p.entry_id = NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency = NEW.currency;
            IF line_account IS NULL OR line_account <> NEW.fee_account_id OR line_currency <> NEW.currency OR invoice_seal IS NULL OR run_state <> 'posted' OR receivable <> NEW.receivable_account_id OR unpaid < 0 OR NEW.amount_cents + prior_credits > share_cents OR NEW.unpaid_cents <> LEAST(NEW.amount_cents, unpaid) OR released <> NEW.released_cents OR debits <> NEW.amount_cents OR receivable_credit <> NEW.unpaid_cents OR held_credit <> NEW.released_cents OR posting_count <> IF(NEW.unpaid_cents > 0 AND NEW.released_cents > 0,3,2) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credit, original share, releases and posted entry must agree'; END IF; END
OLDPSQL;
        DB::unprepared(str_replace('{{IDENTITY}}', $identity, $sql));
    }

    private function dropCheck(string $table, string $constraint): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE '.$table.' DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' '.$constraint);
    }
};

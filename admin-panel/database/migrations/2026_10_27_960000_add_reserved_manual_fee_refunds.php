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
        Schema::create('fee_refund_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->boolean('cash_allowed');
            $t->boolean('eft_allowed');
            $t->boolean('independent_approval');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique('school_id');
            $t->unique(['school_id', 'id']);
        });
        DB::statement('ALTER TABLE fee_refund_policies ADD CONSTRAINT fee_refund_policy_shape CHECK (revision>0 AND cash_allowed IN (0,1) AND eft_allowed IN (0,1) AND cash_allowed+eft_allowed>=1 AND independent_approval IN (0,1))');
        DB::unprepared("CREATE TRIGGER fee_refund_policy_update BEFORE UPDATE ON fee_refund_policies FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Refund policy identity and revisions are retained'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_refund_policy_no_delete BEFORE DELETE ON fee_refund_policies FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain school refund policy history'");
        Schema::create('fee_refunds', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            foreach (['payment_id', 'fee_account_id', 'holder_user_id', 'fee_refund_policy_id'] as $col) {
                $t->unsignedBigInteger($col);
            }$t->unsignedBigInteger('pinned_learner_id')->nullable();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->string('method', 4)->collation('utf8mb4_bin');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->json('snapshot');
            $t->text('private_explanation');
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->index(['school_id', 'payment_id']);
            foreach (['payment_id' => 'payments', 'fee_account_id' => 'fee_accounts', 'fee_refund_policy_id' => 'fee_refund_policies', 'pinned_learner_id' => 'students'] as $col => $parent) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
            $t->foreign('holder_user_id')->references('id')->on('users')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE fee_refunds ADD CONSTRAINT fee_refund_shape CHECK (amount_cents BETWEEN 1 AND 100000000000 AND method IN ('cash','eft') AND currency REGEXP '^[A-Z]{3}$' AND JSON_TYPE(snapshot)='OBJECT' AND fingerprint REGEXP '^[a-f0-9]{64}$')");
        DB::unprepared("CREATE TRIGGER fee_refund_insert BEFORE INSERT ON fee_refunds FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM payments WHERE school_id=NEW.school_id AND id=NEW.payment_id AND fee_account_id=NEW.fee_account_id AND holder_user_id=NEW.holder_user_id AND currency=NEW.currency AND (pinned_learner_id <=> NEW.pinned_learner_id)) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.payment_id')) AS UNSIGNED) <=> NEW.payment_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.amount_cents')) AS SIGNED) <=> NEW.amount_cents) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.fee_account_id')) AS UNSIGNED) <=> NEW.fee_account_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.holder_user_id')) AS UNSIGNED) <=> NEW.holder_user_id) OR NOT(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.currency')) <=> NEW.currency) OR NOT(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.method')) <=> NEW.method) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Refund review retains the original receipt, holder, pin and currency'; END IF; END");
        foreach (['UPDATE', 'DELETE'] as $action) {
            DB::unprepared('CREATE TRIGGER fee_refund_no_'.strtolower($action)." BEFORE $action ON fee_refunds FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Refund request history is immutable'");
        }
        Schema::create('fee_refund_actions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_refund_id');
            $t->string('action', 12)->collation('utf8mb4_bin');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->bigInteger('amount_cents');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('ledger_entry_id');
            $t->unsignedBigInteger('pending_account_id');
            $t->unsignedBigInteger('counter_account_id');
            $t->char('reference_hash', 64)->nullable()->collation('utf8mb4_bin');
            $t->text('private_evidence')->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'fee_refund_id', 'action']);
            $t->unique(['school_id', 'reference_hash']);
            $t->unique(['school_id', 'ledger_entry_id']);
            foreach (['fee_refund_id' => 'fee_refunds', 'ledger_entry_id' => 'ledger_entries', 'pending_account_id' => 'ledger_accounts', 'counter_account_id' => 'ledger_accounts'] as $col => $parent) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE fee_refund_actions ADD CONSTRAINT fee_refund_action_shape CHECK (action IN ('reserved','paid','cancelled') AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$' AND pending_account_id<>counter_account_id AND ((action='paid' AND reference_hash IS NOT NULL AND private_evidence IS NOT NULL) OR (action<>'paid' AND reference_hash IS NULL)))");
        DB::unprepared("CREATE TRIGGER fee_refund_action_insert BEFORE INSERT ON fee_refund_actions FOR EACH ROW BEGIN
   DECLARE refund_payment BIGINT; DECLARE family_account BIGINT; DECLARE request_amount BIGINT; DECLARE request_currency CHAR(3); DECLARE request_method VARCHAR(4); DECLARE preparer BIGINT; DECLARE requires_other BOOLEAN; DECLARE reserved_count BIGINT; DECLARE terminal_count BIGINT; DECLARE original_pending BIGINT; DECLARE available DECIMAL(65,0); DECLARE allocated DECIMAL(65,0); DECLARE released DECIMAL(65,0); DECLARE held DECIMAL(65,0); DECLARE posting_count BIGINT; DECLARE pending_sum DECIMAL(65,0); DECLARE counter_sum DECIMAL(65,0); DECLARE counter_type VARCHAR(32);
   SELECT r.payment_id,p.credit_account_id,r.amount_cents,r.currency,r.method,r.prepared_by,JSON_UNQUOTE(JSON_EXTRACT(r.snapshot,'$.policy.independent_approval'))='true' INTO refund_payment,family_account,request_amount,request_currency,request_method,preparer,requires_other FROM fee_refunds r JOIN payments p ON p.school_id=r.school_id AND p.id=r.payment_id WHERE r.school_id=NEW.school_id AND r.id=NEW.fee_refund_id;
   SELECT COUNT(*),MAX(pending_account_id) INTO reserved_count,original_pending FROM fee_refund_actions WHERE school_id=NEW.school_id AND fee_refund_id=NEW.fee_refund_id AND action='reserved';
   SELECT COUNT(*) INTO terminal_count FROM fee_refund_actions WHERE school_id=NEW.school_id AND fee_refund_id=NEW.fee_refund_id AND action IN ('paid','cancelled');
   IF request_amount IS NULL OR NEW.amount_cents<>request_amount OR NEW.currency<>request_currency OR (NEW.action='reserved' AND (reserved_count<>0 OR terminal_count<>0 OR NEW.counter_account_id<>family_account OR (requires_other AND NEW.actor_id=preparer))) OR (NEW.action<>'reserved' AND (reserved_count<>1 OR terminal_count<>0 OR NEW.pending_account_id<>original_pending)) OR (NEW.action='cancelled' AND NEW.counter_account_id<>family_account) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Refund actions retain their original reservation and legal transitions'; END IF;
   IF NOT EXISTS(SELECT 1 FROM ledger_accounts WHERE school_id=NEW.school_id AND id=NEW.pending_account_id AND type='fee_refund' AND module='fees' AND currency=NEW.currency AND reference=CONCAT('fees:refund:',NEW.fee_refund_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Use the unique original refund liability'; END IF;
   SELECT type INTO counter_type FROM ledger_accounts WHERE school_id=NEW.school_id AND id=NEW.counter_account_id AND module='fees' AND currency=NEW.currency;
   IF counter_type IS NULL OR (NEW.action='paid' AND counter_type<>IF(request_method='cash','cash_desk','fee_bank')) OR (NEW.action<>'paid' AND counter_type<>'family') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Use the original family credit or declared payout asset'; END IF;
   SELECT COUNT(*),COALESCE(SUM(CASE WHEN lp.account_id=NEW.pending_account_id AND lp.direction=IF(NEW.action='reserved','credit','debit') THEN lp.cents ELSE 0 END),0),COALESCE(SUM(CASE WHEN lp.account_id=NEW.counter_account_id AND lp.direction=IF(NEW.action='reserved','debit','credit') THEN lp.cents ELSE 0 END),0) INTO posting_count,pending_sum,counter_sum FROM ledger_postings lp JOIN ledger_entries e ON e.school_id=lp.school_id AND e.id=lp.entry_id WHERE lp.school_id=NEW.school_id AND lp.entry_id=NEW.ledger_entry_id AND e.currency=NEW.currency AND e.posted_at IS NOT NULL;
   IF posting_count<>2 OR pending_sum<>NEW.amount_cents OR counter_sum<>NEW.amount_cents THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Refund action requires its exact balanced posted entry'; END IF;
   IF NEW.action='reserved' THEN
    SELECT amount_cents INTO available FROM payments WHERE school_id=NEW.school_id AND id=refund_payment;
    SELECT COALESCE(SUM(amount_cents),0) INTO allocated FROM payment_allocations WHERE school_id=NEW.school_id AND payment_id=refund_payment;
    SELECT COALESCE(SUM(c.amount_cents),0) INTO released FROM fee_credit_releases c JOIN fee_credit_notes n ON n.school_id=c.school_id AND n.id=c.credit_note_id WHERE c.school_id=NEW.school_id AND c.payment_id=refund_payment AND n.sealed_at IS NOT NULL;
    SELECT COALESCE(SUM(r.amount_cents),0) INTO held FROM fee_refunds r JOIN fee_refund_actions a ON a.school_id=r.school_id AND a.fee_refund_id=r.id AND a.action='reserved' WHERE r.school_id=NEW.school_id AND r.payment_id=refund_payment AND NOT EXISTS(SELECT 1 FROM fee_refund_actions x WHERE x.school_id=r.school_id AND x.fee_refund_id=r.id AND x.action='cancelled');
    IF available-allocated+released-held<NEW.amount_cents THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Refund reservation exceeds original unallocated receipt credit'; END IF;
   END IF;
  END");
        foreach (['UPDATE', 'DELETE'] as $action) {
            DB::unprepared('CREATE TRIGGER fee_refund_action_no_'.strtolower($action)." BEFORE $action ON fee_refund_actions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Refund actions are immutable'");
        }
        DB::unprepared("CREATE TRIGGER allocation_refund_budget BEFORE INSERT ON payment_allocations FOR EACH ROW BEGIN
   DECLARE held DECIMAL(65,0); DECLARE paid DECIMAL(65,0); DECLARE used DECIMAL(65,0); DECLARE restored DECIMAL(65,0);
   SELECT COALESCE(SUM(r.amount_cents),0) INTO held FROM fee_refunds r JOIN fee_refund_actions a ON a.school_id=r.school_id AND a.fee_refund_id=r.id AND a.action='reserved' WHERE r.school_id=NEW.school_id AND r.payment_id=NEW.payment_id AND NOT EXISTS(SELECT 1 FROM fee_refund_actions x WHERE x.school_id=r.school_id AND x.fee_refund_id=r.id AND x.action='cancelled');
   SELECT amount_cents INTO paid FROM payments WHERE school_id=NEW.school_id AND id=NEW.payment_id;
   SELECT COALESCE(SUM(amount_cents),0) INTO used FROM payment_allocations WHERE school_id=NEW.school_id AND payment_id=NEW.payment_id;
   SELECT COALESCE(SUM(c.amount_cents),0) INTO restored FROM fee_credit_releases c JOIN fee_credit_notes n ON n.school_id=c.school_id AND n.id=c.credit_note_id WHERE c.school_id=NEW.school_id AND c.payment_id=NEW.payment_id AND n.sealed_at IS NOT NULL;
   IF used+NEW.amount_cents>paid+restored-held THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Allocation cannot spend reserved or paid refund credit'; END IF;
  END");
    }

    private function ledgerTypes(bool $refund): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE ledger_accounts DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' ledger_account_rules');
        $extra = $refund ? ",'fee_refund'" : '';
        $nonnegative = $refund ? "type NOT IN ('wallet','fee_refund') OR balance_cents>=0" : "type<>'wallet' OR balance_cents>=0";
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk','fee_income','fee_bank','fee_vat'$extra) AND currency REGEXP '^[A-Z]{3}$' AND normal_direction=CASE WHEN type IN ('fee_receivable','cash_desk','clearing','fee_bank') THEN 'debit' ELSE 'credit' END AND ((type='wallet' AND module='wallet') OR (type='vendor' AND module='vendors') OR (type IN ('fee_receivable','family','fee_income','fee_bank','fee_vat'$extra) AND module='fees') OR (type IN ('cash_desk','clearing','due_to_school') AND module IN ('fees','wallet','vendors','ticketing'))) AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000 AND ($nonnegative) AND (type NOT IN ('clearing','due_to_school') OR balance_cents=0))");
    }

    public function down(): void
    {
        if (DB::table('fee_refunds')->exists() || DB::table('fee_refund_policies')->exists() || DB::table('fee_refund_actions')->exists() || DB::table('ledger_accounts')->where('type', 'fee_refund')->exists()) {
            throw new RuntimeException('Retain refund reservations, evidence and posted history.');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS allocation_refund_budget');
        Schema::dropIfExists('fee_refund_actions');
        Schema::dropIfExists('fee_refunds');
        Schema::dropIfExists('fee_refund_policies');
        $this->ledgerTypes(false);
    }
};

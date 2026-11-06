<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Independent school business tools (Action Plan 5.10): re-enrolment
 * contracts, bursary applications and alumni giving on the ledger.
 * engineering/specs/money/school-business-tools.md.
 */
return new class extends Migration
{
    private const TABLES = ['giving_donations', 'giving_pledges', 'giving_campaigns', 'bursary_events', 'bursary_documents', 'bursary_applications',
        'reenrolment_confirmations', 'reenrolment_acceptances', 'reenrolment_contracts', 'school_business_operations'];

    public function up(): void
    {
        Schema::create('school_business_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->string('kind', 48)->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            // Identifiers only: never names, household details or contract text.
            $t->json('receipt');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'actor_id', 'operation_id'], 'school_business_operation_once');
        });

        Schema::create('reenrolment_contracts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('session_year_id');
            $t->unsignedInteger('version');
            $t->string('title', 191);
            $t->mediumText('body');
            $t->char('text_hash', 64)->collation('utf8mb4_bin');
            $t->string('acceptance_rule', 24)->collation('utf8mb4_bin');
            $t->date('respond_by');
            $t->string('state', 12)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('published_at', 6);
            $t->dateTime('closed_at', 6)->nullable();
            $t->timestamps();
            $t->unsignedBigInteger('open_year')->nullable()->storedAs("CASE WHEN state='open' THEN session_year_id ELSE NULL END");
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'session_year_id', 'version'], 'reenrolment_contract_version');
            $t->unique(['school_id', 'open_year'], 'reenrolment_contract_one_open');
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE reenrolment_contracts ADD CONSTRAINT reenrolment_contract_shape CHECK (version>0 AND revision>0 AND CHAR_LENGTH(text_hash)=64 AND acceptance_rule IN ('all_fee_payers','fee_payer','any_guardian') AND state IN ('open','superseded','withdrawn') AND (state='open' OR closed_at IS NOT NULL))");
        DB::unprepared("CREATE TRIGGER reenrolment_contract_update BEFORE UPDATE ON reenrolment_contracts FOR EACH ROW BEGIN IF NEW.school_id<>OLD.school_id OR NEW.id<>OLD.id OR NEW.session_year_id<>OLD.session_year_id OR NEW.version<>OLD.version OR NOT(NEW.title <=> OLD.title) OR NOT(NEW.body <=> OLD.body) OR NEW.text_hash<>OLD.text_hash OR NEW.acceptance_rule<>OLD.acceptance_rule OR NEW.published_by<>OLD.published_by OR NEW.published_at<>OLD.published_at OR NEW.revision<>OLD.revision+1 OR OLD.state<>'open' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A published contract keeps its exact wording; publish a new version instead'; END IF; END");

        Schema::create('reenrolment_acceptances', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('learner_id');
            $t->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->char('text_hash', 64)->collation('utf8mb4_bin');
            $t->boolean('fee_responsible');
            $t->string('signed_name', 191);
            $t->string('ip_address', 45);
            $t->dateTime('accepted_at', 6);
            $t->unique(['school_id', 'contract_id', 'learner_id', 'guardian_user_id'], 'reenrolment_acceptance_once');
            $t->foreign(['school_id', 'contract_id'])->references(['school_id', 'id'])->on('reenrolment_contracts')->restrictOnDelete();
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
        });
        DB::unprepared("CREATE TRIGGER reenrolment_acceptance_insert BEFORE INSERT ON reenrolment_acceptances FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM reenrolment_contracts WHERE school_id=NEW.school_id AND id=NEW.contract_id AND state='open' AND version=NEW.version AND text_hash=NEW.text_hash) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='An acceptance names the exact open contract version and wording'; END IF; END");

        Schema::create('reenrolment_confirmations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('contract_id');
            $t->unsignedBigInteger('learner_id');
            $t->unsignedBigInteger('session_year_id');
            $t->dateTime('confirmed_at', 6);
            $t->unique(['school_id', 'contract_id', 'learner_id'], 'reenrolment_confirmed_once');
            $t->index(['school_id', 'session_year_id', 'learner_id'], 'reenrolment_confirmed_year');
            $t->foreign(['school_id', 'contract_id'])->references(['school_id', 'id'])->on('reenrolment_contracts')->restrictOnDelete();
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
        });

        Schema::create('bursary_applications', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('public_id', 36)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('learner_id');
            $t->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('session_year_id');
            $t->string('state', 12)->collation('utf8mb4_bin');
            // Encrypted household details; NULL once the retention rule erased them.
            $t->longText('household')->nullable();
            $t->unsignedInteger('revision');
            $t->dateTime('submitted_at', 6)->nullable();
            $t->dateTime('decided_at', 6)->nullable();
            $t->string('award_kind', 12)->collation('utf8mb4_bin')->nullable();
            $t->bigInteger('award_value')->nullable();
            $t->char('currency', 3)->collation('utf8mb4_bin')->nullable();
            $t->unsignedBigInteger('fee_discount_id')->nullable();
            $t->dateTime('erased_at', 6)->nullable();
            $t->timestamps();
            $t->unsignedBigInteger('active_learner')->nullable()->storedAs("CASE WHEN state IN ('draft','submitted') THEN learner_id ELSE NULL END");
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'public_id']);
            $t->unique(['school_id', 'session_year_id', 'active_learner'], 'bursary_one_open_application');
            $t->unique(['school_id', 'fee_discount_id']);
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
            $t->foreign(['school_id', 'fee_discount_id'])->references(['school_id', 'id'])->on('fee_discounts')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE bursary_applications ADD CONSTRAINT bursary_application_shape CHECK (revision>0 AND state IN ('draft','submitted','awarded','declined','withdrawn') AND (state<>'draft' OR submitted_at IS NULL) AND (state NOT IN ('submitted','awarded','declined') OR submitted_at IS NOT NULL) AND ((state IN ('awarded','declined') AND decided_at IS NOT NULL) OR (state NOT IN ('awarded','declined') AND decided_at IS NULL)) AND ((state='awarded' AND award_kind IN ('fixed','percent') AND award_value>0 AND currency REGEXP '^[A-Z]{3}$' AND ((award_kind='percent' AND award_value<=10000) OR (award_kind='fixed' AND award_value<=100000000000))) OR (state<>'awarded' AND award_kind IS NULL AND award_value IS NULL AND currency IS NULL AND fee_discount_id IS NULL)) AND (erased_at IS NULL OR household IS NULL) AND (erased_at IS NOT NULL OR household IS NOT NULL))");
        DB::unprepared("CREATE TRIGGER bursary_application_update BEFORE UPDATE ON bursary_applications FOR EACH ROW BEGIN IF NEW.school_id<>OLD.school_id OR NEW.id<>OLD.id OR NEW.public_id<>OLD.public_id OR NEW.learner_id<>OLD.learner_id OR NEW.guardian_user_id<>OLD.guardian_user_id OR NEW.session_year_id<>OLD.session_year_id OR (OLD.erased_at IS NOT NULL AND NEW.household IS NOT NULL) OR (OLD.state IN ('awarded','declined') AND (NEW.state<>OLD.state OR NOT(NEW.award_kind <=> OLD.award_kind) OR NOT(NEW.award_value <=> OLD.award_value) OR NOT(NEW.currency <=> OLD.currency) OR (OLD.fee_discount_id IS NOT NULL AND NOT(NEW.fee_discount_id <=> OLD.fee_discount_id)))) OR (OLD.state='withdrawn' AND NEW.state<>'withdrawn') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A bursary decision and its applicant are retained'; END IF; END");
        DB::unprepared("CREATE TRIGGER bursary_application_no_delete BEFORE DELETE ON bursary_applications FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Erase bursary household details instead of deleting the decision record'");

        Schema::create('bursary_documents', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('application_id');
            $t->char('handle', 36)->collation('utf8mb4_bin');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('source_digest', 64)->collation('utf8mb4_bin');
            $t->string('category', 32)->collation('utf8mb4_bin');
            $t->string('path', 255)->collation('utf8mb4_bin');
            $t->string('mime', 64);
            $t->unsignedBigInteger('bytes');
            $t->char('sha256', 64)->collation('utf8mb4_bin');
            $t->string('scanner', 120);
            $t->dateTime('scanned_at', 6);
            $t->timestamps();
            $t->unique(['school_id', 'handle']);
            $t->unique(['school_id', 'application_id', 'operation_id'], 'bursary_document_operation');
            $t->foreign(['school_id', 'application_id'])->references(['school_id', 'id'])->on('bursary_applications')->restrictOnDelete();
        });

        Schema::create('bursary_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('application_id');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('action', 24)->collation('utf8mb4_bin');
            // Encrypted committee reasons; NULL once retention erased them.
            $t->text('reasons')->nullable();
            $t->string('award_kind', 12)->collation('utf8mb4_bin')->nullable();
            $t->bigInteger('award_value')->nullable();
            $t->dateTime('created_at', 6);
            $t->index(['school_id', 'application_id']);
            $t->foreign(['school_id', 'application_id'])->references(['school_id', 'id'])->on('bursary_applications')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE bursary_events ADD CONSTRAINT bursary_event_shape CHECK (action IN ('created','updated','document_added','document_removed','submitted','withdrawn','review_note','awarded','declined','discount_drafted','erased'))");
        DB::unprepared("CREATE TRIGGER bursary_event_update BEFORE UPDATE ON bursary_events FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.application_id<>OLD.application_id OR NEW.actor_id<>OLD.actor_id OR NEW.action<>OLD.action OR NOT(NEW.award_kind <=> OLD.award_kind) OR NOT(NEW.award_value <=> OLD.award_value) OR NEW.created_at<>OLD.created_at OR NEW.reasons IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='The bursary audit trail is append-only; only retention may erase its reasons'; END IF; END");
        DB::unprepared("CREATE TRIGGER bursary_event_no_delete BEFORE DELETE ON bursary_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='The bursary audit trail is retained'");

        $this->ledgerTypes(true);

        Schema::create('giving_campaigns', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('name', 160);
            $t->text('description')->nullable();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('goal_cents')->nullable();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('public_totals');
            $t->string('state', 12)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('income_account_id');
            $t->unsignedInteger('revision');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'income_account_id']);
            $t->foreign(['school_id', 'income_account_id'])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE giving_campaigns ADD CONSTRAINT giving_campaign_shape CHECK (currency REGEXP '^[A-Z]{3}$' AND (goal_cents IS NULL OR goal_cents BETWEEN 1 AND 100000000000) AND starts_on<=ends_on AND state IN ('open','closed') AND revision>0)");
        DB::unprepared("CREATE TRIGGER giving_campaign_update BEFORE UPDATE ON giving_campaigns FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.currency<>OLD.currency OR NEW.income_account_id<>OLD.income_account_id OR NEW.revision<>OLD.revision+1 OR (OLD.state='closed' AND NEW.state<>'closed') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A campaign keeps its currency, ledger account and closure'; END IF; END");
        DB::unprepared("CREATE TRIGGER giving_campaign_no_delete BEFORE DELETE ON giving_campaigns FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign history is retained'");

        Schema::create('giving_pledges', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedInteger('reference_number');
            $t->text('donor_name');
            $t->text('donor_email')->nullable();
            $t->bigInteger('amount_cents');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->date('pledged_on');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'reference_number']);
            $t->foreign(['school_id', 'campaign_id'])->references(['school_id', 'id'])->on('giving_campaigns')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE giving_pledges ADD CONSTRAINT giving_pledge_shape CHECK (reference_number>0 AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$')");
        DB::unprepared("CREATE TRIGGER giving_pledge_insert BEFORE INSERT ON giving_pledges FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM giving_campaigns WHERE school_id=NEW.school_id AND id=NEW.campaign_id AND currency=NEW.currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A pledge uses its campaign currency'; END IF; END");

        Schema::create('giving_donations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('campaign_id');
            $t->unsignedBigInteger('pledge_id')->nullable();
            $t->unsignedInteger('receipt_number');
            $t->text('donor_name');
            $t->text('donor_email')->nullable();
            $t->string('method', 12)->collation('utf8mb4_bin');
            // The gateway seam: online collection is a later, separate provider.
            $t->string('provider', 24)->collation('utf8mb4_bin');
            $t->string('collected_by', 12)->collation('utf8mb4_bin');
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->date('received_on');
            $t->unsignedBigInteger('asset_account_id');
            $t->unsignedBigInteger('income_account_id');
            $t->unsignedBigInteger('ledger_entry_id');
            $t->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'receipt_number']);
            $t->unique(['school_id', 'method', 'source_reference'], 'giving_donation_business_source');
            $t->unique(['school_id', 'ledger_entry_id']);
            $t->foreign(['school_id', 'campaign_id'])->references(['school_id', 'id'])->on('giving_campaigns')->restrictOnDelete();
            $t->foreign(['school_id', 'pledge_id'])->references(['school_id', 'id'])->on('giving_pledges')->restrictOnDelete();
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            foreach (['asset_account_id', 'income_account_id'] as $column) {
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE giving_donations ADD CONSTRAINT giving_donation_shape CHECK (receipt_number>0 AND method='eft' AND provider='manual' AND collected_by='school' AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$' AND source_reference REGEXP '^[A-Z0-9][A-Z0-9 ._:/-]{0,95}$' AND source_reference=UPPER(TRIM(source_reference)) AND LOCATE('  ',source_reference)=0)");
        DB::unprepared("CREATE TRIGGER giving_donation_insert BEFORE INSERT ON giving_donations FOR EACH ROW BEGIN
   DECLARE debit_cents DECIMAL(65,0); DECLARE credit_cents DECIMAL(65,0); DECLARE posting_count INT;
   SELECT COALESCE(SUM(CASE WHEN p.direction='debit' AND p.account_id=NEW.asset_account_id AND a.type='giving_bank' AND a.module='giving' THEN p.cents ELSE 0 END),0),
          COALESCE(SUM(CASE WHEN p.direction='credit' AND p.account_id=NEW.income_account_id AND a.type='giving_income' AND a.module='giving' THEN p.cents ELSE 0 END),0), COUNT(*)
   INTO debit_cents, credit_cents, posting_count
   FROM ledger_postings p JOIN ledger_accounts a ON a.school_id=p.school_id AND a.id=p.account_id JOIN ledger_entries e ON e.school_id=p.school_id AND e.id=p.entry_id
   WHERE p.school_id=NEW.school_id AND p.entry_id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency=NEW.currency;
   IF debit_cents<>NEW.amount_cents OR credit_cents<>NEW.amount_cents OR posting_count<>2
      OR NOT EXISTS(SELECT 1 FROM giving_campaigns WHERE school_id=NEW.school_id AND id=NEW.campaign_id AND income_account_id=NEW.income_account_id AND currency=NEW.currency)
      OR (NEW.pledge_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM giving_pledges WHERE school_id=NEW.school_id AND id=NEW.pledge_id AND campaign_id=NEW.campaign_id)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A donation retains its exact verified bank and campaign postings';
   END IF;
  END");
        foreach (['giving_pledges', 'giving_donations'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pledge and donation receipts are immutable'");
            }
        }
        foreach (['reenrolment_acceptances', 'reenrolment_confirmations'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Re-enrolment acceptances are retained as signed'");
            }
        }
        DB::unprepared("CREATE TRIGGER reenrolment_contract_no_delete BEFORE DELETE ON reenrolment_contracts FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Published contracts are retained'");
        $this->receiptRegistry(true);
        DB::unprepared("CREATE TRIGGER giving_donation_external_identity AFTER INSERT ON giving_donations FOR EACH ROW INSERT INTO financial_receipts(school_id,method,source_reference,domain,record_id,ledger_entry_id,amount_cents,currency) VALUES(NEW.school_id,NEW.method,NEW.source_reference,'giving',NEW.id,NEW.ledger_entry_id,NEW.amount_cents,NEW.currency)");
    }

    /** Giving has its own bank and income accounts in the giving module. */
    private function ledgerTypes(bool $giving): void
    {
        $drop = str_contains(DB::selectOne('SELECT VERSION() AS version')->version, 'MariaDB') ? 'CONSTRAINT' : 'CHECK';
        DB::statement("ALTER TABLE ledger_accounts DROP $drop ledger_account_rules");
        $types = $giving ? ",'giving_bank','giving_income'" : '';
        $debit = $giving ? ",'giving_bank'" : '';
        $module = $giving ? " OR (type IN ('giving_bank','giving_income') AND module='giving')" : '';
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk','fee_income','fee_bank','fee_vat','fee_refund','wallet_bank'$types) AND currency REGEXP '^[A-Z]{3}$' AND normal_direction=CASE WHEN type IN ('fee_receivable','cash_desk','clearing','fee_bank','wallet_bank'$debit) THEN 'debit' ELSE 'credit' END AND ((type IN ('wallet','wallet_bank') AND module='wallet') OR (type='vendor' AND module='vendors') OR (type IN ('fee_receivable','family','fee_income','fee_bank','fee_vat','fee_refund') AND module='fees') OR (type IN ('cash_desk','clearing','due_to_school') AND module IN ('fees','wallet','vendors','ticketing')) OR (type IN ('fee_income','fee_vat') AND module IN ('vendors','ticketing'))$module) AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000 AND (type NOT IN ('wallet','fee_refund') OR balance_cents>=0) AND (type NOT IN ('clearing','due_to_school') OR balance_cents=0))");
    }

    private function receiptRegistry(bool $giving): void
    {
        $drop = str_contains(DB::selectOne('SELECT VERSION() AS version')->version, 'MariaDB') ? 'CONSTRAINT' : 'CHECK';
        DB::unprepared('DROP TRIGGER IF EXISTS financial_receipt_insert');
        DB::statement("ALTER TABLE financial_receipts DROP $drop financial_receipt_shape");
        $domains = $giving ? "'fees','wallet','parent','giving'" : "'fees','wallet','parent'";
        DB::statement("ALTER TABLE financial_receipts ADD CONSTRAINT financial_receipt_shape CHECK (method IN ('cash','eft') AND domain IN ($domains) AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$')");
        $extra = $giving ? " OR (NEW.domain='giving' AND NOT EXISTS(SELECT 1 FROM giving_donations WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency))" : '';
        DB::unprepared("CREATE TRIGGER financial_receipt_insert BEFORE INSERT ON financial_receipts FOR EACH ROW BEGIN IF (NEW.domain='fees' AND NOT EXISTS(SELECT 1 FROM payments WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency)) OR (NEW.domain='wallet' AND NOT EXISTS(SELECT 1 FROM wallet_fundings WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency)) OR (NEW.domain='parent' AND NOT EXISTS(SELECT 1 FROM wallet_parent_deposits WHERE school_id=NEW.school_id AND id=NEW.record_id AND method=NEW.method AND source_reference=NEW.source_reference AND ledger_entry_id=NEW.ledger_entry_id AND amount_cents=NEW.amount_cents AND currency=NEW.currency))$extra THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='External receipt identity retains its original immutable domain receipt'; END IF; END");
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Retain re-enrolment, bursary and giving history.');
            }
        }
        if (DB::table('ledger_accounts')->whereIn('type', ['giving_bank', 'giving_income'])->exists()) {
            throw new RuntimeException('Retain alumni giving ledger accounts.');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS giving_donation_external_identity');
        $this->receiptRegistry(false);
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        $this->ledgerTypes(false);
    }
};

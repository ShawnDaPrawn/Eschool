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
        Schema::create('wallet_desk_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('threshold_cents');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique('school_id');
        });
        DB::statement("ALTER TABLE wallet_desk_policies ADD CONSTRAINT wallet_desk_policy_shape CHECK (currency REGEXP '^[A-Z]{3}$' AND threshold_cents BETWEEN 0 AND 100000000000 AND revision>0)");
        Schema::create('wallet_desk_topups', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('wallet_account_id');
            $t->foreign(['school_id', 'wallet_account_id'])->references(['school_id', 'id'])->on('wallet_accounts')->restrictOnDelete();
            $t->foreignId('owner_user_id')->constrained('users')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            foreach (['master_account_id', 'reserve_account_id'] as $key) {
                $t->unsignedBigInteger($key);
                $t->foreign(['school_id', $key])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
            }
            $t->unsignedBigInteger('reserve_entry_id');
            $t->foreign(['school_id', 'reserve_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->unsignedInteger('policy_revision');
            $t->bigInteger('threshold_cents');
            $t->boolean('requires_second_approval');
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->text('reason');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'reserve_entry_id']);
            $t->unique(['school_id', 'reserve_account_id']);
        });
        DB::statement("ALTER TABLE wallet_desk_topups ADD CONSTRAINT wallet_desk_topup_shape CHECK (currency REGEXP '^[A-Z]{3}$' AND operation_id REGEXP '^[0-9a-fA-F-]{36}$' AND amount_cents BETWEEN 1 AND 100000000000 AND threshold_cents BETWEEN 0 AND 100000000000 AND policy_revision>0 AND requires_second_approval=IF(amount_cents>threshold_cents,1,0) AND CHAR_LENGTH(reason)>0)");
        Schema::create('wallet_desk_actions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('topup_id');
            $t->foreign(['school_id', 'topup_id'])->references(['school_id', 'id'])->on('wallet_desk_topups')->restrictOnDelete();
            $t->string('action', 12);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('ledger_entry_id');
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->unsignedInteger('policy_revision');
            $t->bigInteger('threshold_cents');
            $t->boolean('requires_second_approval');
            $t->text('note')->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'topup_id']);
            $t->unique(['school_id', 'ledger_entry_id']);
        });
        DB::statement("ALTER TABLE wallet_desk_actions ADD CONSTRAINT wallet_desk_action_shape CHECK (action IN ('released','cancelled') AND policy_revision>0 AND threshold_cents BETWEEN 0 AND 100000000000 AND requires_second_approval IN (0,1) AND (action<>'cancelled' OR (note IS NOT NULL AND CHAR_LENGTH(note)>0)))");
        DB::unprepared("CREATE TRIGGER wallet_desk_topup_insert BEFORE INSERT ON wallet_desk_topups FOR EACH ROW BEGIN
   DECLARE amount_debit DECIMAL(65,0);DECLARE amount_credit DECIMAL(65,0);DECLARE posted_count INT;
   SELECT COALESCE(SUM(CASE WHEN p.direction='debit' AND a.id=NEW.master_account_id AND a.type='wallet' AND a.module='wallet' AND a.owner_user_id IS NULL AND a.reference=CONCAT('wallet:master:',NEW.currency) THEN p.cents ELSE 0 END),0),COALESCE(SUM(CASE WHEN p.direction='credit' AND a.id=NEW.reserve_account_id AND a.type='wallet' AND a.module='wallet' AND a.owner_user_id IS NULL AND a.reference=CONCAT('wallet:desk-reserve:',NEW.operation_id) THEN p.cents ELSE 0 END),0),COUNT(*) INTO amount_debit,amount_credit,posted_count FROM ledger_postings p JOIN ledger_accounts a ON a.school_id=p.school_id AND a.id=p.account_id JOIN ledger_entries e ON e.school_id=p.school_id AND e.id=p.entry_id WHERE p.school_id=NEW.school_id AND p.entry_id=NEW.reserve_entry_id AND e.posted_at IS NOT NULL AND e.currency=NEW.currency;
   IF amount_debit<>NEW.amount_cents OR amount_credit<>NEW.amount_cents OR posted_count<>2 OR NOT EXISTS(SELECT 1 FROM wallet_accounts WHERE school_id=NEW.school_id AND id=NEW.wallet_account_id AND user_id=NEW.owner_user_id AND currency=NEW.currency) OR NOT EXISTS(SELECT 1 FROM wallet_desk_policies WHERE school_id=NEW.school_id AND revision=NEW.policy_revision AND threshold_cents=NEW.threshold_cents AND currency=NEW.currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Desk top-up retains exact master reservation, personal owner and approval policy'; END IF;
  END");
        DB::unprepared("CREATE TRIGGER wallet_desk_action_insert BEFORE INSERT ON wallet_desk_actions FOR EACH ROW BEGIN
   DECLARE requested_amount BIGINT;DECLARE reserve_id BIGINT;DECLARE master_id BIGINT;DECLARE personal_id BIGINT;DECLARE requester BIGINT;DECLARE requested_currency CHAR(3);DECLARE original_second TINYINT;DECLARE required_second TINYINT;DECLARE amount_debit DECIMAL(65,0);DECLARE amount_credit DECIMAL(65,0);DECLARE posted_count INT;
   SELECT t.amount_cents,t.reserve_account_id,t.master_account_id,w.ledger_account_id,t.requested_by,t.currency,t.requires_second_approval INTO requested_amount,reserve_id,master_id,personal_id,requester,requested_currency,original_second FROM wallet_desk_topups t JOIN wallet_accounts w ON w.school_id=t.school_id AND w.id=t.wallet_account_id WHERE t.school_id=NEW.school_id AND t.id=NEW.topup_id;
   SET required_second=IF(NEW.action='released' AND (original_second=1 OR requested_amount>NEW.threshold_cents),1,0);
   SELECT COALESCE(SUM(CASE WHEN p.direction='debit' AND a.id=reserve_id AND a.type='wallet' AND a.module='wallet' THEN p.cents ELSE 0 END),0),COALESCE(SUM(CASE WHEN p.direction='credit' AND a.id=IF(NEW.action='released',personal_id,master_id) AND a.type='wallet' AND a.module='wallet' THEN p.cents ELSE 0 END),0),COUNT(*) INTO amount_debit,amount_credit,posted_count FROM ledger_postings p JOIN ledger_accounts a ON a.school_id=p.school_id AND a.id=p.account_id JOIN ledger_entries e ON e.school_id=p.school_id AND e.id=p.entry_id WHERE p.school_id=NEW.school_id AND p.entry_id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency=requested_currency;
   IF requested_amount IS NULL OR amount_debit<>requested_amount OR amount_credit<>requested_amount OR posted_count<>2 OR NEW.requires_second_approval<>required_second OR (required_second=1 AND NEW.actor_id=requester) OR NOT EXISTS(SELECT 1 FROM wallet_desk_policies WHERE school_id=NEW.school_id AND revision=NEW.policy_revision AND threshold_cents=NEW.threshold_cents AND currency=requested_currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Desk release/cancellation retains its exact reservation, approval separation and current policy'; END IF;
  END");
        DB::unprepared("CREATE TRIGGER wallet_desk_policy_update BEFORE UPDATE ON wallet_desk_policies FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Desk policy retains school and ordered revisions'; END IF; END");
        foreach (['wallet_desk_topups' => ['UPDATE', 'DELETE'], 'wallet_desk_actions' => ['UPDATE', 'DELETE'], 'wallet_desk_policies' => ['DELETE']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Desk reservation, approval and policy history is retained'");
            }
        }
    }

    public function down(): void
    {
        foreach (['wallet_desk_topups', 'wallet_desk_actions', 'wallet_desk_policies'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain desk top-up approval and reservation history.');
            }
        }
        foreach (['wallet_desk_actions', 'wallet_desk_topups', 'wallet_desk_policies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

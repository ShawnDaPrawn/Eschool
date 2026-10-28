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
        Schema::create('wallet_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('daily_spend_cents');
            $t->boolean('child_limits_required');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique('school_id');
            $t->unique(['school_id', 'id']);
        });
        DB::statement("ALTER TABLE wallet_settings ADD CONSTRAINT wallet_settings_shape CHECK (currency REGEXP '^[A-Z]{3}$' AND daily_spend_cents BETWEEN 0 AND 100000000000 AND child_limits_required IN (0,1) AND revision>0)");
        Schema::create('wallet_accounts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('ledger_account_id');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'user_id', 'currency']);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'ledger_account_id']);
            $t->foreign(['school_id', 'ledger_account_id'])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
        });
        DB::unprepared("CREATE TRIGGER wallet_account_insert BEFORE INSERT ON wallet_accounts FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM ledger_accounts a JOIN wallet_settings s ON s.school_id=a.school_id AND s.currency=a.currency WHERE a.school_id=NEW.school_id AND a.id=NEW.ledger_account_id AND a.owner_user_id=NEW.user_id AND a.type='wallet' AND a.module='wallet' AND a.currency=NEW.currency AND a.reference=CONCAT('wallet:person:',NEW.user_id,':',NEW.currency)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Personal wallet retains its exact school, person, currency and ledger account'; END IF; END");
        Schema::create('wallet_limits', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('wallet_account_id');
            $t->bigInteger('daily_spend_cents');
            $t->boolean('all_categories');
            $t->json('categories');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'wallet_account_id']);
            $t->foreign(['school_id', 'wallet_account_id'])->references(['school_id', 'id'])->on('wallet_accounts')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE wallet_limits ADD CONSTRAINT wallet_limit_shape CHECK (daily_spend_cents BETWEEN 0 AND 100000000000 AND all_categories IN (0,1) AND JSON_TYPE(categories)='ARRAY' AND JSON_LENGTH(categories)<=50 AND (all_categories=0 OR JSON_LENGTH(categories)=0) AND revision>0)");
        Schema::create('wallet_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->json('receipt');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
        });
        DB::unprepared("CREATE TRIGGER wallet_settings_update BEFORE UPDATE ON wallet_settings FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 OR (NEW.currency<>OLD.currency AND EXISTS(SELECT 1 FROM wallet_accounts WHERE school_id=OLD.school_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet settings retain school, revisions and issued currency'; END IF; END");
        DB::unprepared("CREATE TRIGGER wallet_limit_update BEFORE UPDATE ON wallet_limits FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.wallet_account_id<>OLD.wallet_account_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet controls retain owner and ordered revisions'; END IF; END");
        foreach (['wallet_accounts' => ['UPDATE', 'DELETE'], 'wallet_operations' => ['UPDATE', 'DELETE'], 'wallet_settings' => ['DELETE'], 'wallet_limits' => ['DELETE']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Wallet identity, controls and operation history are retained'");
            }
        }
    }

    public function down(): void
    {
        foreach (['wallet_accounts', 'wallet_limits', 'wallet_settings', 'wallet_operations'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain issued wallets, controls and operation history.');
            }
        }
        foreach (['wallet_operations', 'wallet_limits', 'wallet_accounts', 'wallet_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

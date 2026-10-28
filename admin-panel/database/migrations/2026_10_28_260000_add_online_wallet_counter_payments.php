<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['vendor_card_readers', 'wallet_payment_intents', 'wallet_payment_closures', 'wallet_counter_payments'];

    public function up(): void
    {
        Schema::create('vendor_card_readers', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('vendor_id');
            $t->uuid('device_id');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->foreign(['school_id', 'vendor_id'])->references(['school_id', 'id'])->on('vendors')->restrictOnDelete();
            $t->foreign(['school_id', 'device_id'])->references(['school_id', 'id'])->on('card_reader_devices')->restrictOnDelete();
            $t->unique(['school_id', 'device_id']);
        });
        Schema::create('wallet_payment_intents', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('wallet_account_id');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->string('kind', 8);
            $t->char('currency', 3);
            foreach (['amount_cents', 'net_cents', 'vat_cents'] as $c) {
                $t->bigInteger($c);
            }
            $t->text('private_quote');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->text('private_qr_token')->nullable();
            $t->char('qr_hash', 64)->nullable()->collation('utf8mb4_bin');
            $t->uuid('card_id')->nullable();
            $t->uuid('device_id')->nullable();
            $t->unsignedInteger('card_revision')->nullable();
            $t->unsignedInteger('portrait_revision')->nullable();
            $t->char('portrait_digest', 64)->nullable();
            $t->unsignedInteger('sun_counter')->nullable();
            $t->dateTime('expires_at', 6);
            $t->dateTime('created_at', 6);
            foreach (['vendor_id' => 'vendors', 'wallet_account_id' => 'wallet_accounts', 'card_id' => 'cards', 'device_id' => 'card_reader_devices'] as $c => $table) {
                $t->foreign(['school_id', $c])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'qr_hash']);
            $t->unique(['school_id', 'card_id', 'sun_counter']);
            $t->unique(['school_id', 'actor_id', 'operation_id']);
            $t->index(['school_id', 'actor_id', 'created_at']);
        });
        Schema::create('wallet_payment_closures', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->uuid('intent_id');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->foreign(['school_id', 'intent_id'])->references(['school_id', 'id'])->on('wallet_payment_intents')->restrictOnDelete();
            $t->unique(['school_id', 'intent_id']);
        });
        Schema::create('wallet_counter_payments', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->uuid('intent_id');
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('order_id');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            foreach (['intent_id' => 'wallet_payment_intents', 'vendor_id' => 'vendors', 'order_id' => 'vendor_orders'] as $c => $table) {
                $t->foreign(['school_id', $c])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'intent_id']);
            $t->unique(['school_id', 'order_id']);
            $t->unique(['school_id', 'operation_id']);
        });
        Schema::table('vendor_orders', function (Blueprint $t): void {
            $t->string('purchase_channel', 12)->default('app');
            $t->uuid('wallet_intent_id')->nullable();
            $t->unique(['school_id', 'wallet_intent_id']);
            $t->foreign(['school_id', 'wallet_intent_id'])->references(['school_id', 'id'])->on('wallet_payment_intents')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE vendor_orders ADD CONSTRAINT wallet_counter_order_shape CHECK ((purchase_channel='app' AND wallet_intent_id IS NULL) OR (purchase_channel='counter' AND wallet_intent_id IS NOT NULL))");
        DB::unprepared("CREATE TRIGGER wallet_counter_order_insert BEFORE INSERT ON vendor_orders FOR EACH ROW BEGIN IF NEW.purchase_channel='counter' AND (EXISTS(SELECT 1 FROM wallet_payment_closures c WHERE c.school_id=NEW.school_id AND c.intent_id=NEW.wallet_intent_id) OR NOT EXISTS(SELECT 1 FROM wallet_payment_intents i WHERE i.school_id=NEW.school_id AND i.id=NEW.wallet_intent_id AND i.vendor_id=NEW.vendor_id AND i.wallet_account_id=NEW.wallet_account_id AND i.actor_id=NEW.actor_id AND i.currency=NEW.currency AND i.amount_cents=NEW.amount_cents AND i.net_cents=NEW.net_cents AND i.vat_cents=NEW.vat_cents AND i.fingerprint=NEW.quote_fingerprint AND NEW.created_at<i.expires_at)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Counter order retains its exact live original wallet payment instruction'; END IF; END");
        DB::unprepared("CREATE TRIGGER wallet_counter_order_update BEFORE UPDATE ON vendor_orders FOR EACH ROW BEGIN IF NOT(NEW.purchase_channel<=>OLD.purchase_channel) OR NOT(NEW.wallet_intent_id<=>OLD.wallet_intent_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Original order authorization channel and wallet instruction are immutable'; END IF; END");
        DB::statement("ALTER TABLE wallet_payment_intents ADD CONSTRAINT wallet_payment_intent_shape CHECK (kind IN ('qr','sun') AND amount_cents BETWEEN 0 AND 100000000000 AND net_cents>=0 AND vat_cents>=0 AND amount_cents=net_cents+vat_cents AND expires_at>created_at AND ((kind='qr' AND private_qr_token IS NOT NULL AND qr_hash IS NOT NULL AND card_id IS NULL AND device_id IS NULL AND card_revision IS NULL AND portrait_revision IS NULL AND portrait_digest IS NULL AND sun_counter IS NULL) OR (kind='sun' AND private_qr_token IS NULL AND qr_hash IS NULL AND card_id IS NOT NULL AND device_id IS NOT NULL AND card_revision IS NOT NULL AND card_revision>0 AND portrait_revision IS NOT NULL AND portrait_revision>0 AND portrait_digest IS NOT NULL AND sun_counter IS NOT NULL AND sun_counter BETWEEN 0 AND 16777215)))");
        DB::unprepared("CREATE TRIGGER vendor_card_reader_insert BEFORE INSERT ON vendor_card_readers FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM card_reader_devices d WHERE d.school_id=NEW.school_id AND d.id=NEW.device_id AND d.kind='vendor' AND d.issued_by=NEW.actor_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment reader retains its explicit Vendor purpose and issuer'; END IF; END");
        DB::unprepared("CREATE TRIGGER wallet_payment_intent_insert BEFORE INSERT ON wallet_payment_intents FOR EACH ROW BEGIN
            IF NOT EXISTS(SELECT 1 FROM wallet_accounts w JOIN vendors v ON v.school_id=w.school_id WHERE w.school_id=NEW.school_id AND w.id=NEW.wallet_account_id AND v.id=NEW.vendor_id AND w.currency=NEW.currency AND v.currency=NEW.currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Counter instruction retains its original wallet, Vendor and currency'; END IF;
            IF NEW.kind='sun' AND NOT EXISTS(SELECT 1 FROM cards c JOIN card_chip_profiles p ON p.school_id=c.school_id AND p.card_id=c.id JOIN card_portraits ph ON ph.school_id=c.school_id AND ph.user_id=c.user_id JOIN vendor_card_readers r ON r.school_id=c.school_id AND r.device_id=NEW.device_id JOIN wallet_accounts w ON w.school_id=c.school_id AND w.user_id=c.user_id WHERE c.school_id=NEW.school_id AND c.id=NEW.card_id AND c.status='active' AND c.revision=NEW.card_revision AND c.user_id=NEW.actor_id AND w.id=NEW.wallet_account_id AND r.vendor_id=NEW.vendor_id AND p.last_counter=NEW.sun_counter AND ph.revision=NEW.portrait_revision AND ph.digest=NEW.portrait_digest) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Card payment instruction retains exact verified SUN and portrait revisions'; END IF;
        END");
        DB::unprepared("CREATE TRIGGER wallet_counter_payment_insert BEFORE INSERT ON wallet_counter_payments FOR EACH ROW BEGIN
            IF EXISTS(SELECT 1 FROM wallet_payment_closures WHERE school_id=NEW.school_id AND intent_id=NEW.intent_id) OR NOT EXISTS(SELECT 1 FROM wallet_payment_intents i JOIN vendor_orders o ON o.school_id=i.school_id JOIN vendor_order_actions a ON a.school_id=o.school_id AND a.order_id=o.id WHERE i.school_id=NEW.school_id AND i.id=NEW.intent_id AND i.vendor_id=NEW.vendor_id AND o.id=NEW.order_id AND o.purchase_channel='counter' AND o.wallet_intent_id=i.id AND o.vendor_id=i.vendor_id AND o.wallet_account_id=i.wallet_account_id AND o.actor_id=i.actor_id AND o.currency=i.currency AND o.amount_cents=i.amount_cents AND o.net_cents=i.net_cents AND o.vat_cents=i.vat_cents AND o.quote_fingerprint=i.fingerprint AND o.sealed_at IS NOT NULL AND a.action='collected' AND a.actor_id=NEW.actor_id AND NEW.created_at<i.expires_at) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Counter payment retains the exact live instruction and original collected order'; END IF;
        END");
        DB::unprepared("CREATE TRIGGER wallet_payment_closure_insert BEFORE INSERT ON wallet_payment_closures FOR EACH ROW BEGIN IF EXISTS(SELECT 1 FROM wallet_counter_payments WHERE school_id=NEW.school_id AND intent_id=NEW.intent_id) OR NOT EXISTS(SELECT 1 FROM wallet_payment_intents WHERE school_id=NEW.school_id AND id=NEW.intent_id AND actor_id=NEW.actor_id AND kind='qr') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only the original holder can cancel an unpaid QR instruction'; END IF; END");
        foreach (self::TABLES as $table) {
            foreach (['UPDATE', 'DELETE'] as $action) {
                $suffix = strtolower($action);
                DB::unprepared("CREATE TRIGGER {$table}_{$suffix} BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Counter payment evidence is immutable'");
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain original online counter payment evidence.');
            }
        }
        DB::unprepared('DROP TRIGGER wallet_counter_order_insert');
        DB::unprepared('DROP TRIGGER wallet_counter_order_update');
        $drop = str_contains((string) DB::getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION), 'MariaDB') ? 'CONSTRAINT' : 'CHECK';
        DB::statement("ALTER TABLE vendor_orders DROP {$drop} wallet_counter_order_shape");
        Schema::table('vendor_orders', function (Blueprint $t): void {
            $t->dropForeign(['school_id', 'wallet_intent_id']);
            $t->dropUnique(['school_id', 'wallet_intent_id']);
            $t->dropColumn(['purchase_channel', 'wallet_intent_id']);
        });
        foreach (array_reverse(self::TABLES) as $table) {
            Schema::dropIfExists($table);
        }
    }
};

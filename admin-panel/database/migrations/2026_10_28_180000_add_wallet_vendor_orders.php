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
        // Check retained counted stock before changing its projection mechanism.
        foreach (DB::table('vendor_products')->where('tracks_stock', true)->orderBy('id')->get() as $product) {
            $balance = DB::table('vendor_stock_balances')->where('school_id', $product->school_id)->where('product_id', $product->id)->first();
            $units = 0;
            $rows = DB::table('vendor_stock_movements')->where('school_id', $product->school_id)->where('product_id', $product->id)->orderBy('id')->get();
            foreach ($rows as $row) {
                if ((int) $row->before_units !== $units || (int) $row->after_units !== $units + (int) $row->units_delta) {
                    throw new RuntimeException('Reconcile retained counted stock before adding orders.');
                }
                $units = (int) $row->after_units;
            }
            if ($balance === null || (int) $balance->on_hand !== $units || (int) $balance->revision !== $rows->count()) {
                throw new RuntimeException('Retained stock projection differs from immutable receipts.');
            }
        }
        if (! Schema::hasColumn('vendors', 'legal_address')) {
            Schema::table('vendors', fn (Blueprint $t) => $t->text('legal_address')->nullable());
        }
        Schema::create('vendor_order_counters', function (Blueprint $t): void {
            $t->foreignId('school_id')->primary()->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('next_number');
        });
        Schema::create('vendor_orders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->string('order_number', 32)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('wallet_account_id');
            $t->unsignedBigInteger('learner_id')->nullable();
            $t->unsignedBigInteger('ledger_entry_id')->nullable();
            foreach (['vendor_id' => 'vendors', 'wallet_account_id' => 'wallet_accounts', 'learner_id' => 'students', 'ledger_entry_id' => 'ledger_entries'] as $field => $table) {
                $t->foreign(['school_id', $field])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('wallet_owner_id')->constrained('users')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->bigInteger('net_cents');
            $t->bigInteger('vat_cents');
            $t->json('seller_snapshot');
            $t->char('quote_fingerprint', 64)->collation('utf8mb4_bin');
            $t->text('private_qr_token');
            $t->char('qr_hash', 64)->collation('utf8mb4_bin');
            $t->dateTime('created_at', 6);
            $t->dateTime('sealed_at', 6)->nullable();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'order_number']);
            $t->unique(['school_id', 'qr_hash']);
            $t->unique(['school_id', 'ledger_entry_id']);
            $t->index(['school_id', 'wallet_account_id', 'created_at'], 'vendor_orders_wallet_day');
        });
        DB::statement("ALTER TABLE vendor_orders ADD CONSTRAINT vendor_order_shape CHECK (amount_cents BETWEEN 0 AND 100000000000 AND net_cents>=0 AND vat_cents>=0 AND amount_cents=net_cents+vat_cents AND ((amount_cents=0 AND ledger_entry_id IS NULL) OR (amount_cents>0 AND ledger_entry_id IS NOT NULL)) AND currency REGEXP '^[A-Z]{3}$' AND CHAR_LENGTH(private_qr_token)>0 AND CHAR_LENGTH(qr_hash)=64)");
        Schema::create('vendor_order_lines', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('product_id');
            foreach (['order_id' => 'vendor_orders', 'product_id' => 'vendor_products'] as $field => $table) {
                $t->foreign(['school_id', $field])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->unsignedInteger('product_revision');
            $t->string('product_name', 160);
            $t->string('sku', 64);
            $t->string('unit_label', 40);
            $t->string('category_key', 40)->collation('utf8mb4_bin');
            $t->unsignedInteger('quantity');
            $t->bigInteger('unit_price_cents');
            $t->bigInteger('gross_cents');
            $t->bigInteger('net_cents');
            $t->bigInteger('vat_cents');
            $t->string('vat_kind', 12);
            $t->unsignedInteger('vat_rate_bps');
            $t->boolean('tracks_stock');
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'order_id', 'product_id']);
        });
        DB::statement("ALTER TABLE vendor_order_lines ADD CONSTRAINT vendor_order_line_shape CHECK (quantity BETWEEN 1 AND 999 AND product_revision>0 AND unit_price_cents>=0 AND gross_cents=quantity*unit_price_cents AND gross_cents=net_cents+vat_cents AND gross_cents BETWEEN 0 AND 100000000000 AND net_cents>=0 AND vat_cents>=0 AND tracks_stock IN (0,1) AND ((vat_kind IN ('none','zero','exempt') AND vat_rate_bps=0 AND vat_cents=0) OR (vat_kind='standard' AND vat_rate_bps BETWEEN 1 AND 10000 AND vat_cents=FLOOR((gross_cents*vat_rate_bps+(10000+vat_rate_bps)/2)/(10000+vat_rate_bps)))))");
        Schema::create('vendor_order_actions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('ledger_entry_id')->nullable();
            foreach (['order_id' => 'vendor_orders', 'ledger_entry_id' => 'ledger_entries'] as $field => $table) {
                $t->foreign(['school_id', $field])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('action', 12);
            $t->text('private_reason')->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'order_id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'ledger_entry_id']);
        });
        DB::statement("ALTER TABLE vendor_order_actions ADD CONSTRAINT vendor_order_action_shape CHECK (action IN ('collected','cancelled') AND (action<>'collected' OR ledger_entry_id IS NULL) AND (action<>'cancelled' OR (private_reason IS NOT NULL AND CHAR_LENGTH(private_reason)>0)))");
        Schema::create('vendor_stock_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('product_id');
            $t->unsignedBigInteger('manual_movement_id')->nullable();
            $t->unsignedBigInteger('order_line_id')->nullable();
            $t->unsignedBigInteger('order_action_id')->nullable();
            foreach (['product_id' => 'vendor_products', 'manual_movement_id' => 'vendor_stock_movements', 'order_line_id' => 'vendor_order_lines', 'order_action_id' => 'vendor_order_actions'] as $field => $table) {
                $t->foreign(['school_id', $field])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->string('kind', 12);
            $t->unsignedInteger('sequence');
            $t->bigInteger('units_delta');
            $t->bigInteger('before_units');
            $t->bigInteger('after_units');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'product_id', 'sequence'], 'vendor_stock_event_sequence');
            $t->unique(['school_id', 'manual_movement_id']);
            $t->unique(['school_id', 'order_line_id', 'kind'], 'vendor_stock_event_order_kind');
        });
        DB::statement("ALTER TABLE vendor_stock_events ADD CONSTRAINT vendor_stock_event_shape CHECK (sequence>0 AND units_delta<>0 AND before_units BETWEEN 0 AND 1000000000 AND after_units BETWEEN 0 AND 1000000000 AND after_units=before_units+units_delta AND ((kind='manual' AND manual_movement_id IS NOT NULL AND order_line_id IS NULL AND order_action_id IS NULL) OR (kind='purchase' AND manual_movement_id IS NULL AND order_line_id IS NOT NULL AND order_action_id IS NULL AND units_delta<0) OR (kind='cancellation' AND manual_movement_id IS NULL AND order_line_id IS NOT NULL AND order_action_id IS NOT NULL AND units_delta>0)))");
        // Index original roots, retaining their IDs, quantities, timestamps and private proof.
        foreach (DB::table('vendor_stock_movements')->orderBy('school_id')->orderBy('product_id')->orderBy('id')->get()->groupBy(fn ($m) => $m->school_id.':'.$m->product_id) as $rows) {
            foreach ($rows->values() as $index => $row) {
                DB::table('vendor_stock_events')->insert(['school_id' => $row->school_id, 'product_id' => $row->product_id, 'manual_movement_id' => $row->id, 'kind' => 'manual', 'sequence' => $index + 1, 'units_delta' => $row->units_delta, 'before_units' => $row->before_units, 'after_units' => $row->after_units, 'created_at' => $row->created_at]);
            }
        }
        DB::unprepared('DROP TRIGGER vendor_stock_movement_project');
        DB::unprepared('DROP TRIGGER vendor_stock_balance_update');
        DB::unprepared("CREATE TRIGGER vendor_stock_balance_update BEFORE UPDATE ON vendor_stock_balances FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.product_id<>OLD.product_id OR NEW.revision<>OLD.revision+1 OR NEW.on_hand<>(SELECT COALESCE(SUM(units_delta),0) FROM vendor_stock_events WHERE school_id=OLD.school_id AND product_id=OLD.product_id) OR NEW.revision<>(SELECT COUNT(*) FROM vendor_stock_events WHERE school_id=OLD.school_id AND product_id=OLD.product_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stock balance is a checked immutable event projection'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_stock_event_insert BEFORE INSERT ON vendor_stock_events FOR EACH ROW BEGIN
            IF NOT EXISTS(SELECT 1 FROM vendor_stock_balances b WHERE b.school_id=NEW.school_id AND b.product_id=NEW.product_id AND b.on_hand=NEW.before_units AND b.revision+1=NEW.sequence) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stock event retains its exact preceding balance and sequence'; END IF;
            IF NEW.kind='manual' AND NOT EXISTS(SELECT 1 FROM vendor_stock_movements m WHERE m.school_id=NEW.school_id AND m.id=NEW.manual_movement_id AND m.product_id=NEW.product_id AND m.units_delta=NEW.units_delta AND m.before_units=NEW.before_units AND m.after_units=NEW.after_units AND m.created_at=NEW.created_at) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Manual stock event retains its original counted receipt'; END IF;
            IF NEW.kind IN ('purchase','cancellation') AND NOT EXISTS(SELECT 1 FROM vendor_order_lines l JOIN vendor_orders o ON o.school_id=l.school_id AND o.id=l.order_id WHERE l.school_id=NEW.school_id AND l.id=NEW.order_line_id AND l.product_id=NEW.product_id AND l.tracks_stock=1 AND ((NEW.kind='purchase' AND NEW.units_delta=-l.quantity AND o.sealed_at IS NULL) OR (NEW.kind='cancellation' AND NEW.units_delta=l.quantity AND EXISTS(SELECT 1 FROM vendor_order_actions a WHERE a.school_id=o.school_id AND a.id=NEW.order_action_id AND a.order_id=o.id AND a.action='cancelled')))) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Order stock event retains the exact paid or cancelled original line'; END IF;
        END");
        DB::unprepared('CREATE TRIGGER vendor_stock_event_project AFTER INSERT ON vendor_stock_events FOR EACH ROW UPDATE vendor_stock_balances SET on_hand=NEW.after_units,revision=NEW.sequence WHERE school_id=NEW.school_id AND product_id=NEW.product_id');
        DB::unprepared("CREATE TRIGGER vendor_stock_movement_project AFTER INSERT ON vendor_stock_movements FOR EACH ROW BEGIN DECLARE next_sequence INT; SELECT revision+1 INTO next_sequence FROM vendor_stock_balances WHERE school_id=NEW.school_id AND product_id=NEW.product_id; INSERT INTO vendor_stock_events(school_id,product_id,manual_movement_id,kind,sequence,units_delta,before_units,after_units,created_at) VALUES(NEW.school_id,NEW.product_id,NEW.id,'manual',next_sequence,NEW.units_delta,NEW.before_units,NEW.after_units,NEW.created_at); END");
        DB::unprepared("CREATE TRIGGER vendor_order_insert BEFORE INSERT ON vendor_orders FOR EACH ROW BEGIN
            IF NEW.sealed_at IS NOT NULL OR NOT EXISTS(SELECT 1 FROM vendors v JOIN wallet_accounts w ON w.school_id=v.school_id WHERE v.school_id=NEW.school_id AND v.id=NEW.vendor_id AND w.id=NEW.wallet_account_id AND w.user_id=NEW.wallet_owner_id AND v.currency=NEW.currency AND w.currency=NEW.currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Order retains its own original wallet, vendor and currency'; END IF;
            IF NEW.amount_cents>0 AND NOT EXISTS(SELECT 1 FROM ledger_entries e JOIN vendors v ON v.school_id=e.school_id AND v.id=NEW.vendor_id JOIN wallet_accounts w ON w.school_id=e.school_id AND w.id=NEW.wallet_account_id WHERE e.school_id=NEW.school_id AND e.id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency=NEW.currency AND e.source=CONCAT('vendor:order:',NEW.operation_id) AND (SELECT COUNT(*) FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id)=2 AND EXISTS(SELECT 1 FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id AND account_id=w.ledger_account_id AND direction='debit' AND cents=NEW.amount_cents) AND EXISTS(SELECT 1 FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id AND account_id=v.ledger_account_id AND direction='credit' AND cents=NEW.amount_cents)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Paid order has its exact balanced original-wallet to vendor entry'; END IF;
        END");
        DB::unprepared("CREATE TRIGGER vendor_order_line_insert BEFORE INSERT ON vendor_order_lines FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM vendor_orders o JOIN vendor_products p ON p.school_id=o.school_id AND p.vendor_id=o.vendor_id JOIN vendor_categories c ON c.school_id=p.school_id AND c.id=p.category_id WHERE o.school_id=NEW.school_id AND o.id=NEW.order_id AND o.sealed_at IS NULL AND p.id=NEW.product_id AND p.revision=NEW.product_revision AND p.name=NEW.product_name AND p.sku=NEW.sku AND p.unit_label=NEW.unit_label AND p.price_cents=NEW.unit_price_cents AND p.vat_kind=NEW.vat_kind AND p.vat_rate_bps=NEW.vat_rate_bps AND p.tracks_stock=NEW.tracks_stock AND c.`key`=NEW.category_key) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Order line retains its original vendor product, category, price and tax revision'; END IF; END");
        $immutable = ['id', 'school_id', 'operation_id', 'order_number', 'vendor_id', 'wallet_account_id', 'learner_id', 'ledger_entry_id', 'actor_id', 'wallet_owner_id', 'currency', 'amount_cents', 'net_cents', 'vat_cents', 'seller_snapshot', 'quote_fingerprint', 'private_qr_token', 'qr_hash', 'created_at'];
        $same = implode(' OR ', array_map(fn ($c) => "NOT(NEW.`$c` <=> OLD.`$c`)", $immutable));
        DB::unprepared("CREATE TRIGGER vendor_order_seal BEFORE UPDATE ON vendor_orders FOR EACH ROW BEGIN IF $same OR OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR (SELECT COUNT(*) FROM vendor_order_lines WHERE school_id=OLD.school_id AND order_id=OLD.id) NOT BETWEEN 1 AND 20 OR (SELECT COALESCE(SUM(gross_cents),0) FROM vendor_order_lines WHERE school_id=OLD.school_id AND order_id=OLD.id)<>OLD.amount_cents OR (SELECT COALESCE(SUM(vat_cents),0) FROM vendor_order_lines WHERE school_id=OLD.school_id AND order_id=OLD.id)<>OLD.vat_cents OR EXISTS(SELECT 1 FROM vendor_order_lines l WHERE l.school_id=OLD.school_id AND l.order_id=OLD.id AND l.tracks_stock=1 AND NOT EXISTS(SELECT 1 FROM vendor_stock_events s WHERE s.school_id=l.school_id AND s.order_line_id=l.id AND s.kind='purchase')) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Complete order seals once with all exact lines and stock events'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_order_action_insert BEFORE INSERT ON vendor_order_actions FOR EACH ROW BEGIN
            IF NOT EXISTS(SELECT 1 FROM vendor_orders WHERE school_id=NEW.school_id AND id=NEW.order_id AND sealed_at IS NOT NULL) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only a complete original order can be collected or cancelled'; END IF;
            IF NEW.action='cancelled' AND NOT EXISTS(SELECT 1 FROM vendor_orders o JOIN vendors v ON v.school_id=o.school_id AND v.id=o.vendor_id JOIN wallet_accounts w ON w.school_id=o.school_id AND w.id=o.wallet_account_id WHERE o.school_id=NEW.school_id AND o.id=NEW.order_id AND ((o.amount_cents=0 AND NEW.ledger_entry_id IS NULL) OR (o.amount_cents>0 AND EXISTS(SELECT 1 FROM ledger_entries e WHERE e.school_id=o.school_id AND e.id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.source=CONCAT('vendor:cancel:',o.id) AND (SELECT COUNT(*) FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id)=2 AND EXISTS(SELECT 1 FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id AND account_id=v.ledger_account_id AND direction='debit' AND cents=o.amount_cents) AND EXISTS(SELECT 1 FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id AND account_id=w.ledger_account_id AND direction='credit' AND cents=o.amount_cents))))) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cancelled order returns its exact original gross only to its original wallet'; END IF;
        END");
        DB::unprepared("CREATE TRIGGER vendor_order_counter_update BEFORE UPDATE ON vendor_order_counters FOR EACH ROW BEGIN IF NEW.school_id<>OLD.school_id OR NEW.next_number<>OLD.next_number+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='School order numbers advance once without reuse'; END IF; END");
        foreach (['vendor_orders', 'vendor_order_lines', 'vendor_order_actions', 'vendor_stock_events', 'vendor_order_counters'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_delete BEFORE DELETE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Order and inventory history is retained'");
        }
        foreach (['vendor_order_lines', 'vendor_order_actions', 'vendor_stock_events'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_update BEFORE UPDATE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Order and inventory roots are immutable'");
        }
    }

    public function down(): void
    {
        foreach (['vendor_orders', 'vendor_order_lines', 'vendor_order_actions'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain original wallet orders, stock and counter actions.');
            }
        }
        // Manual receipts may predate this migration. Their original projection remains exact.
        DB::unprepared('DROP TRIGGER vendor_stock_movement_project');
        DB::unprepared('DROP TRIGGER vendor_stock_balance_update');
        DB::unprepared('CREATE TRIGGER vendor_stock_movement_project AFTER INSERT ON vendor_stock_movements FOR EACH ROW UPDATE vendor_stock_balances SET on_hand=NEW.after_units,revision=revision+1 WHERE school_id=NEW.school_id AND product_id=NEW.product_id');
        DB::unprepared("CREATE TRIGGER vendor_stock_balance_update BEFORE UPDATE ON vendor_stock_balances FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.product_id<>OLD.product_id OR NEW.revision<>OLD.revision+1 OR NEW.on_hand<>(SELECT COALESCE(SUM(units_delta),0) FROM vendor_stock_movements WHERE school_id=OLD.school_id AND product_id=OLD.product_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stock balance is a checked immutable-history projection'; END IF; END");
        foreach (['vendor_stock_events', 'vendor_order_actions', 'vendor_order_lines', 'vendor_orders', 'vendor_order_counters'] as $table) {
            Schema::dropIfExists($table);
        }
        // Address is retained rather than discarded during an empty-order rollback.
    }
};

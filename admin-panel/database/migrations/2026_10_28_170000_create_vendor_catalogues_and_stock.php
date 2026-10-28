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
        Schema::create('vendor_categories', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('key', 40)->collation('utf8mb4_bin');
            $t->string('name', 100);
            $t->string('status', 12);
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'key']);
        });
        DB::statement("ALTER TABLE vendor_categories ADD CONSTRAINT vendor_category_shape CHECK (`key` REGEXP '^[a-z][a-z0-9_-]{0,39}$' AND CHAR_LENGTH(TRIM(name))>0 AND status IN ('active','archived') AND revision>0)");
        Schema::create('vendors', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('ledger_account_id');
            $t->unsignedBigInteger('category_id');
            $t->foreign(['school_id', 'ledger_account_id'])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
            $t->foreign(['school_id', 'category_id'])->references(['school_id', 'id'])->on('vendor_categories')->restrictOnDelete();
            $t->string('name', 160);
            $t->string('legal_name', 160)->nullable();
            $t->text('description')->nullable();
            $t->string('kind', 16);
            $t->string('status', 12);
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->boolean('vat_registered')->nullable();
            $t->string('vat_number', 40)->nullable();
            $t->string('refund_bearer', 12)->nullable();
            $t->string('chargeback_bearer', 12)->nullable();
            $t->string('settlement_method', 20)->nullable();
            $t->string('settlement_cycle', 12)->nullable();
            $t->unsignedInteger('settlement_delay_days')->nullable();
            $t->text('settlement_terms')->nullable();
            $t->text('private_bank_instructions')->nullable();
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'ledger_account_id']);
            $t->unique(['school_id', 'operation_id']);
        });
        DB::statement("ALTER TABLE vendors ADD CONSTRAINT vendor_shape CHECK (CHAR_LENGTH(TRIM(name))>0 AND kind IN ('school','independent') AND status IN ('draft','active','archived') AND currency REGEXP '^[A-Z]{3}$' AND revision>0 AND (refund_bearer IS NULL OR refund_bearer IN ('school','vendor')) AND (chargeback_bearer IS NULL OR chargeback_bearer IN ('school','vendor')) AND (settlement_method IS NULL OR settlement_method IN ('school_internal','cash','eft')) AND (settlement_cycle IS NULL OR settlement_cycle IN ('daily','weekly','monthly','manual')) AND (settlement_delay_days IS NULL OR settlement_delay_days<=90) AND (status<>'active' OR (legal_name IS NOT NULL AND CHAR_LENGTH(TRIM(legal_name))>0 AND vat_registered IN (0,1) AND vat_registered IS NOT NULL AND (vat_registered=0 OR (vat_number IS NOT NULL AND CHAR_LENGTH(TRIM(vat_number))>0)) AND refund_bearer IS NOT NULL AND chargeback_bearer IS NOT NULL AND settlement_method IS NOT NULL AND settlement_cycle IS NOT NULL AND settlement_delay_days IS NOT NULL AND settlement_terms IS NOT NULL AND CHAR_LENGTH(TRIM(settlement_terms))>=10 AND ((kind='school' AND settlement_method='school_internal') OR (kind='independent' AND settlement_method IN ('cash','eft'))) AND (settlement_method<>'eft' OR (private_bank_instructions IS NOT NULL AND CHAR_LENGTH(private_bank_instructions)>0)))))");
        DB::unprepared("CREATE TRIGGER vendor_insert BEFORE INSERT ON vendors FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM ledger_accounts a JOIN wallet_settings s ON s.school_id=a.school_id AND s.currency=a.currency WHERE a.school_id=NEW.school_id AND a.id=NEW.ledger_account_id AND a.type='vendor' AND a.module='vendors' AND a.owner_user_id IS NULL AND a.currency=NEW.currency AND a.balance_cents=0 AND a.reference=CONCAT('vendors:merchant:',NEW.operation_id,':',NEW.currency)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor starts with its exact zero-balance ledger identity and configured school currency'; END IF; END");
        Schema::create('vendor_products', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('vendor_id');
            $t->unsignedBigInteger('category_id');
            $t->foreign(['school_id', 'vendor_id'])->references(['school_id', 'id'])->on('vendors')->restrictOnDelete();
            $t->foreign(['school_id', 'category_id'])->references(['school_id', 'id'])->on('vendor_categories')->restrictOnDelete();
            $t->string('sku', 64)->collation('utf8mb4_bin');
            $t->string('name', 160);
            $t->text('description')->nullable();
            $t->string('unit_label', 40);
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('price_cents');
            $t->string('vat_kind', 12);
            $t->unsignedInteger('vat_rate_bps');
            $t->boolean('tracks_stock');
            $t->string('status', 12);
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'vendor_id', 'sku']);
        });
        DB::statement("ALTER TABLE vendor_products ADD CONSTRAINT vendor_product_shape CHECK (sku REGEXP '^[A-Z0-9][A-Z0-9._/-]{0,63}$' AND CHAR_LENGTH(TRIM(name))>0 AND CHAR_LENGTH(TRIM(unit_label))>0 AND price_cents BETWEEN 0 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$' AND status IN ('draft','active','archived') AND tracks_stock IN (0,1) AND revision>0 AND ((vat_kind IN ('none','zero','exempt') AND vat_rate_bps=0) OR (vat_kind='standard' AND vat_rate_bps BETWEEN 1 AND 10000)))");
        DB::unprepared("CREATE TRIGGER vendor_product_insert BEFORE INSERT ON vendor_products FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM vendors WHERE school_id=NEW.school_id AND id=NEW.vendor_id AND currency=NEW.currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Product retains its own vendor and currency'; END IF; END");
        Schema::create('vendor_stock_balances', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('product_id');
            $t->foreign(['school_id', 'product_id'])->references(['school_id', 'id'])->on('vendor_products')->restrictOnDelete();
            $t->bigInteger('on_hand');
            $t->unsignedInteger('revision');
            $t->unique(['school_id', 'product_id']);
        });
        DB::statement('ALTER TABLE vendor_stock_balances ADD CONSTRAINT vendor_stock_balance_shape CHECK (on_hand BETWEEN 0 AND 1000000000)');
        Schema::create('vendor_stock_movements', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('product_id');
            $t->foreign(['school_id', 'product_id'])->references(['school_id', 'id'])->on('vendor_products')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->string('kind', 12);
            $t->bigInteger('units_delta');
            $t->bigInteger('before_units');
            $t->bigInteger('after_units');
            $t->text('private_reason');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'product_id', 'source_reference'], 'vendor_stock_source_unique');
        });
        DB::statement("ALTER TABLE vendor_stock_movements ADD CONSTRAINT vendor_stock_movement_shape CHECK (units_delta BETWEEN -1000000000 AND 1000000000 AND units_delta<>0 AND before_units BETWEEN 0 AND 1000000000 AND after_units BETWEEN 0 AND 1000000000 AND after_units=before_units+units_delta AND kind IN ('receipt','correction','waste') AND (kind<>'receipt' OR units_delta>0) AND (kind<>'waste' OR units_delta<0) AND source_reference REGEXP '^[A-Z0-9][A-Z0-9 ._:/-]{0,95}$' AND source_reference=UPPER(TRIM(source_reference)) AND LOCATE('  ',source_reference)=0 AND CHAR_LENGTH(private_reason)>0)");
        DB::unprepared("CREATE TRIGGER vendor_stock_balance_insert BEFORE INSERT ON vendor_stock_balances FOR EACH ROW BEGIN IF NEW.on_hand<>0 OR NEW.revision<>0 OR NOT EXISTS(SELECT 1 FROM vendor_products WHERE school_id=NEW.school_id AND id=NEW.product_id AND tracks_stock=1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Tracked product stock starts at zero'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_stock_movement_insert BEFORE INSERT ON vendor_stock_movements FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM vendor_products p JOIN vendor_stock_balances b ON b.school_id=p.school_id AND b.product_id=p.id WHERE p.school_id=NEW.school_id AND p.id=NEW.product_id AND p.tracks_stock=1 AND b.on_hand=NEW.before_units) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stock movement retains its exact original product and projection'; END IF; END");
        DB::unprepared('CREATE TRIGGER vendor_stock_movement_project AFTER INSERT ON vendor_stock_movements FOR EACH ROW UPDATE vendor_stock_balances SET on_hand=NEW.after_units,revision=revision+1 WHERE school_id=NEW.school_id AND product_id=NEW.product_id');
        DB::unprepared("CREATE TRIGGER vendor_stock_balance_update BEFORE UPDATE ON vendor_stock_balances FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.product_id<>OLD.product_id OR NEW.revision<>OLD.revision+1 OR NEW.on_hand<>(SELECT COALESCE(SUM(units_delta),0) FROM vendor_stock_movements WHERE school_id=OLD.school_id AND product_id=OLD.product_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stock balance is a checked immutable-history projection'; END IF; END");
        Schema::create('vendor_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->json('receipt');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'operation_id']);
        });
        DB::unprepared("CREATE TRIGGER vendor_category_update BEFORE UPDATE ON vendor_categories FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.`key`<>OLD.`key` OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor category retains its key, school and ordered revision'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_update BEFORE UPDATE ON vendors FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.operation_id<>OLD.operation_id OR NEW.ledger_account_id<>OLD.ledger_account_id OR NEW.currency<>OLD.currency OR NEW.kind<>OLD.kind OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor retains its original ownership kind, ledger, currency and ordered revision'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_product_update BEFORE UPDATE ON vendor_products FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.vendor_id<>OLD.vendor_id OR NEW.currency<>OLD.currency OR NEW.sku<>OLD.sku OR NEW.tracks_stock<>OLD.tracks_stock OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Product retains its vendor, currency, SKU, stock mode and ordered revision'; END IF; END");
        foreach (['vendor_categories', 'vendors', 'vendor_products', 'vendor_stock_balances', 'vendor_stock_movements', 'vendor_operations'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_delete BEFORE DELETE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor catalogue and stock history is retained'");
        }
        foreach (['vendor_stock_movements', 'vendor_operations'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_update BEFORE UPDATE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor operation and stock receipts are immutable'");
        }
        $permission = DB::table('permissions')->where('name', 'vendors-manage')->where('guard_name', 'web')->value('id');
        $permissionId = $permission ?? DB::table('permissions')->insertGetId(['name' => 'vendors-manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        foreach (DB::table('roles')->where('name', 'Super Admin')->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        foreach (['vendor_categories', 'vendors', 'vendor_products', 'vendor_stock_balances', 'vendor_stock_movements', 'vendor_operations'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain vendor identities, catalogue and stock history.');
            }
        }
        foreach (['vendor_operations', 'vendor_stock_movements', 'vendor_stock_balances', 'vendor_products', 'vendors', 'vendor_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

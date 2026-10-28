<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashup_counts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->date('counted_on');
            $t->unsignedInteger('revision');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->char('source_hash', 64)->collation('utf8mb4_bin');
            foreach (['opening_cents', 'receipts_cents', 'payouts_cents', 'closing_cents', 'counted_cents', 'variance_cents'] as $c) {
                $t->bigInteger($c);
            }
            $t->text('private_evidence');
            $t->text('private_reason')->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'operator_id', 'currency', 'counted_on', 'revision'], 'cashup_count_revision');
        });
        DB::statement("ALTER TABLE cashup_counts ADD CONSTRAINT cashup_count_shape CHECK (currency REGEXP '^[A-Z]{3}$' AND revision>0 AND receipts_cents>=0 AND payouts_cents>=0 AND counted_cents BETWEEN 0 AND 100000000000 AND closing_cents=opening_cents+receipts_cents-payouts_cents AND variance_cents=counted_cents-closing_cents AND CHAR_LENGTH(source_hash)=64 AND CHAR_LENGTH(request_hash)=64 AND CHAR_LENGTH(private_evidence)>0 AND ((variance_cents=0 AND revision=1) OR (private_reason IS NOT NULL AND CHAR_LENGTH(private_reason)>0)))");
        DB::unprepared("CREATE TRIGGER cashup_count_sequence BEFORE INSERT ON cashup_counts FOR EACH ROW BEGIN IF NEW.revision<>(SELECT COUNT(*)+1 FROM cashup_counts WHERE school_id=NEW.school_id AND operator_id=NEW.operator_id AND currency=NEW.currency AND counted_on=NEW.counted_on) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cash counts retain ordered revisions and original evidence'; END IF; END");
        foreach (['UPDATE', 'DELETE'] as $event) {
            DB::unprepared('CREATE TRIGGER cashup_count_no_'.strtolower($event).' BEFORE '.$event." ON cashup_counts FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cash count history is immutable'");
        }
        $permission = Permission::firstOrCreate(['name' => 'money-cashup-manage', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (DB::table('roles')->where('name', 'Super Admin')->where('guard_name', 'web')->where(fn ($q) => $q->where('custom_role', 0)->orWhereNull('custom_role'))->pluck('id') as $id) {
            DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $id, 'permission_id' => $permission->id]);
        }
    }

    public function down(): void
    {
        if (DB::table('cashup_counts')->exists()) {
            throw new RuntimeException('Retain original physical cash count history.');
        }Schema::drop('cashup_counts');
    }
};

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
        Schema::create('vendor_operators', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('vendor_id');
            $t->foreign(['school_id', 'vendor_id'])->references(['school_id', 'id'])->on('vendors')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'vendor_id', 'user_id'], 'vendor_operator_identity');
            $t->index(['school_id', 'user_id'], 'vendor_operator_user');
        });
        Schema::create('vendor_operator_actions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('operator_id');
            $t->foreign(['school_id', 'operator_id'])->references(['school_id', 'id'])->on('vendor_operators')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('status', 12);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->text('private_reason');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operator_id', 'revision'], 'vendor_operator_action_revision');
            $t->unique(['school_id', 'operation_id'], 'vendor_operator_action_operation');
        });
        DB::statement("ALTER TABLE vendor_operator_actions ADD CONSTRAINT vendor_operator_action_shape CHECK (revision>0 AND status IN ('active','revoked') AND CHAR_LENGTH(private_reason)>0)");
        DB::unprepared("CREATE TRIGGER vendor_operator_action_sequence BEFORE INSERT ON vendor_operator_actions FOR EACH ROW BEGIN IF NEW.revision<>(SELECT COUNT(*)+1 FROM vendor_operator_actions WHERE school_id=NEW.school_id AND operator_id=NEW.operator_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor access changes retain every original revision'; END IF; END");
        foreach (['vendor_operators', 'vendor_operator_actions'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_delete BEFORE DELETE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor operator access history is retained'");
            DB::unprepared("CREATE TRIGGER {$table}_no_update BEFORE UPDATE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Vendor operator access history is immutable'");
        }
    }

    public function down(): void
    {
        if (DB::table('vendor_operators')->exists() || DB::table('vendor_operator_actions')->exists()) {
            throw new RuntimeException('Retain original vendor operator access history.');
        }
        Schema::drop('vendor_operator_actions');
        Schema::drop('vendor_operators');
    }
};

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
        Schema::create('fee_lifecycle_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('session_year_id');
            $t->string('join_basis', 16)->collation('utf8mb4_bin');
            $t->string('leaver_basis', 16)->collation('utf8mb4_bin');
            $t->string('installments', 20)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'session_year_id']);
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE fee_lifecycle_policies ADD CONSTRAINT fee_lifecycle_policy_shape CHECK (revision>0 AND join_basis IN ('daily','calendar_month','full_year') AND leaver_basis IN ('daily','calendar_month','no_credit') AND installments IN ('preserve_dates','remaining_dates'))");
        DB::unprepared("CREATE TRIGGER fee_lifecycle_policy_update BEFORE UPDATE ON fee_lifecycle_policies FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.session_year_id<>OLD.session_year_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee lifecycle policy identity and revisions are retained'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_lifecycle_policy_no_delete BEFORE DELETE ON fee_lifecycle_policies FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain explicit school fee lifecycle policies'");
    }

    public function down(): void
    {
        if (DB::table('fee_lifecycle_policies')->exists()) {
            throw new RuntimeException('Retain school fee lifecycle policies and original billed snapshots.');
        }
        Schema::dropIfExists('fee_lifecycle_policies');
    }
};

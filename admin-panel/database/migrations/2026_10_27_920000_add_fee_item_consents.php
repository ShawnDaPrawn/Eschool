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
        Schema::table('fee_item_eligibility_rules', fn (Blueprint $t) => $t->string('opt_in_billing', 20)->nullable()->collation('utf8mb4_bin'));
        $this->modes(true);
        Schema::create('fee_item_consents', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_item_id');
            $t->unsignedBigInteger('fee_account_id');
            $t->unsignedBigInteger('session_year_id');
            $t->unsignedBigInteger('learner_id')->nullable();
            $t->string('scope', 12)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('target_id');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->boolean('active');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('terms_hash', 64)->collation('utf8mb4_bin');
            $t->json('snapshot');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'fee_item_id', 'fee_account_id', 'session_year_id', 'scope', 'target_id', 'actor_id', 'revision'], 'fee_item_consent_revision_once');
            foreach (['fee_item_id' => 'fee_items', 'fee_account_id' => 'fee_accounts', 'session_year_id' => 'session_years', 'learner_id' => 'students'] as $column => $parent) {
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE fee_item_consents ADD CONSTRAINT fee_item_consent_shape CHECK (revision>0 AND scope IN ('learner','family') AND ((scope='family' AND learner_id IS NULL AND target_id=fee_account_id) OR (scope='learner' AND learner_id IS NOT NULL AND target_id=learner_id)) AND terms_hash REGEXP '^[a-f0-9]{64}$' AND JSON_TYPE(snapshot)='OBJECT')");
        DB::unprepared("CREATE TRIGGER fee_item_consent_insert BEFORE INSERT ON fee_item_consents FOR EACH ROW BEGIN
            DECLARE previous_revision BIGINT;
            SELECT COALESCE(MAX(revision),0) INTO previous_revision FROM fee_item_consents WHERE school_id=NEW.school_id AND fee_item_id=NEW.fee_item_id AND fee_account_id=NEW.fee_account_id AND session_year_id=NEW.session_year_id AND scope=NEW.scope AND target_id=NEW.target_id AND actor_id=NEW.actor_id;
            IF NEW.revision<>previous_revision+1 OR NOT EXISTS(SELECT 1 FROM fee_items WHERE school_id=NEW.school_id AND id=NEW.fee_item_id AND scope=NEW.scope) OR NOT EXISTS(SELECT 1 FROM fee_account_holders WHERE school_id=NEW.school_id AND fee_account_id=NEW.fee_account_id AND session_year_id=NEW.session_year_id AND user_id=NEW.actor_id) OR (NEW.scope='learner' AND NOT EXISTS(SELECT 1 FROM fee_account_learners WHERE school_id=NEW.school_id AND fee_account_id=NEW.fee_account_id AND session_year_id=NEW.session_year_id AND learner_id=NEW.learner_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Consent must retain its own current payer and target revision'; END IF; END");
        foreach (['UPDATE', 'DELETE'] as $action) {
            DB::unprepared('CREATE TRIGGER fee_item_consent_no_'.strtolower($action)." BEFORE $action ON fee_item_consents FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item consent history is immutable'");
        }
        DB::unprepared("CREATE TRIGGER fee_item_consented_identity BEFORE UPDATE ON fee_items FOR EACH ROW BEGIN IF (NEW.scope<>OLD.scope OR NEW.currency<>OLD.currency) AND EXISTS(SELECT 1 FROM fee_item_consents WHERE school_id=OLD.school_id AND fee_item_id=OLD.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='An agreed item retains its scope and currency'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_item_consented_mode BEFORE UPDATE ON fee_item_eligibility_rules FOR EACH ROW BEGIN IF NEW.mode<>'opt_in' AND EXISTS(SELECT 1 FROM fee_item_consents WHERE school_id=OLD.school_id AND fee_item_id=OLD.fee_item_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='An agreed optional item retains its approval requirement'; END IF; END");
    }

    private function modes(bool $optIn): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE fee_item_eligibility_rules DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' fee_item_eligibility_shape');
        $modes = $optIn ? "'manual','compulsory','membership','opt_in'" : "'manual','compulsory','membership'";
        $billing = $optIn ? " AND ((mode='opt_in' AND opt_in_billing IS NOT NULL AND opt_in_billing IN ('once_per_year','per_occurrence')) OR (mode<>'opt_in' AND opt_in_billing IS NULL))" : '';
        DB::statement("ALTER TABLE fee_item_eligibility_rules ADD CONSTRAINT fee_item_eligibility_shape CHECK (revision>0 AND mode IN ($modes) AND target_scope IN ('all','grade','class') AND ((target_scope='all' AND target_id IS NULL) OR (target_scope<>'all' AND target_id IS NOT NULL)) AND JSON_TYPE(applies_to)='ARRAY' AND JSON_LENGTH(applies_to)<=20 AND (mode<>'manual' OR (target_scope='all' AND JSON_LENGTH(applies_to)=0)) AND ((mode='membership' AND (activity_group_id IS NOT NULL)+(teaching_group_id IS NOT NULL)+(subject_id IS NOT NULL)=1) OR (mode<>'membership' AND activity_group_id IS NULL AND teaching_group_id IS NULL AND subject_id IS NULL)) $billing)");
    }

    public function down(): void
    {
        if (DB::table('fee_item_consents')->exists() || DB::table('fee_item_eligibility_rules')->where('mode', 'opt_in')->exists()) {
            throw new RuntimeException('Retain parent opt-in agreements and configured item rules.');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS fee_item_consented_mode');
        DB::unprepared('DROP TRIGGER IF EXISTS fee_item_consented_identity');
        Schema::dropIfExists('fee_item_consents');
        $this->modes(false);
        Schema::table('fee_item_eligibility_rules', fn (Blueprint $t) => $t->dropColumn('opt_in_billing'));
    }
};

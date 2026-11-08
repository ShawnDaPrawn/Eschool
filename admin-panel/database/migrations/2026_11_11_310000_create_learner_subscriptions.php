<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Learner subscriptions for the paid Mastery features (Action Plan 3.20) and
 * their revenue (SA.8, Build 3). engineering/specs/platform/learner-subscriptions.md.
 *
 * School-owned: each subscription is about one school's learner, the
 * school's licence or a sponsor's places at that school, so it carries the
 * school. Its payments, sponsored learners, audit trail and prompts carry the
 * school too, with composite foreign keys so a row can never point into
 * another school.
 *
 * Global: platform_bank_receipts is Navigate Ed's own register of outside
 * receipts (one bank line or gateway charge, one receipt) across every school,
 * filled only by the payment trigger, like the school ledger's
 * financial_receipts registry. subscription_gateway_events records each
 * gateway notice once. Neither names a school or a person.
 *
 * Money is integer cents. Payments, receipts and the audit trail are never
 * changed or deleted (database triggers), and the rollback refuses while any
 * of these tables holds rows.
 */
return new class extends Migration
{
    private const TABLES = [
        'subscription_gateway_events', 'learner_subscription_prompts', 'learner_subscription_events',
        'platform_bank_receipts', 'learner_subscription_payments', 'learner_subscription_members', 'learner_subscriptions',
    ];

    public function up(): void
    {
        Schema::create('learner_subscriptions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('reference', 24)->unique()->comment('the reference a family or sponsor quotes on their EFT, NES-000001');
            $t->string('plan', 16)->comment('per_child, family, school_licence or sponsored');
            $t->unsignedBigInteger('learner_id')->nullable()->comment('per_child and family only');
            $t->foreignId('guardian_user_id')->nullable()->comment('the guardian who asked, per_child and family only')->constrained('users')->restrictOnDelete();
            $t->string('sponsor_name', 120)->nullable()->comment('an organisation, never a person');
            $t->unsignedSmallInteger('places')->nullable()->comment('sponsored places at the school');
            $t->string('status', 12)->default('requested')->comment('requested, active, cancelled, ended or declined');
            $t->bigInteger('price_cents')->comment('what one period costs, fixed when the subscription was asked for');
            $t->char('currency', 3);
            $t->unsignedTinyInteger('period_months')->default(1);
            $t->string('provider', 32)->default('manual')->comment('manual (EFT recorded by HQ Finance) or a gateway key');
            $t->string('provider_ref', 96)->nullable()->comment('the gateway\'s own id for this subscription');
            $t->date('current_period_start')->nullable();
            $t->date('current_period_end')->nullable()->comment('the last day included');
            $t->uuid('request_key')->nullable()->comment('the guardian\'s operation id, so a retried request returns the same subscription');
            $t->uuid('cancel_key')->nullable();
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('activated_at')->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->dateTime('closed_at')->nullable()->comment('declined or ended by HQ');
            $t->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('close_reason', 500)->nullable();
            $t->unsignedBigInteger('open_learner')->nullable()->storedAs("IF(status IN ('requested','active') AND plan IN ('per_child','family'), learner_id, NULL)");
            $t->unsignedTinyInteger('open_licence')->nullable()->storedAs("IF(status IN ('requested','active') AND plan = 'school_licence', 1, NULL)");
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'open_learner'], 'learner_subscription_one_open_per_learner');
            $t->unique(['school_id', 'open_licence'], 'learner_subscription_one_open_licence');
            $t->unique(['school_id', 'guardian_user_id', 'request_key'], 'learner_subscription_request_key');
            $t->index(['school_id', 'status', 'current_period_end']);
            $t->index(['school_id', 'guardian_user_id']);
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE learner_subscriptions ADD CONSTRAINT learner_subscription_shape CHECK (
            plan IN ('per_child','family','school_licence','sponsored') AND status IN ('requested','active','cancelled','ended','declined')
            AND price_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$' AND period_months BETWEEN 1 AND 12
            AND reference REGEXP '^[A-Z]{2,8}-[0-9]{6,12}$'
            AND ((plan IN ('per_child','family') AND learner_id IS NOT NULL AND guardian_user_id IS NOT NULL AND places IS NULL AND sponsor_name IS NULL)
              OR (plan = 'school_licence' AND learner_id IS NULL AND guardian_user_id IS NULL AND places IS NULL AND sponsor_name IS NULL)
              OR (plan = 'sponsored' AND learner_id IS NULL AND guardian_user_id IS NULL AND places BETWEEN 1 AND 10000 AND CHAR_LENGTH(sponsor_name) > 1))
            AND (status <> 'requested' OR (current_period_end IS NULL AND activated_at IS NULL))
            AND (status NOT IN ('active','ended') OR (current_period_start IS NOT NULL AND current_period_end >= current_period_start AND activated_at IS NOT NULL))
            AND (status <> 'cancelled' OR cancelled_at IS NOT NULL)
            AND (status NOT IN ('ended','declined') OR (closed_at IS NOT NULL AND CHAR_LENGTH(close_reason) >= 10)))");

        Schema::create('learner_subscription_members', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('subscription_id');
            $t->unsignedBigInteger('learner_id');
            $t->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('added_at');
            $t->dateTime('removed_at')->nullable();
            $t->foreignId('removed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('active_learner')->nullable()->storedAs('IF(removed_at IS NULL, learner_id, NULL)');
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'subscription_id', 'active_learner'], 'learner_subscription_member_once');
            $t->index(['school_id', 'learner_id']);
            $t->foreign(['school_id', 'subscription_id'])->references(['school_id', 'id'])->on('learner_subscriptions')->restrictOnDelete();
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
        });

        Schema::create('learner_subscription_payments', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('subscription_id');
            $t->string('method', 12)->comment('eft (recorded by HQ Finance) or gateway');
            $t->string('provider', 32);
            $t->string('source_reference', 96)->collation('utf8mb4_bin')->comment('the bank line reference, or provider:charge id');
            $t->bigInteger('amount_cents');
            $t->char('currency', 3);
            $t->date('received_on');
            $t->date('period_start');
            $t->date('period_end');
            $t->foreignId('recorded_by')->nullable()->comment('HQ Finance; null for a gateway notice')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'subscription_id', 'period_start'], 'learner_subscription_payment_period');
            $t->foreign(['school_id', 'subscription_id'])->references(['school_id', 'id'])->on('learner_subscriptions')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE learner_subscription_payments ADD CONSTRAINT learner_subscription_payment_shape CHECK (
            method IN ('eft','gateway') AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$' AND period_end >= period_start
            AND source_reference REGEXP '^[A-Z0-9][A-Z0-9 ._:/#-]{1,95}$' AND source_reference = UPPER(TRIM(source_reference)) AND LOCATE('  ', source_reference) = 0
            AND ((method = 'eft' AND provider = 'manual' AND recorded_by IS NOT NULL) OR (method = 'gateway' AND provider <> 'manual')))");

        Schema::create('platform_bank_receipts', function (Blueprint $t): void {
            $t->id();
            $t->string('method', 12);
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->string('domain', 16)->comment('subscription');
            $t->unsignedBigInteger('record_id');
            $t->bigInteger('amount_cents');
            $t->char('currency', 3);
            $t->dateTime('created_at', 6)->useCurrent();
            $t->unique(['method', 'source_reference'], 'platform_bank_receipt_source');
            $t->unique(['domain', 'record_id'], 'platform_bank_receipt_record');
        });
        DB::statement("ALTER TABLE platform_bank_receipts ADD CONSTRAINT platform_bank_receipt_shape CHECK (method IN ('eft','gateway') AND domain IN ('subscription') AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$')");

        Schema::create('learner_subscription_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('subscription_id');
            $t->string('event', 24)->comment('requested, activated, renewed, cancelled, declined, ended, member_added, member_removed');
            $t->string('actor_kind', 12)->comment('guardian, hq, school or gateway');
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('status_after', 12);
            $t->bigInteger('amount_cents')->nullable();
            $t->json('detail')->nullable()->comment('ids, dates and references only, never a name');
            $t->dateTime('created_at', 6);
            $t->index(['school_id', 'subscription_id']);
            $t->index(['school_id', 'event', 'created_at']);
            $t->foreign(['school_id', 'subscription_id'])->references(['school_id', 'id'])->on('learner_subscriptions')->restrictOnDelete();
        });

        Schema::create('learner_subscription_prompts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('learner_id');
            $t->string('reason', 16)->comment('frequency or scores_fell; the notice itself never says which');
            $t->unsignedBigInteger('notification_id')->nullable();
            $t->date('sent_on');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'guardian_user_id', 'learner_id', 'sent_on'], 'learner_subscription_prompt_daily');
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
        });

        Schema::create('subscription_gateway_events', function (Blueprint $t): void {
            $t->id();
            $t->string('provider', 32);
            $t->string('event_id', 128)->collation('utf8mb4_bin');
            $t->string('type', 32);
            $t->string('reference', 24)->nullable();
            $t->string('outcome', 24);
            $t->dateTime('received_at', 6);
            $t->unique(['provider', 'event_id'], 'subscription_gateway_event_once');
        });

        // One bank line or gateway charge settles one subscription period, once,
        // across every school (Navigate Ed has one bank account).
        DB::unprepared("CREATE TRIGGER platform_bank_receipt_insert BEFORE INSERT ON platform_bank_receipts FOR EACH ROW BEGIN
            IF NEW.domain = 'subscription' AND NOT EXISTS(SELECT 1 FROM learner_subscription_payments WHERE id = NEW.record_id AND method = NEW.method AND source_reference = NEW.source_reference AND amount_cents = NEW.amount_cents AND currency = NEW.currency) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A platform receipt names its original immutable payment';
            END IF;
        END");
        DB::unprepared("CREATE TRIGGER learner_subscription_payment_receipt AFTER INSERT ON learner_subscription_payments FOR EACH ROW
            INSERT INTO platform_bank_receipts(method, source_reference, domain, record_id, amount_cents, currency, created_at)
            VALUES(NEW.method, NEW.source_reference, 'subscription', NEW.id, NEW.amount_cents, NEW.currency, NEW.created_at)");
        foreach (['learner_subscription_payments', 'platform_bank_receipts', 'learner_subscription_events'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Subscription payments, receipts and their audit trail are immutable'");
            }
        }
        DB::unprepared("CREATE TRIGGER learner_subscription_no_delete BEFORE DELETE ON learner_subscriptions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Subscriptions are retained; cancel, decline or end them'");
        DB::unprepared("CREATE TRIGGER learner_subscription_update BEFORE UPDATE ON learner_subscriptions FOR EACH ROW BEGIN
            IF NEW.id <> OLD.id OR NEW.school_id <> OLD.school_id OR NEW.reference <> OLD.reference OR NEW.plan <> OLD.plan
                OR NOT (NEW.learner_id <=> OLD.learner_id) OR NOT (NEW.guardian_user_id <=> OLD.guardian_user_id) OR NEW.price_cents <> OLD.price_cents OR NEW.currency <> OLD.currency
                OR (OLD.status IN ('ended','declined') AND NEW.status <> OLD.status)
                OR (OLD.status = 'cancelled' AND NEW.status NOT IN ('cancelled','ended'))
                OR (NEW.status = 'requested' AND OLD.status <> 'requested')
                OR (NOT (OLD.current_period_end <=> NEW.current_period_end) AND OLD.current_period_end IS NOT NULL AND (NEW.current_period_end IS NULL OR NEW.current_period_end < OLD.current_period_end)) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A subscription keeps its identity, price and paid periods, and its status only moves forward';
            END IF;
        END");
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Retain learner subscriptions, their payments, receipts and audit trail.');
            }
        }
        foreach (['platform_bank_receipt_insert', 'learner_subscription_payment_receipt', 'learner_subscription_no_delete', 'learner_subscription_update'] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
        foreach (['learner_subscription_payments', 'platform_bank_receipts', 'learner_subscription_events'] as $table) {
            foreach (['update', 'delete'] as $action) {
                DB::unprepared('DROP TRIGGER IF EXISTS '.$table.'_no_'.$action);
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};

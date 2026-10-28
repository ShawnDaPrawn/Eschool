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
        Schema::create('ticket_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('name', 191);
            $t->text('description')->nullable();
            $t->string('status', 12);
            foreach (['sales_from', 'sales_until', 'starts_at', 'ends_at'] as $c) {
                $t->dateTime($c, 6);
            }
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
        });
        Schema::create('ticket_types', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('event_id');
            $t->unsignedBigInteger('product_id');
            foreach (['event_id' => 'ticket_events', 'product_id' => 'vendor_products'] as $c => $table) {
                $t->foreign(['school_id', $c])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->string('kind', 8);
            $t->string('status', 12);
            $t->string('transfer_rule', 16);
            $t->unsignedInteger('capacity');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'product_id']);
        });
        Schema::create('ticket_catalogue_revisions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('resource_type', 8);
            $t->unsignedBigInteger('record_id');
            $t->unsignedInteger('revision');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->json('snapshot');
            $t->text('private_reason');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'resource_type', 'record_id', 'revision'], 'ticket_catalogue_revision_identity');
        });
        Schema::create('tickets', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('type_id');
            $t->unsignedBigInteger('order_line_id');
            $t->unsignedInteger('ordinal');
            $t->foreignId('original_holder_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('named_learner_id')->nullable();
            $t->json('snapshot');
            $t->text('private_qr_token');
            $t->char('qr_hash', 64)->collation('utf8mb4_bin');
            $t->text('private_number');
            $t->char('number_hash', 64)->collation('utf8mb4_bin');
            $t->dateTime('created_at', 6);
            foreach (['type_id' => 'ticket_types', 'order_line_id' => 'vendor_order_lines', 'named_learner_id' => 'students'] as $c => $table) {
                $t->foreign(['school_id', $c])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'order_line_id', 'ordinal']);
            $t->unique(['school_id', 'qr_hash']);
            $t->unique(['school_id', 'number_hash']);
            $t->index(['school_id', 'type_id']);
        });
        Schema::create('ticket_actions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('ticket_id');
            $t->foreign(['school_id', 'ticket_id'])->references(['school_id', 'id'])->on('tickets')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->string('action', 12);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('from_holder_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('to_holder_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->unsignedTinyInteger('redemption_marker')->nullable();
            foreach (['private_qr_token', 'private_number'] as $c) {
                $t->text($c)->nullable();
            }
            foreach (['qr_hash', 'number_hash'] as $c) {
                $t->char($c, 64)->nullable()->collation('utf8mb4_bin');
            }
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'ticket_id', 'revision']);
            $t->unique(['school_id', 'ticket_id', 'redemption_marker']);
            $t->index(['school_id', 'to_holder_id']);
            $t->unique(['school_id', 'qr_hash']);
            $t->unique(['school_id', 'number_hash']);
        });
        DB::statement("ALTER TABLE ticket_events ADD CONSTRAINT ticket_event_shape CHECK (revision>0 AND status IN ('draft','active','paused','cancelled') AND sales_from<sales_until AND sales_until<=ends_at AND starts_at<ends_at)");
        DB::statement("ALTER TABLE ticket_types ADD CONSTRAINT ticket_type_shape CHECK (revision>0 AND capacity BETWEEN 1 AND 1000000 AND kind IN ('general','named') AND status IN ('draft','active','paused') AND transfer_rule IN ('none','families','wallet_profiles'))");
        DB::statement("ALTER TABLE ticket_catalogue_revisions ADD CONSTRAINT ticket_catalogue_revision_shape CHECK(resource_type IN ('event','type') AND revision>0 AND CHAR_LENGTH(private_reason)>0)");
        DB::unprepared("CREATE TRIGGER ticket_catalogue_revision_insert BEFORE INSERT ON ticket_catalogue_revisions FOR EACH ROW BEGIN IF NEW.revision<>(SELECT COUNT(*)+1 FROM ticket_catalogue_revisions WHERE school_id=NEW.school_id AND resource_type=NEW.resource_type AND record_id=NEW.record_id) OR NOT ((NEW.resource_type='event' AND EXISTS(SELECT 1 FROM ticket_events WHERE school_id=NEW.school_id AND id=NEW.record_id AND revision=NEW.revision AND changed_by=NEW.actor_id)) OR (NEW.resource_type='type' AND EXISTS(SELECT 1 FROM ticket_types WHERE school_id=NEW.school_id AND id=NEW.record_id AND revision=NEW.revision AND changed_by=NEW.actor_id))) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain each original catalogue revision with its school and actor'; END IF; END");
        DB::statement('ALTER TABLE tickets ADD CONSTRAINT ticket_ordinal_positive CHECK (ordinal BETWEEN 1 AND 100)');
        DB::statement("ALTER TABLE ticket_actions ADD CONSTRAINT ticket_action_shape CHECK (revision>0 AND ((action='transferred' AND from_holder_id<>to_holder_id AND redemption_marker IS NULL AND private_qr_token IS NOT NULL AND qr_hash IS NOT NULL AND private_number IS NOT NULL AND number_hash IS NOT NULL) OR (action='redeemed' AND from_holder_id=to_holder_id AND redemption_marker=1 AND private_qr_token IS NULL AND qr_hash IS NULL AND private_number IS NULL AND number_hash IS NULL)))");
        DB::unprepared("CREATE TRIGGER ticket_event_revision BEFORE UPDATE ON ticket_events FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 OR OLD.status='cancelled' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain event identity and revision; cancelled events remain cancelled'; END IF; END");
        DB::unprepared("CREATE TRIGGER ticket_type_insert BEFORE INSERT ON ticket_types FOR EACH ROW BEGIN IF NOT EXISTS (SELECT 1 FROM vendor_products p WHERE p.school_id=NEW.school_id AND p.id=NEW.product_id AND p.tracks_stock=0) OR EXISTS (SELECT 1 FROM vendor_order_lines l WHERE l.school_id=NEW.school_id AND l.product_id=NEW.product_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ticket types require a new uncounted vendor product'; END IF; END");
        DB::unprepared("CREATE TRIGGER ticket_type_revision BEFORE UPDATE ON ticket_types FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.product_id<>OLD.product_id OR NEW.event_id<>OLD.event_id OR NEW.kind<>OLD.kind OR NEW.revision<>OLD.revision+1 OR NEW.capacity<(SELECT COUNT(*) FROM tickets WHERE school_id=OLD.school_id AND type_id=OLD.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ticket type retains its original event, product, kind and issued capacity'; END IF; END");
        DB::unprepared("CREATE TRIGGER ticket_original_sale BEFORE INSERT ON tickets FOR EACH ROW BEGIN IF NOT EXISTS (SELECT 1 FROM ticket_types tt JOIN vendor_order_lines l ON l.school_id=tt.school_id AND l.product_id=tt.product_id JOIN vendor_orders o ON o.school_id=l.school_id AND o.id=l.order_id WHERE tt.school_id=NEW.school_id AND tt.id=NEW.type_id AND l.id=NEW.order_line_id AND NEW.ordinal<=l.quantity AND o.wallet_owner_id=NEW.original_holder_id AND o.sealed_at IS NOT NULL AND EXISTS(SELECT 1 FROM vendor_order_actions a WHERE a.school_id=o.school_id AND a.order_id=o.id AND a.action='collected') AND ((tt.kind='general' AND NEW.named_learner_id IS NULL) OR (tt.kind='named' AND o.learner_id=NEW.named_learner_id))) OR (SELECT COUNT(*) FROM tickets WHERE school_id=NEW.school_id AND type_id=NEW.type_id)>=(SELECT capacity FROM ticket_types WHERE school_id=NEW.school_id AND id=NEW.type_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admission ticket retains its original fulfilled sale, holder, learner and capacity'; END IF; END");
        DB::unprepared("CREATE TRIGGER ticket_action_sequence BEFORE INSERT ON ticket_actions FOR EACH ROW BEGIN DECLARE holder BIGINT; DECLARE seq INT DEFAULT 0; SELECT original_holder_id INTO holder FROM tickets WHERE school_id=NEW.school_id AND id=NEW.ticket_id; SELECT COUNT(*) INTO seq FROM ticket_actions WHERE school_id=NEW.school_id AND ticket_id=NEW.ticket_id; IF seq>0 THEN SELECT to_holder_id INTO holder FROM ticket_actions WHERE school_id=NEW.school_id AND ticket_id=NEW.ticket_id ORDER BY revision DESC LIMIT 1; END IF; IF holder IS NULL OR NEW.from_holder_id<>holder OR NEW.revision<>seq+1 OR EXISTS(SELECT 1 FROM ticket_actions WHERE school_id=NEW.school_id AND ticket_id=NEW.ticket_id AND action='redeemed') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ticket action retains current ownership and single use'; END IF; END");
        foreach (['tickets', 'ticket_actions', 'ticket_catalogue_revisions'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $event) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($event).' BEFORE '.$event.' ON '.$table." FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ticket ownership and redemption history is immutable'");
            }
        }
        DB::unprepared("CREATE TRIGGER ticket_product_no_counting BEFORE UPDATE ON vendor_products FOR EACH ROW BEGIN IF NEW.tracks_stock<>0 AND EXISTS(SELECT 1 FROM ticket_types t WHERE t.school_id=NEW.school_id AND t.product_id=NEW.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admission capacity is independent of physical product stock'; END IF; END");
        foreach (['ticket_events', 'ticket_types'] as $table) {
            DB::unprepared('CREATE TRIGGER '.$table.'_no_delete BEFORE DELETE ON '.$table." FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain event and ticket catalogue history'");
        }
    }

    public function down(): void
    {
        foreach (['ticket_events', 'ticket_types', 'tickets', 'ticket_actions', 'ticket_catalogue_revisions'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain event admission and original ticket history.');
            }
        }
        DB::unprepared('DROP TRIGGER IF EXISTS ticket_product_no_counting');
        foreach (['ticket_actions', 'tickets', 'ticket_catalogue_revisions', 'ticket_types', 'ticket_events'] as $table) {
            Schema::drop($table);
        }
    }
};

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
        Schema::create('cashup_count_sources', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('count_id');
            $t->char('source_hash', 64)->collation('utf8mb4_bin');
            $t->json('watermarks');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'count_id']);
            $t->foreign(['school_id', 'count_id'])->references(['school_id', 'id'])->on('cashup_counts')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE cashup_count_sources ADD CONSTRAINT cashup_count_sources_shape CHECK (CHAR_LENGTH(source_hash)=64 AND JSON_TYPE(watermarks)='OBJECT')");
        DB::unprepared("CREATE TRIGGER cashup_count_sources_insert BEFORE INSERT ON cashup_count_sources FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM cashup_counts c WHERE c.school_id=NEW.school_id AND c.id=NEW.count_id AND c.source_hash=NEW.source_hash) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cash source boundary retains the original count fingerprint'; END IF; END");
        foreach (['UPDATE', 'DELETE'] as $event) {
            DB::unprepared('CREATE TRIGGER cashup_count_sources_'.strtolower($event).' BEFORE '.$event." ON cashup_count_sources FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Original cash count source boundaries are immutable'");
        }
    }

    public function down(): void
    {
        if (DB::table('cashup_count_sources')->exists()) {
            throw new RuntimeException('Retain the original cash count source boundaries.');
        }
        Schema::drop('cashup_count_sources');
    }
};

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
        Schema::create('ai_quarantine_snapshots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('ai_usage_id')->constrained('ai_usage')->restrictOnDelete();
            $t->string('stage', 12);
            $t->longText('private_content');
            $t->string('content_hash', 64);
            $t->timestamp('expires_at')->index();
            $t->timestamps();
            $t->unique(['school_id', 'ai_usage_id', 'stage'], 'ai_quarantine_stage');
        });
        Schema::table('ai_flags', function (Blueprint $t) {
            $t->foreignId('quarantine_snapshot_id')->nullable()->constrained('ai_quarantine_snapshots')->restrictOnDelete();
            $t->unique(['school_id', 'quarantine_snapshot_id', 'code'], 'ai_quarantine_flag');
        });
        Schema::table('ai_usage', fn (Blueprint $t) => $t->timestamp('quarantine_purged_at')->nullable());
    }

    public function down(): void
    {
        if (DB::table('ai_quarantine_snapshots')->exists() || DB::table('ai_usage')->whereNotNull('quarantine_purged_at')->exists()) {
            throw new RuntimeException('Quarantine copies and purge tombstones must be retained.');
        }
        Schema::table('ai_flags', function (Blueprint $t) {
            $t->dropUnique('ai_quarantine_flag');
            $t->dropConstrainedForeignId('quarantine_snapshot_id');
        });
        Schema::dropIfExists('ai_quarantine_snapshots');
        Schema::table('ai_usage', fn (Blueprint $t) => $t->dropColumn('quarantine_purged_at'));
    }
};

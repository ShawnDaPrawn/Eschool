<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Everyone who wrote or changed a library version's content (SA.16), so none
 * of them can review it. Existing versions start with their author.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_item_versions', fn (Blueprint $t) => $t->json('editor_ids')->nullable()->after('created_by'));
        DB::table('library_item_versions')->orderBy('id')->get(['id', 'created_by'])
            ->each(fn ($row) => DB::table('library_item_versions')->where('id', $row->id)->update(['editor_ids' => json_encode([(int) $row->created_by])]));
    }

    public function down(): void
    {
        if (DB::table('library_item_versions')->exists()) {
            throw new RuntimeException('Library versions keep who edited them: export them before rolling back.');
        }
        Schema::table('library_item_versions', fn (Blueprint $t) => $t->dropColumn('editor_ids'));
    }
};

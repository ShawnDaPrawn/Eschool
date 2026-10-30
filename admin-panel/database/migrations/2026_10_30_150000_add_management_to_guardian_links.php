<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guardians managed by the school office: where a link came from (the legacy
 * father/mother/guardian fields, or managed directly), and when, by whom and
 * why it ended. Specification: engineering/specs/platform/guardian-management.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardian_links', function (Blueprint $t) {
            $t->string('source', 16)->default('legacy')->after('status');
            $t->timestamp('ended_at')->nullable()->after('source');
            $t->foreignId('ended_by')->nullable()->after('ended_at')->constrained('users')->nullOnDelete();
            $t->string('ended_reason', 500)->nullable()->after('ended_by');
        });
    }

    public function down(): void
    {
        Schema::table('guardian_links', function (Blueprint $t) {
            $t->dropConstrainedForeignId('ended_by');
            $t->dropColumn(['source', 'ended_at', 'ended_reason']);
        });
    }
};

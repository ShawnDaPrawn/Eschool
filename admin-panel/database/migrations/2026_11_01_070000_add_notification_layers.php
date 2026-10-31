<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Three-layer notifications (Action Plan 1.4): a title, a short excerpt for
 * the collapsed inbox row and a full body for the detail view, plus a generic
 * app path the apps open. engineering/specs/notifications/notification-layers.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('excerpt', 140)->nullable()->after('message');
            $table->text('body')->nullable()->after('excerpt');
            $table->string('target', 512)->nullable()->after('body')->comment('App path the notice opens, for example /academics/reports');
        });
    }

    public function down(): void
    {
        // Never throw away written notice text on a rollback.
        if (DB::table('notifications')->whereNotNull('excerpt')->orWhereNotNull('body')->orWhereNotNull('target')->exists()) {
            throw new RuntimeException('Notifications already hold excerpts, bodies or targets; refusing to drop those columns.');
        }
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['excerpt', 'body', 'target']);
        });
    }
};

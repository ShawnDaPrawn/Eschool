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
        Schema::table('school_sending_domains', function (Blueprint $table): void {
            $table->string('dkim_signing_hosted_zone', 253)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('school_sending_domains')->whereNotNull('dkim_signing_hosted_zone')->exists()) {
            throw new LogicException('Retain the DKIM hosted zone while school sending domains are connected.');
        }
        Schema::table('school_sending_domains', function (Blueprint $table): void {
            $table->dropColumn('dkim_signing_hosted_zone');
        });
    }
};

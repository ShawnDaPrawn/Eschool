<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The service_health_logs table was used only by the removed license
     * check. Drop it so existing installs lose the table too.
     */
    public function up(): void
    {
        Schema::dropIfExists('service_health_logs');
    }

    /**
     * Reverse the migrations.
     *
     * Nothing to restore: the table is no longer used by the application.
     */
    public function down(): void
    {
        //
    }
};

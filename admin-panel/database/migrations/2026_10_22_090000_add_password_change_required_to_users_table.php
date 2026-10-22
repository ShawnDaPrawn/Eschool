<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * users.password_change_required marks an account that must choose a new
 * password before carrying on. Learners whose password is still their date of
 * birth are flagged by learners:flag-dob-passwords, queued from here (on the
 * sync connection it runs straight away; on a database queue the worker runs
 * it, since checking every learner's hash takes a while on a large school).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_change_required')->default(false)->after('reset_request');
        });

        Artisan::queue('learners:flag-dob-passwords');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_change_required');
        });
    }
};

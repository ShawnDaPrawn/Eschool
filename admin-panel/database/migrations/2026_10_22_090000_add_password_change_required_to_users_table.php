<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.password_change_required marks an account that must choose a new
 * password before carrying on. Learners whose password is still their date of
 * birth are flagged by learners:flag-dob-passwords. The migration does not
 * queue it: on Cloud Run the web revision migrates before the worker is
 * upgraded, and the old worker does not know the command. The schedule queues
 * it once, on the first night after the release (routes/console.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_change_required')->default(false)->after('reset_request');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_change_required');
        });
    }
};

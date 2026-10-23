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
        foreach (['guardian_invitations', 'learner_invitations'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('email_hash', 64)->nullable());
        }
        // An old invitation does not prove which mailbox received its secret.
        // Do not infer that proof from today's mutable user email.
        DB::table('guardian_invitations')->whereNull('used_at')->update(['used_at' => now()]);
        DB::table('learner_invitations')->where('channel', 'link')->whereNull('used_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    public function down(): void
    {
        foreach (['guardian_invitations', 'learner_invitations'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('email_hash'));
        }
        // A rollback must not reactivate retired credentials.
    }
};

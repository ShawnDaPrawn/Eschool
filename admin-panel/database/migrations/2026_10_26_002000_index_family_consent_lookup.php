<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_recipients', fn (Blueprint $t) => $t->index(['school_id', 'learner_id', 'request_id'], 'family_consent_learner_lookup'));
    }

    public function down(): void
    {
        Schema::table('consent_recipients', fn (Blueprint $t) => $t->dropIndex('family_consent_learner_lookup'));
    }
};

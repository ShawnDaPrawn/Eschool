<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_recipients', fn (Blueprint $t) => $t->index(['school_id', 'learner_id', 'request_id'], 'family_consent_learner_lookup'));
        if (Schema::hasIndex('consent_recipients', 'family_consent_learner_fk')) {
            Schema::table('consent_recipients', fn (Blueprint $t) => $t->dropIndex('family_consent_learner_fk'));
        }
    }

    public function down(): void
    {
        // MySQL may discard the implicit two-column FK index when the longer
        // lookup is created. Restore its support before removing that lookup.
        if (! Schema::hasIndex('consent_recipients', 'family_consent_learner_fk')) {
            Schema::table('consent_recipients', fn (Blueprint $t) => $t->index(['school_id', 'learner_id'], 'family_consent_learner_fk'));
        }
        Schema::table('consent_recipients', fn (Blueprint $t) => $t->dropIndex('family_consent_learner_lookup'));
    }
};

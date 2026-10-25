<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = ['school_id', 'learner_id', 'grade_level_id', 'session_year_id', 'as_of', 'source', 'fingerprint'];

    public function up(): void
    {
        // Retain every old row. The school-row mutex serializes append/replay;
        // historical matching fingerprints no longer prohibit a later reversion.
        Schema::table('risk_observations', function (Blueprint $table) {
            $table->dropUnique('risk_observation_evidence');
            $table->index(self::COLUMNS, 'risk_observation_evidence_lookup');
        });
    }

    public function down(): void
    {
        if (DB::table('risk_observations')->select(self::COLUMNS)->groupBy(self::COLUMNS)->havingRaw('COUNT(*) > 1')->exists()) {
            throw new LogicException('Retain risk reversion history; do not restore the obsolete historical-fingerprint uniqueness constraint.');
        }
        Schema::table('risk_observations', function (Blueprint $table) {
            $table->dropIndex('risk_observation_evidence_lookup');
            $table->unique(self::COLUMNS, 'risk_observation_evidence');
        });
    }
};

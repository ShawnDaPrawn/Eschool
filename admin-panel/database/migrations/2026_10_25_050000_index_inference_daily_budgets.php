<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage', function (Blueprint $table) {
            $table->index(['school_id', 'usage_date', 'unit', 'learner_id'], 'ai_learner_daily_budget');
            $table->index(['school_id', 'usage_date', 'unit', 'user_id'], 'ai_actor_daily_budget');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage', function (Blueprint $table) {
            $table->dropIndex('ai_learner_daily_budget');
            $table->dropIndex('ai_actor_daily_budget');
        });
    }
};

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
        Schema::table('ai_usage', function (Blueprint $table) {
            $table->string('model', 128)->nullable();
            $table->string('prompt_key', 64)->nullable();
            $table->string('prompt_version', 32)->nullable();
            $table->string('prompt_sha256', 64)->nullable();
            $table->unsignedBigInteger('reserved_cost_cents')->default(0);
            $table->longText('inference_output')->nullable();
            $table->text('inference_sources')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('ai_usage')->whereNotNull('prompt_key')->exists()) {
            throw new RuntimeException('Inference records must be retained; rollback refused.');
        }
        Schema::table('ai_usage', fn (Blueprint $table) => $table->dropColumn([
            'model', 'prompt_key', 'prompt_version', 'prompt_sha256', 'reserved_cost_cents', 'inference_output', 'inference_sources',
        ]));
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['af' => 'Afrikaans', 'zu' => 'isiZulu'] as $code => $name) {
            if (! DB::table('languages')->where('code', $code)->exists()) {
                DB::table('languages')->insert(['name' => $name, 'code' => $code, 'file' => $code.'.json', 'is_rtl' => 0, 'status' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Retain configured language rows and references on rollback. Older
        // releases already fall back to English when a locale file is absent.
    }
};

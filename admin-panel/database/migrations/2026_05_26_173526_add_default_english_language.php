<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('languages')->where('code', 'en')->exists();

        if ($exists) {
            return;
        }

        $now = Carbon::now();

        DB::table('languages')->insert([
            'name' => 'English',
            'code' => 'en',
            'file' => 'en.json',
            'is_rtl' => 0,
            'status' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('languages')
            ->where('code', 'en')
            ->where('status', 0)
            ->delete();
    }
};

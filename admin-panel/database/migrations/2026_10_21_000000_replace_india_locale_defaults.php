<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace the original India locale defaults on databases that still have them.
     * Schools that already changed these values are left alone.
     */
    public function up(): void
    {
        $replacements = [
            'school_email' => ['eschool@gmail.com' => 'info@tokensoft.tech'],
            'school_phone' => ['9876543210' => '+27 12 345 6789'],
            'school_address' => ['India' => 'Pretoria, South Africa'],
            'time_zone' => ['Asia/Kolkata' => 'Africa/Johannesburg'],
        ];

        foreach ($replacements as $type => $messages) {
            foreach ($messages as $from => $to) {
                DB::table('settings')
                    ->where('type', $type)
                    ->where('message', $from)
                    ->update(['message' => $to]);
            }
        }

        foreach (['currency_code' => 'ZAR', 'currency_symbol' => 'R'] as $type => $message) {
            $exists = DB::table('settings')->where('type', $type)->exists();
            if (! $exists) {
                DB::table('settings')->insert([
                    'type' => $type,
                    'message' => $message,
                ]);
            }
        }
    }

    public function down(): void
    {
        $replacements = [
            'school_email' => ['info@tokensoft.tech' => 'eschool@gmail.com'],
            'school_phone' => ['+27 12 345 6789' => '9876543210'],
            'school_address' => ['Pretoria, South Africa' => 'India'],
            'time_zone' => ['Africa/Johannesburg' => 'Asia/Kolkata'],
        ];

        foreach ($replacements as $type => $messages) {
            foreach ($messages as $from => $to) {
                DB::table('settings')
                    ->where('type', $type)
                    ->where('message', $from)
                    ->update(['message' => $to]);
            }
        }
    }
};

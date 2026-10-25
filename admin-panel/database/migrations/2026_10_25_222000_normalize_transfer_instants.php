<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These were written with the owning school's local clock. Only true
        // instants change: academic local dates and signed payloads stay intact.
        DB::transaction(function (): void {
            foreach (['transfer_packages' => ['expires_at', 'released_at', 'accepted_at'], 'transfer_stages' => ['expires_at']] as $table => $columns) {
                DB::table('schools')->select(['id', 'timezone'])->orderBy('id')->chunkById(100, function ($schools) use ($table, $columns): void {
                    foreach ($schools as $school) {
                        $configured = DB::table('settings')->where('school_id', $school->id)->where('type', 'time_zone')->value('message');
                        $zone = is_string($configured) && in_array($configured, timezone_identifiers_list(), true) ? $configured : $school->timezone;
                        DB::table($table)->where('school_id', $school->id)->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns, $school, $zone): void {
                            foreach ($rows as $row) {
                                $values = [];
                                foreach ($columns as $column) {
                                    if ($row->{$column} !== null) {
                                        $values[$column] = CarbonImmutable::parse($row->{$column}, $zone)->utc()->format('Y-m-d H:i:s');
                                    }
                                }
                                if ($values !== []) {
                                    DB::table($table)->where('school_id', $school->id)->where('id', $row->id)->update($values);
                                }
                            }
                        });
                    }
                });
            }
        });
    }

    public function down(): void
    {
        if (DB::table('transfer_packages')->exists() || DB::table('transfer_stages')->exists()) {
            throw new RuntimeException('UTC transfer instants must be retained with their records.');
        }
    }
};

<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Services\Academics\TimetableProjection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class TimetableProjectionConcurrencyTest extends TestCase
{
    use UsesTwoSchools;

    public function test_projection_read_and_cleanup_keep_the_school_lock_against_another_connection(): void
    {
        $this->setUpTwoSchools();
        // Remove the fixture's ambient test transaction. Otherwise its school
        // lock would mask the very missing refresh transaction under test.
        DB::rollBack();
        $this->assertSame(0, DB::transactionLevel());
        $connection = config('database.default');
        config(['database.connections.timetable_lock_probe' => config('database.connections.'.$connection)]);
        $probe = DB::connection('timetable_lock_probe');
        $probe->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $attempted = $blocked = false;
        DB::listen(function (QueryExecuted $query) use ($probe, &$attempted, &$blocked) {
            if ($attempted || ! str_contains($query->sql, 'from `timetable_lessons`')) {
                return;
            }
            $attempted = true;
            try {
                $probe->transaction(fn () => $probe->table('schools')->where('id', 1)->lockForUpdate()->first());
            } catch (QueryException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1205) {
                    throw $error;
                }
                $blocked = true;
            }
        });
        try {
            app(TimetableProjection::class)->refresh();
            $this->assertTrue($attempted, 'The second connection must actually compete during the canonical read.');
            $this->assertTrue($blocked, 'Refresh must retain the school lock through reading and cleanup.');
        } finally {
            DB::purge('timetable_lock_probe');
        }
    }
}

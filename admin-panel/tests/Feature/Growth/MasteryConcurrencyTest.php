<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryScope;
use App\Models\SessionYear;
use App\Services\Growth\MasteryRecompute;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class MasteryConcurrencyTest extends TestCase
{
    use UsesTwoSchools;

    public function test_different_grade_year_rebuild_waits_before_any_cross_year_aggregate_snapshot(): void
    {
        $this->setUpTwoSchools();
        // The mutex must belong to the rebuild itself, not an ambient fixture transaction.
        DB::rollBack();
        $this->assertSame(0, DB::transactionLevel());
        $year = SessionYear::query()->findOrFail($this->schoolA->ids['session_year'])->replicate();
        $year->forceFill(['default' => 0, 'name' => 'Synthetic cross-year mastery', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31'])->save();
        $subject = $this->schoolA->ids['maths'];
        $groups = [];
        foreach ([['7', $this->schoolA->ids['session_year']], ['8', $year->id]] as [$grade, $yearId]) {
            $groups[] = TeachingGroup::create(['name' => 'Synthetic concurrent mastery '.$grade, 'subject_id' => $subject,
                'grade_level_id' => GradeLevel::where('code', $grade)->value('id'), 'session_year_id' => $yearId, 'active' => true]);
        }
        $primary = config('database.default');
        config(['database.connections.mastery_lock_probe' => config('database.connections.'.$primary)]);
        DB::connection('mastery_lock_probe')->statement('SET SESSION innodb_lock_wait_timeout=1');
        $attempted = $blocked = false;
        DB::listen(function (QueryExecuted $query) use ($primary, $groups, &$attempted, &$blocked) {
            if ($attempted || $query->connectionName !== $primary || ! str_contains($query->sql, 'from `mastery_evidence`')) {
                return;
            }
            $attempted = true;
            DB::setDefaultConnection('mastery_lock_probe');
            try {
                app(MasteryRecompute::class)->group((int) $groups[1]->id);
            } catch (QueryException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1205) {
                    throw $error;
                }
                $blocked = true;
            } finally {
                DB::setDefaultConnection($primary);
            }
        });
        try {
            app(MasteryRecompute::class)->group((int) $groups[0]->id);
            $this->assertTrue($attempted, 'A second real connection must compete during the evidence snapshot.');
            $this->assertTrue($blocked, 'The same learner/subject aggregate must serialize across grade and year contexts.');
            app(MasteryRecompute::class)->group((int) $groups[1]->id);
            $this->assertSame(1, MasteryScope::where('subject_id', $subject)->count());
        } finally {
            DB::setDefaultConnection($primary);
            DB::purge('mastery_lock_probe');
            MasteryScope::where('subject_id', $subject)->delete();
            foreach ($groups as $group) {
                $group->forceDelete();
            }
            $year->delete();
        }
    }
}

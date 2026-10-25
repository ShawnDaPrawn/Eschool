<?php

declare(strict_types=1);

namespace Tests\Unit\Learning;

use App\Services\Learning\FsrsScheduler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FsrsSchedulerTest extends TestCase
{
    public function test_memory_transitions_match_the_pinned_upstream_reference_vectors(): void
    {
        $vectors = json_decode(file_get_contents(__DIR__.'/fsrs45-reference-vectors.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($vectors['cases'] as $case) {
            $actual = (new FsrsScheduler)->choices($case['memory'], $case['elapsed_days']);
            foreach ($case['choices'] as $rating => $expected) {
                $this->assertEqualsWithDelta($expected['difficulty'], $actual[$rating]['difficulty'], 0.0000001);
                $this->assertEqualsWithDelta($expected['stability_days'], $actual[$rating]['stability_days'], 0.0000001);
            }
        }
    }

    public function test_new_daily_cards_have_explicit_ordered_intervals_and_no_randomness(): void
    {
        $scheduler = new FsrsScheduler;
        $choices = $scheduler->choices(null, 0);
        $this->assertSame([1, 1, 2, 6], array_column($choices, 'interval_days'));
        $this->assertSame($choices, $scheduler->choices(null, 0));
        foreach ($choices as $rating => $choice) {
            $this->assertSame(FsrsScheduler::VERSION, $choice['version']);
            $this->assertSame($choice, $scheduler->review(null, $rating, 0));
            $this->assertNull($choice['retrievability_before']);
        }
    }

    public function test_retrievability_decays_and_daily_failure_is_due_next_day_with_bounded_ratings(): void
    {
        $scheduler = new FsrsScheduler;
        $memory = ['difficulty' => 4.93, 'stability_days' => 2.4];
        $early = $scheduler->choices($memory, 1);
        $late = $scheduler->choices($memory, 10);
        $this->assertGreaterThan($late[3]['retrievability_before'], $early[3]['retrievability_before']);
        $this->assertSame(1, $late[1]['interval_days']);
        $this->assertLessThanOrEqual($memory['stability_days'], $late[1]['stability_days']);
        foreach ([1, 2, 7, 365] as $cap) {
            $choices = $scheduler->choices($memory, 10, maximumDays: $cap);
            $intervals = array_column($choices, 'interval_days');
            $ordered = $intervals;
            sort($ordered);
            $this->assertSame($ordered, $intervals);
            foreach ($choices as $choice) {
                $this->assertGreaterThanOrEqual(1, $choice['interval_days']);
                $this->assertLessThanOrEqual($cap, $choice['interval_days']);
                $this->assertGreaterThanOrEqual(1, $choice['difficulty']);
                $this->assertLessThanOrEqual(10, $choice['difficulty']);
            }
        }
    }

    public function test_invalid_or_nonfinite_state_never_produces_a_schedule(): void
    {
        $calls = [
            fn () => (new FsrsScheduler)->review(null, 0, 0),
            fn () => (new FsrsScheduler)->review(null, 5, 0),
            fn () => (new FsrsScheduler)->review(null, 3, -1),
            fn () => (new FsrsScheduler)->review(null, 3, 0, NAN),
            fn () => (new FsrsScheduler)->review(null, 3, 0, 1.0),
            fn () => (new FsrsScheduler)->review(null, 3, 0, maximumDays: 0),
            fn () => (new FsrsScheduler)->review(['difficulty' => INF, 'stability_days' => 1], 3, 0),
            fn () => (new FsrsScheduler)->review(['difficulty' => 5, 'stability_days' => 0], 3, 0),
            fn () => (new FsrsScheduler)->review(['difficulty' => '5', 'stability_days' => 1], 3, 0),
        ];
        foreach ($calls as $call) {
            try {
                $call();
                $this->fail('Invalid memory input must not schedule a question.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}

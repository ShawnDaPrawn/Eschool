<?php

declare(strict_types=1);

namespace Tests\Unit\Learning;

use App\Services\Learning\Tests\Marking\TestMark;
use App\Services\Learning\Tests\Marking\TestMarkers;
use App\Services\Learning\Tests\TestTopicShares;
use Tests\TestCase;

/** Action Plan 3.17: how a test's questions are shared between its topics, and which marker marks which kind. */
class TestTopicSharesTest extends TestCase
{
    public function test_questions_are_shared_by_largest_remainder_and_never_beyond_what_a_topic_holds(): void
    {
        $shares = new TestTopicShares;
        $this->assertSame([1 => 3, 2 => 3], $shares->allocate(6, [1 => 1, 2 => 1], [1 => 10, 2 => 10]));
        $this->assertSame([1 => 4, 2 => 3, 3 => 3], $shares->allocate(10, [1 => 1, 2 => 1, 3 => 1], [1 => 10, 2 => 10, 3 => 10]), 'ties go to the lower topic id');
        $this->assertSame([1 => 4, 2 => 1], $shares->allocate(5, [1 => 3, 2 => 1], [1 => 10, 2 => 10]));
        $this->assertSame([1 => 2, 2 => 3], $shares->allocate(5, [1 => 3, 2 => 1], [1 => 2, 2 => 10]), 'what a topic cannot take goes to the others');
        $this->assertSame([1 => 2, 2 => 1], $shares->allocate(8, [1 => 1, 2 => 1], [1 => 2, 2 => 1]), 'never more than the pool');
        $this->assertSame([1 => 0, 2 => 5], $shares->allocate(5, [1 => 1, 2 => 1], [1 => 0, 2 => 9]));
        $this->assertSame([1 => 0, 2 => 0], $shares->allocate(5, [1 => 1, 2 => 1], []));
    }

    public function test_only_choice_and_number_are_assembled_and_other_kinds_wait_for_a_marker(): void
    {
        $markers = new TestMarkers;
        $this->assertTrue($markers->assembles('choice'));
        $this->assertTrue($markers->assembles('number'));
        $this->assertFalse($markers->assembles('short_text'));
        $number = ['kind' => 'number', 'prompt' => 'Half?', 'explanation' => 'Divide.', 'options' => [], 'answers' => ['0.5']];
        $this->assertSame([TestMark::AUTO, true], [($mark = $markers->for('number')->mark($number, '.50'))->marking, $mark->correct]);
        $this->assertSame([TestMark::AUTO, false], [($mark = $markers->for('number')->mark($number, '2'))->marking, $mark->correct]);
        $this->assertSame([TestMark::AWAITING_MARKER, null], [($mark = $markers->for('photo')->mark([], 'my working'))->marking, $mark->correct]);
    }
}

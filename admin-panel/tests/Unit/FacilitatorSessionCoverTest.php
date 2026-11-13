<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Leave\FacilitatorSessionCover;
use Tests\TestCase;

class FacilitatorSessionCoverTest extends TestCase
{
    public function test_attendance_revision_does_not_mark_an_unchanged_cover_schedule_stale(): void
    {
        $cover = new FacilitatorSessionCover;
        $cover->forceFill(['activity_group_id' => 12, 'on_date' => '2026-02-12', 'starts_at' => '14:30:00', 'ends_at' => '15:30:00', 'session_revision' => 3]);
        $source = ['activity_group_id' => 12, 'on_date' => '2026-02-12', 'starts_at' => '14:30', 'ends_at' => '15:30', 'status' => 'scheduled', 'revision' => 4];
        $this->assertFalse($cover->needsReview($source));
        $this->assertFalse($cover->needsReview(array_replace($source, ['status' => 'completed'])));
        $this->assertTrue($cover->needsReview(array_replace($source, ['starts_at' => '14:00'])));
        $this->assertTrue($cover->needsReview(array_replace($source, ['on_date' => '2026-02-13'])));
        $this->assertTrue($cover->needsReview(array_replace($source, ['activity_group_id' => 13])));
        $this->assertTrue($cover->needsReview(array_replace($source, ['status' => 'cancelled'])));
        $this->assertTrue($cover->needsReview(null));
    }
}

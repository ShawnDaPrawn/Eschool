<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FamilyForms\SchoolFormTime;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FamilyFormTimeTest extends TestCase
{
    public function test_school_wall_time_uses_the_school_zone_and_refuses_dst_gaps_and_folds(): void
    {
        $time = new SchoolFormTime;
        $this->assertSame('2026-02-20T08:00:00+00:00', $time->utc('2026-02-20T10:00', 'Africa/Johannesburg'));
        $this->assertSame('2026-07-20T09:00:00+00:00', $time->utc('2026-07-20T10:00', 'Europe/London'));
        foreach (['2026-03-29T01:30', '2026-10-25T01:30', '2026-02-30T10:00', 'invalid'] as $wall) {
            try {
                $time->utc($wall, 'Europe/London');
                $this->fail('An invalid or ambiguous wall clock was accepted.');
            } catch (ValidationException $error) {
                $this->assertSame('Choose a valid, unambiguous school date and time.', $error->errors()['time'][0]);
            }
        }
    }
}

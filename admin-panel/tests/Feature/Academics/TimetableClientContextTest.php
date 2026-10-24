<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class TimetableClientContextTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    public function test_default_week_uses_school_midnight_and_a_scoped_calendar_without_device_dates(): void
    {
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-08 22:30:00', 'UTC'));
        School::whereKey(1)->update(['timezone' => 'Africa/Johannesburg']);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        SessionYear::whereKey($this->schoolA->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->withToken($teacher->createToken('school-week')->plainTextToken)->getJson('/api/v2/academics/timetable/week')
            ->assertOk()->assertJsonPath('data.session_year_id', $this->schoolA->ids['session_year'])
            ->assertJsonPath('data.starts_on', '2026-02-09')->assertJsonPath('data.ends_on', '2026-02-15');
        $this->getJson('/api/v2/academics/timetable/week?starts_on=2026-02-02')->assertOk()->assertJsonPath('data.starts_on', '2026-02-02');
        $this->withHeader('X-School-Id', '2')->getJson('/api/v2/academics/timetable/week')->assertForbidden();
    }
}

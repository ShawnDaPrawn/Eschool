<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MembershipCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $person;

    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
        $this->seedLikeInstaller();
        School::findOrFail(1)->update(['timezone' => 'Africa/Johannesburg']);
        School::forceCreate(['id' => 2, 'name' => 'Pacific school', 'slug' => 'pacific-school', 'status' => 'active', 'timezone' => 'America/Los_Angeles']);
        $this->person = User::forceCreate(['first_name' => 'Synthetic', 'last_name' => 'Calendar member', 'email' => 'membership-calendar@example.test', 'password' => 'SyntheticOnly2026', 'status' => 1]);
        foreach ([1, 2] as $schoolId) {
            SchoolMembership::create(['school_id' => $schoolId, 'user_id' => $this->person->id, 'role' => 'Teacher', 'status' => 'active', 'starts_on' => '2026-10-26']);
            SchoolMembership::create(['school_id' => $schoolId, 'user_id' => $this->person->id, 'role' => 'Facilitator', 'status' => 'active', 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-25']);
        }
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    private function instant(string $value): void
    {
        $this->travelTo(CarbonImmutable::parse($value, 'UTC'));
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
    }

    public function test_active_memberships_follow_each_joined_school_day_instead_of_the_app_day(): void
    {
        $this->instant('2026-10-25T22:30:00Z');
        $other = User::forceCreate(['first_name' => 'Unrelated', 'last_name' => 'Person', 'email' => 'unrelated-calendar@example.test', 'password' => 'SyntheticOnly2026', 'status' => 1]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $other->id, 'role' => 'Teacher', 'status' => 'active']);
        DB::enableQueryLog();
        $rows = app(MembershipService::class)->active($this->person);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertSame([[1, 'Teacher'], [2, 'Facilitator']], $rows->map(fn ($row) => [(int) $row->school_id, $row->role])->all());
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('`school_memberships`.`user_id` = ?', $queries[0]['query']);
        foreach ($rows as $membership) {
            $this->assertSame($this->person->id, $membership->user_id);
            $this->assertTrue($membership->relationLoaded('school'));
            $this->assertFalse($membership->isDirty());
            $this->assertFalse(collect(array_keys($membership->getAttributes()))->contains(fn ($key) => str_starts_with($key, 'school__')));
        }
    }

    public function test_contextual_active_scope_obeys_the_selected_school_on_both_sides_of_utc_midnight(): void
    {
        app(CurrentSchool::class)->set(School::findOrFail(1));
        $this->instant('2026-10-25T22:30:00Z');
        $this->assertSame(['Teacher'], SchoolMembership::where('school_id', 1)->where('user_id', $this->person->id)->active()->pluck('role')->all());
        app(CurrentSchool::class)->set(School::findOrFail(2));
        $this->instant('2026-10-26T00:30:00Z');
        $this->assertSame(['Facilitator'], SchoolMembership::where('school_id', 2)->where('user_id', $this->person->id)->active()->pluck('role')->all());
    }

    public function test_new_role_grants_and_end_dates_use_the_target_school_calendar(): void
    {
        $this->instant('2026-10-26T00:30:00Z');
        $service = app(MembershipService::class);
        $row = $service->grant($this->person, 2, 'Counsellor');
        $this->assertSame('2026-10-25', $row->starts_on->toDateString());
        $this->assertTrue($service->active($this->person)->contains('role', 'Counsellor'));
        $service->end($this->person, School::findOrFail(2), 'Counsellor');
        $this->assertSame('ended', $row->fresh()->status);
        $this->assertSame('2026-10-25', $row->fresh()->ends_on->toDateString());
        $this->assertFalse($service->active($this->person)->contains('role', 'Counsellor'));
    }

    public function test_status_school_status_and_request_cache_semantics_are_preserved(): void
    {
        $this->instant('2026-10-25T22:30:00Z');
        SchoolMembership::where('school_id', 1)->where('user_id', $this->person->id)->where('role', 'Teacher')->update(['status' => 'suspended']);
        School::whereKey(2)->update(['status' => 'suspended']);
        $service = app(MembershipService::class);
        $before = $service->active($this->person);
        $this->assertTrue($before->isEmpty());
        School::whereKey(2)->update(['status' => 'active']);
        $this->assertSame($before, $service->active($this->person));
        $service->forget($this->person);
        $this->assertSame(['Facilitator'], $service->active($this->person)->pluck('role')->all());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityStaff;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\FamilyForms\FamilyPanelScope;
use App\Services\Gallery\GalleryPanelScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class PanelAvailabilityTest extends TestCase
{
    use UsesTwoSchools;

    private ActivityGroup $group;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        config(['modules.gallery' => ['always_on' => true], 'modules.groups' => ['always_on' => true], 'modules.family_forms' => ['always_on' => true]]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->group = ActivityGroup::create(['session_year_id' => $this->schoolA->ids['session_year'], 'name' => 'Synthetic navigation group', 'discipline' => 'Culture', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->teacher->id, 'role' => 'coach', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic navigation assignment', 'revision' => 1]);
    }

    public function test_navigation_preserves_current_role_group_school_and_module_boundaries(): void
    {
        foreach ([app(GalleryPanelScope::class), app(FamilyPanelScope::class)] as $scope) {
            $this->assertTrue($scope->available($this->teacher));
            $this->assertFalse($scope->available(User::findOrFail($this->schoolA->ids['theo_user'])));
            $this->assertFalse($scope->available(User::findOrFail($this->schoolA->ids['farid_user'])));
            $this->assertFalse(app(CurrentSchool::class)->run(2, fn () => $scope->available($this->teacher)));
        }
        ActivityStaff::where('activity_group_id', $this->group->id)->update(['active' => false]);
        $this->assertFalse(app(GalleryPanelScope::class)->available($this->teacher));
        $this->assertFalse(app(FamilyPanelScope::class)->available($this->teacher));
        ActivityStaff::where('activity_group_id', $this->group->id)->update(['active' => true]);
        $this->group->update(['ends_on' => '2026-02-09']);
        $this->assertFalse(app(GalleryPanelScope::class)->available($this->teacher));
        // Consent setup follows its existing active current-year group rule; gallery reading also requires today's group dates.
        $this->assertTrue(app(FamilyPanelScope::class)->available($this->teacher));
        config(['modules.gallery' => ['default' => false], 'modules.family_forms' => ['default' => false]]);
        $this->assertFalse(app(GalleryPanelScope::class)->available($this->superAdmin()));
        $this->assertFalse(app(FamilyPanelScope::class)->available($this->superAdmin()));
    }

    public function test_a_navigation_link_does_not_build_every_groups_configuration(): void
    {
        $admin = $this->superAdmin();
        $count = function (object $scope) use ($admin): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            try {
                $this->assertTrue($scope->available($admin));

                return count(DB::getQueryLog());
            } finally {
                DB::disableQueryLog();
                DB::flushQueryLog();
            }
        };
        $gallery = app(GalleryPanelScope::class);
        $family = app(FamilyPanelScope::class);
        $before = [$count($gallery), $count($family)];
        for ($i = 0; $i < 25; $i++) {
            $group = $this->group->replicate();
            $group->name = 'Additional navigation group '.$i;
            $group->save();
        }
        $this->assertLessThanOrEqual($before[0] + 3, $count($gallery));
        $this->assertLessThanOrEqual($before[1] + 3, $count($family));
        ActivityGroup::query()->update(['active' => false]);
        $this->assertTrue($gallery->available($admin));
        $this->assertFalse($family->available($admin));
    }
}

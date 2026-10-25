<?php

declare(strict_types=1);

namespace Tests\Feature\Pastoral;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\SchoolMembership;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesDemoSchool;
use Tests\TestCase;

class AcademicStaffRoleTransitionTest extends TestCase
{
    use UsesDemoSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 09:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
    }

    public static function restrictedStaffRoles(): array
    {
        return [['Facilitator'], ['Counsellor'], ['Safeguarding']];
    }

    #[DataProvider('restrictedStaffRoles')]
    public function test_a_retained_teacher_profile_does_not_keep_teaching_authority_after_role_change(string $role): void
    {
        $actor = User::findOrFail($this->school->ids['tara_user']);
        $teacherId = (int) Teacher::where('user_id', $actor->id)->value('id');
        $group = TeachingGroup::create(['name' => 'Transition authority group', 'subject_id' => $this->school->ids['maths'],
            'grade_level_id' => GradeLevel::where('code', '7')->value('id'), 'session_year_id' => $this->school->ids['session_year'], 'active' => true]);
        TeachingAllocation::create(['teaching_group_id' => $group->id, 'teacher_id' => $teacherId,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Retained allocation']);
        $authority = app(AcademicAuthority::class);
        $this->assertContains($teacherId, $authority->teachers($actor));
        $this->assertSame($group->id, $authority->group($actor, (int) $group->id)->id);
        $actor->load('roles');
        $actor->syncRoles([$role]);
        $roles = app(ProfilePolicy::class)->roles($actor);
        $this->assertNotContains('Teacher', $roles);
        $this->assertContains($role, $roles);
        $this->assertTrue(Teacher::whereKey($teacherId)->exists());
        $this->assertSame([], $authority->teachers($actor));
        try {
            $authority->group($actor, (int) $group->id);
            $this->fail('A non-teaching role reused stale teaching allocations.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\Modules\ModuleAccess;
use App\Services\Platform\Tenancy\SchoolRoles;
use InvalidArgumentException;
use ReflectionClass;
use Tests\TestCase;

/**
 * The module access map covers every assignable permission exactly once.
 * Specification: engineering/specs/platform/module-access.md.
 */
class ModuleAccessTest extends TestCase
{
    /** @return list<string> */
    private function assignable(): array
    {
        $constants = (new ReflectionClass(SchoolRoles::class))->getConstants();

        return array_values(array_unique($constants['PERMISSIONS'][SchoolRoles::SUPER_ADMIN]));
    }

    public function test_every_assignable_permission_belongs_to_exactly_one_module_or_is_retired(): void
    {
        $owners = app(ModuleAccess::class)->owners();
        $retired = (array) config('module_access.retired');

        $twice = array_keys(array_filter($owners, fn (array $modules) => count($modules) > 1));
        $this->assertSame([], $twice, 'Mapped to more than one module or level');

        $missing = array_values(array_diff($this->assignable(), array_keys($owners), $retired));
        $this->assertSame([], $missing, 'Add these permissions to config/module_access.php');

        $unknown = array_values(array_diff(array_keys($owners), $this->assignable()));
        $this->assertSame([], $unknown, 'The map names permissions the Super Admin role does not hold');

        $this->assertSame([], array_values(array_intersect($retired, array_keys($owners))), 'A retired permission is still offered');
    }

    public function test_every_module_with_levels_is_a_registered_module(): void
    {
        $registered = array_keys((array) config('modules'));
        foreach (app(ModuleAccess::class)->modules() as $module) {
            $this->assertContains($module, $registered, "{$module} is not in config/modules.php");
        }
    }

    public function test_levels_are_cumulative(): void
    {
        $access = app(ModuleAccess::class);
        $view = $access->permissionsFor('academics', ModuleAccess::VIEW);
        $contribute = $access->permissionsFor('academics', ModuleAccess::CONTRIBUTE);
        $manage = $access->permissionsFor('academics', ModuleAccess::MANAGE);

        $this->assertSame([], $access->permissionsFor('academics', ModuleAccess::NONE));
        $this->assertSame([], array_diff($view, $contribute));
        $this->assertSame([], array_diff($contribute, $manage));
        $this->assertContains('timetable-list', $view);
        $this->assertContains('class-attendance', $contribute);
        $this->assertNotContains('class-attendance', $view);
        $this->assertContains('timetable-create', $manage);
        $this->assertNotContains('marks-release', $manage, 'abilities are never part of a level');
    }

    public function test_a_role_level_is_read_back_from_its_permissions(): void
    {
        $access = app(ModuleAccess::class);

        $this->assertSame(ModuleAccess::NONE, $access->levelOf('academics', []));
        $this->assertSame(ModuleAccess::VIEW, $access->levelOf('academics', $access->permissionsFor('academics', ModuleAccess::VIEW)));
        $this->assertSame(ModuleAccess::MANAGE, $access->levelOf('academics', $access->permissionsFor('academics', ModuleAccess::MANAGE)));
        $partial = array_slice($access->permissionsFor('academics', ModuleAccess::MANAGE), 0, -1);
        $this->assertSame(ModuleAccess::CONTRIBUTE, $access->levelOf('academics', $partial), 'an incomplete level does not count');
        $this->assertSame(ModuleAccess::MANAGE, $access->levelOf('intake', ['online-registration-list']), 'empty lower levels are reached with the higher one');
    }

    public function test_choices_become_permission_names_with_abilities_granted_separately(): void
    {
        $access = app(ModuleAccess::class);
        $permissions = $access->permissionsForChoices(['academics' => ModuleAccess::VIEW, 'fees' => ModuleAccess::NONE], ['academics' => ['marks_release']]);

        $this->assertContains('timetable-list', $permissions);
        $this->assertContains('marks-release', $permissions);
        $this->assertNotContains('fee-plans-manage', $permissions);

        $this->expectException(InvalidArgumentException::class);
        $access->permissionsForChoices([], ['academics' => ['launch_rockets']]);
    }

    public function test_health_wellbeing_identity_and_money_approvals_are_marked_sensitive(): void
    {
        $access = app(ModuleAccess::class);
        foreach ([['family_forms', 'health_read'], ['family_forms', 'health_manage'], ['wellbeing', 'wellbeing_configure'],
            ['people', 'sensitive_profiles'], ['people', 'roles'], ['fees', 'fee_billing'], ['academics', 'marks_release']] as [$module, $key]) {
            $this->assertTrue($access->abilities($module)[$key]['sensitive'], "{$module}.{$key} must be sensitive");
        }
    }
}

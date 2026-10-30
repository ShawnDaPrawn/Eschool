<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Platform\Modules\ModuleAccess;
use App\Services\Platform\Tenancy\SchoolRoles;
use Tests\TestCase;

/**
 * Role templates are starting points built only from known modules, levels and
 * ordinary abilities: never a sensitive one (config/role_templates.php).
 */
class RoleTemplatesTest extends TestCase
{
    public function test_every_template_uses_known_modules_levels_and_only_ordinary_abilities(): void
    {
        $access = app(ModuleAccess::class);
        $templates = (array) config('role_templates');
        $this->assertGreaterThanOrEqual(9, count($templates));
        $assignable = array_diff(SchoolRoles::PERMISSIONS[SchoolRoles::SUPER_ADMIN], (array) config('module_access.retired'));
        foreach ($templates as $key => $template) {
            $this->assertNotSame('', trim($template['label']), $key);
            $this->assertNotSame('', trim($template['description']), $key);
            $this->assertNotContains($template['label'], ['Super Admin', 'Teacher', 'Parent', 'Student'], 'a template never replaces a system role');
            foreach ($template['levels'] as $module => $level) {
                $this->assertContains($module, $access->modules(), "{$key}: {$module}");
                $this->assertContains($level, ['none', 'view', 'contribute', 'manage'], "{$key}: {$module}");
            }
            foreach ($template['abilities'] as $module => $abilities) {
                foreach ($abilities as $ability) {
                    $definition = $access->abilities($module)[$ability] ?? null;
                    $this->assertNotNull($definition, "{$key}: {$module}.{$ability}");
                    $this->assertFalse($definition['sensitive'], "{$key}: {$module}.{$ability} is sensitive and must be granted on purpose");
                }
            }
            $permissions = $access->permissionsForChoices($template['levels'], $template['abilities']);
            $this->assertNotEmpty($permissions, $key);
            $this->assertSame([], array_values(array_diff($permissions, $assignable)), "{$key} grants only what a custom role may hold");
        }
    }
}

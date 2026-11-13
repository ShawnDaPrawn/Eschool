<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('UI browser fixtures require an isolated test database.');
}

// The production defaults remain unchanged. This synthetic administrator has
// every registered module needed to audit the complete available panel.
// Source package, permission and role checks remain authoritative.
return app(CurrentSchool::class)->run(1, function (): bool {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $admin->givePermissionTo(['activity-groups-manage', 'people-pool-manage', 'vendors-manage']);
    $gate = app(ModuleGate::class);
    $definitions = config('modules', []);
    $visited = [];
    $enable = function (string $module) use (&$enable, &$visited, $definitions, $gate, $admin): void {
        if (isset($visited[$module])) {
            return;
        }
        $visited[$module] = true;
        foreach ($definitions[$module]['dependencies'] ?? [] as $dependency) {
            $enable($dependency);
        }
        if (! ($definitions[$module]['always_on'] ?? false) && ! $gate->writable($module)) {
            $gate->change($module, true, $admin);
        }
    };
    foreach (array_keys($definitions) as $module) {
        $enable($module);
    }

    return true;
});

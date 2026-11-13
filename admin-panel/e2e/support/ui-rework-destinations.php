<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Panel\Navigation;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Auth;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('UI destination discovery requires an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    Auth::setUser($admin);
    $destinations = [];
    foreach (app(Navigation::class)->sections($admin) as $section) {
        $destinations[] = $section['url'];
        foreach ($section['groups'] as $group) {
            foreach ($group['items'] as $item) {
                $destinations[] = $item['url'];
                if ($item['configuration']) {
                    $destinations[] = route('panel.workspace', ['section' => $section['key'], 'view' => 'configuration']);
                } elseif ($section['key'] === 'settings') {
                    $destinations[] = route('panel.workspace', ['section' => 'settings', 'view' => 'operations']);
                }
            }
        }
    }

    return array_values(array_unique($destinations));
});

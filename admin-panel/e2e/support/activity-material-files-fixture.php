<?php

declare(strict_types=1);

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMaterial;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Material document fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $actor = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('groups', true, $actor);
    $group = ActivityGroup::create(['session_year_id' => schoolSetting('session_year'), 'name' => 'Synthetic document group '.bin2hex(random_bytes(4)),
        'discipline' => 'Culture', 'season' => now()->year, 'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString(), 'active' => true]);
    $material = ActivityMaterial::create(['activity_group_id' => $group->id, 'author_id' => $actor->id, 'title' => 'Synthetic document material',
        'markdown' => 'Private synthetic group instructions', 'published' => true, 'revision' => 1]);

    return ['group' => (int) $group->id, 'material' => (int) $material->id];
});

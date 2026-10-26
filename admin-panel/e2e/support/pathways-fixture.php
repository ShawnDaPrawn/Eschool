<?php

declare(strict_types=1);

use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Pathway browser fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('pathway', true, $admin);
    $subjects = Subject::orderBy('id')->limit(3)->pluck('id')->all();
    if (count($subjects) < 3) {
        throw new RuntimeException('The synthetic demo school must have three subjects.');
    }
    $today = app(AcademicAuthority::class)->today();

    return ['subjects' => $subjects, 'today' => $today,
        'expiry' => CarbonImmutable::parse($today)->addDays(180)->toDateString(), 'year' => (int) substr($today, 0, 4)];
});

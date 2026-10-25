<?php

declare(strict_types=1);

use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Digest browser fixtures require an isolated test database.');
}
app(CurrentSchool::class)->run(1, function (): void {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('digest', true, $admin);
    SessionYear::findOrFail((int) schoolSetting('session_year'))->update([
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
    ]);
});

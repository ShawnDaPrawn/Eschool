<?php

declare(strict_types=1);

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! app()->environment(['local', 'testing']) || (! str_ends_with($database, '_test') && $database !== 'eschool_e2e')) {
    throw new RuntimeException('Platform browser fixtures require a local isolated test database.');
}

return app(CurrentSchool::class)->acrossSchools(function (): array {
    $operator = User::query()->firstOrCreate(['email' => 'platform.browser@demo.navigateed.test'], [
        'first_name' => 'Synthetic', 'last_name' => 'Platform operator', 'mobile' => '', 'password' => 'NavigateDemo2026', 'status' => 1,
    ]);
    if (SchoolMembership::query()->where('user_id', $operator->id)->exists()) {
        throw new RuntimeException('The platform fixture must have no school membership.');
    }
    PlatformStaffIdentity::query()->updateOrCreate(['user_id' => $operator->id], ['active' => true]);
    PlatformAdminGrant::query()->updateOrCreate(['user_id' => $operator->id], [
        'active' => (getenv('SAAS_FIXTURE_ACTION') ?: 'initial') !== 'revoke', 'expires_at' => now()->addHour(),
    ]);

    return ['email' => $operator->email];
});

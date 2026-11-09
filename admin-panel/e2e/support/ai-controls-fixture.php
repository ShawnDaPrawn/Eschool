<?php

declare(strict_types=1);

use App\Models\Ai\AiUsage;
use App\Models\Ai\PlatformAiCostGrant;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\PlatformStaffTwoFactor;
use App\Models\User;
use App\Services\Platform\Saas\Totp;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('AI control browser fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    // Business staff have their own account; never repurpose the school's finance user.
    $operator = User::firstOrCreate(['email' => 'ai.operator@platform.example'], [
        'first_name' => 'Synthetic', 'last_name' => 'Business operator', 'mobile' => '',
        'password' => 'NavigateDemo2026', 'status' => 1,
    ]);
    PlatformStaffIdentity::updateOrCreate(['user_id' => $operator->id], ['active' => true]);
    PlatformAdminGrant::updateOrCreate(['user_id' => $operator->id], ['active' => true, 'role' => 'finance', 'expires_at' => now()->addHour()]);
    $secret = Totp::generateSecret();
    PlatformStaffTwoFactor::updateOrCreate(['user_id' => $operator->id], ['secret' => $secret, 'confirmed_at' => now(), 'last_used_step' => null, 'recovery_codes' => []]);
    PlatformAiCostGrant::updateOrCreate(['user_id' => $operator->id], ['active' => true, 'expires_at' => now()->addHour()]);
    AiUsage::create(['user_id' => $operator->id, 'feature' => 'synthetic_cost_browser', 'provider' => 'synthetic', 'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'synthetic browser cost'), 'usage_date' => now()->toDateString(), 'unit' => 'tokens', 'state' => 'completed', 'used_units' => 100, 'cost_cents' => 3, 'reserved_units' => 50, 'reserved_cost_cents' => 2, 'billing_currency' => 'USD', 'inference_output' => ['output' => 'Synthetic private learning body never belongs in costs']]);

    return ['email' => $operator->email, 'secret' => $secret];
});

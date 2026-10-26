<?php

declare(strict_types=1);

use App\Models\Ai\AiUsage;
use App\Models\Ai\PlatformAiCostGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('AI control browser fixtures require an isolated test database.');
}
app(CurrentSchool::class)->run(1, function (): void {
    $operator = User::where('email', 'finance.a@demo.navigateed.test')->firstOrFail();
    PlatformStaffIdentity::updateOrCreate(['user_id' => $operator->id], ['active' => true]);
    PlatformAiCostGrant::updateOrCreate(['user_id' => $operator->id], ['active' => true, 'expires_at' => now()->addHour()]);
    AiUsage::create(['user_id' => $operator->id, 'feature' => 'synthetic_cost_browser', 'provider' => 'synthetic', 'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'synthetic browser cost'), 'usage_date' => now()->toDateString(), 'unit' => 'tokens', 'state' => 'completed', 'used_units' => 100, 'cost_cents' => 3, 'reserved_units' => 50, 'reserved_cost_cents' => 2, 'billing_currency' => 'USD', 'inference_output' => ['output' => 'Synthetic private learning body never belongs in costs']]);
});

<?php

declare(strict_types=1);

use App\Models\Ai\AiUsage;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('AI review browser fixtures require an isolated test database.');
}
app(CurrentSchool::class)->run(1, function (): void {
    $reviewer = User::updateOrCreate(['email' => 'safeguarding.a@demo.navigateed.test'], ['first_name' => 'Synthetic', 'last_name' => 'Safeguarding', 'password' => Hash::make('NavigateDemo2026'), 'password_change_required' => false, 'status' => 1]);
    SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $reviewer->id, 'role' => 'Safeguarding'], ['status' => 'active', 'starts_on' => now()->subDay()->toDateString()]);
    $reviewer->assignRole('Safeguarding');
    $usage = AiUsage::create(['user_id' => $reviewer->id, 'feature' => 'synthetic_browser_review', 'provider' => 'synthetic', 'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'synthetic browser fixture'), 'usage_date' => now()->toDateString(), 'unit' => 'tokens', 'state' => 'reserved']);
    app(AiQuarantine::class)->capture($usage, 'input', ['synthetic_safety'], 'Synthetic learning disclosure <script>providerUnsafe()</script>');
});

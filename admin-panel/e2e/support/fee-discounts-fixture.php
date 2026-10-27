<?php

declare(strict_types=1);
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeDiscount;
use App\Models\Money\FeeDiscountRun;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Discount fixtures require an isolated DB');
}
$data = require base_path('e2e/support/fee-items-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['discount_rules' => FeeDiscount::count(), 'discount_runs' => FeeDiscountRun::count(), 'posted_discounts' => FeeDiscountRun::whereNotNull('sealed_at')->count(), 'credit_notes' => FeeCreditNote::count()]);
}
app(CurrentSchool::class)->run($data['school'], function () use ($data): void {
    User::where('email', $data['finance'])->firstOrFail()->roles()->firstOrFail()->givePermissionTo('fee-credits-manage');
});

return $data;

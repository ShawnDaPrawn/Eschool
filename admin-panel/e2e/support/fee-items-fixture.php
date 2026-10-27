<?php

declare(strict_types=1);
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;

$data = require base_path('e2e/support/fee-payments-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['items' => FeeItem::count(), 'charges' => FeeCharge::whereNotNull('sealed_at')->count(), 'invoices' => Invoice::whereNotNull('sealed_at')->count(), 'annual_lines' => InvoiceLine::whereNull('fee_charge_id')->count(), 'item_lines' => InvoiceLine::whereNotNull('fee_charge_id')->count()]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $actor->roles()->firstOrFail()->givePermissionTo('fee-items-manage');

    return $data;
});

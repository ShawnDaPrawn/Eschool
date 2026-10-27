<?php

declare(strict_types=1);

use App\Models\Money\FeeCharge;
use App\Models\Money\FeeCreditNote;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

// This underlying fixture guards the isolated database before any writes.
$data = require base_path('e2e/support/fee-items-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + [
        'vat_cents' => (int) DB::table('ledger_accounts')->where('school_id', $school->id)->where('type', 'fee_vat')->sum('balance_cents'),
        'notes' => FeeCreditNote::whereNotNull('sealed_at')->count(),
        'charge_vat' => (int) FeeCharge::whereNotNull('sealed_at')->sum('vat_cents'),
    ]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $actor->roles()->firstOrFail()->givePermissionTo('fee-credits-manage');

    return $data;
});

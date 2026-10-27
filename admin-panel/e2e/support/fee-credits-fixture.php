<?php

declare(strict_types=1);

use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeCreditRelease;
use App\Models\Money\InvoiceLine;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

// The underlying fixture checks the isolated database before any reads/writes.
$data = require base_path('e2e/support/fee-payments-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['notes' => FeeCreditNote::whereNotNull('sealed_at')->count(), 'releases' => FeeCreditRelease::count(), 'gross_invoice_cents' => (int) InvoiceLine::sum('amount_cents')]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $actor->roles()->firstOrFail()->givePermissionTo('fee-credits-manage');
    $actor->unsetRelation('roles');
    app(FeePaymentSettings::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
    $payment = app(FeePayments::class)->record($actor, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $data['account'], 'holder_user_id' => $data['parents'][0], 'pinned_learner_id' => null,
        'method' => 'eft', 'source_reference' => 'SYNTHETIC-CREDIT-'.Str::uuid(), 'amount' => '9.00', 'received_on' => $data['date'], 'confirmed' => true]);
    $preview = app(FeeAllocations::class)->preview($actor, $payment->id);
    app(FeeAllocations::class)->post($actor, $payment->id, ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint'], 'confirmed' => true]);

    return $data + ['line' => (int) InvoiceLine::where('learner_id', $data['learners'][0]['id'])->firstOrFail()->id];
});

<?php

declare(strict_types=1);
use App\Models\Money\FeePayment;
use App\Models\Money\FeeRefund;
use App\Models\Money\FeeRefundAction;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$data = require base_path('e2e/support/fee-payments-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function () use ($data): array {
        $payment = FeePayment::sole();

        return $data + ['refunds' => FeeRefund::count(), 'paid_cents' => (int) FeeRefundAction::where('action', 'paid')->sum('amount_cents'), 'cancelled' => FeeRefundAction::where('action', 'cancelled')->count(), 'available_cents' => app(FeePayments::class)->available($payment), 'healthy' => FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect())];
    });
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $payment = app(FeePayments::class)->record($actor, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $data['account'], 'holder_user_id' => $data['parents'][0], 'pinned_learner_id' => $data['learners'][0]['id'], 'method' => 'cash', 'source_reference' => 'SYNTHETIC-'.Str::uuid(), 'amount' => '9.00', 'received_on' => $data['date'], 'confirmed' => true]);

    return $data + ['payment' => (int) $payment->id];
});

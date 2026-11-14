<?php

declare(strict_types=1);

use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformInvoiceLine;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\SchoolBusiness\BursaryApplication;
use App\Models\SchoolBusiness\GivingDonation;
use App\Models\SchoolBusiness\GivingPledge;
use App\Models\User;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Finance UI fixtures require an isolated test database.');
}
if (getenv('SCHOOL_FINANCE_UI_FIXTURE_ACTION') === 'stats') {
    $school = (int) getenv('BILLING_FIXTURE_SCHOOL');
    abort_unless($school > 0 && DB::table('schools')->where('id', $school)->where('slug', 'like', 'synthetic-billing-%')->exists(), 422);

    return app(CurrentSchool::class)->run($school, fn (): array => ['donations' => GivingDonation::count(), 'donated_cents' => (int) GivingDonation::sum('amount_cents'), 'pledged_cents' => (int) GivingPledge::sum('amount_cents'), 'sponsored_learners' => DB::table('learner_subscription_members')->where('school_id', $school)->whereNull('removed_at')->count(), 'bursary_state' => BursaryApplication::sole()->state, 'healthy' => app(LedgerService::class)->projectionDifferences() === []]);
}
$data = require __DIR__.'/family-billing-fixture.php';

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->sole();
    $actor->assignRole('Super Admin');
    SchoolMembership::updateOrCreate(['user_id' => $actor->id, 'role' => 'Super Admin'], ['starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => null]);
    foreach (['giving', 'bursaries'] as $module) {
        SchoolModule::updateOrCreate(['module' => $module], ['enabled' => true, 'in_package' => true, 'read_only' => false]);
    }
    $application = BursaryApplication::create(['public_id' => (string) Str::uuid(), 'learner_id' => $data['learners'][0]['id'], 'guardian_user_id' => $data['parents'][0], 'session_year_id' => $data['year'], 'state' => 'submitted', 'household' => ['household_size' => 4, 'dependants' => 2, 'monthly_income_cents' => 200001, 'monthly_expenses_cents' => 199999, 'explanation' => 'Synthetic private household application for browser review.'], 'revision' => 1, 'submitted_at' => now()]);
    $sponsor = LearnerSubscription::create(['reference' => 'UI-'.str_pad((string) $data['school'], 6, '0', STR_PAD_LEFT), 'plan' => 'sponsored', 'sponsor_name' => 'Synthetic community sponsor', 'places' => 1, 'status' => 'requested', 'price_cents' => 10000, 'currency' => 'ZAR', 'period_months' => 1, 'provider' => 'manual', 'requested_by' => $actor->id]);
    $invoice = PlatformInvoice::create(['kind' => 'invoice', 'status' => 'draft', 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->endOfMonth()->toDateString(), 'package_key' => 'legacy', 'currency' => 'ZAR', 'vat_rate_bp' => 1500, 'subtotal_cents' => 10000, 'vat_cents' => 1500, 'total_cents' => 11500, 'blocked_reason' => 'PRIVATE HQ DIAGNOSTIC']);
    PlatformInvoiceLine::create(['invoice_id' => $invoice->id, 'position' => 1, 'kind' => 'base', 'description' => 'Issued synthetic subscription package', 'learners' => 1, 'unit_cents' => 10000, 'amount_cents' => 10000, 'vat_cents' => 1500]);
    $invoice->update(['status' => 'issued', 'reference' => 'NE-UI-'.$invoice->id, 'issued_on' => now()->toDateString(), 'due_on' => now()->addMonth()->toDateString(), 'issued_at' => now(), 'issued_by' => $actor->id]);

    return $data + ['application' => $application->public_id, 'sponsor' => $sponsor->id, 'subscription_invoice' => $invoice->id, 'today' => now()->toDateString(), 'through' => now()->addMonth()->toDateString()];
});

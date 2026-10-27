<?php

declare(strict_types=1);

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemConsent;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeItemConsents;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Optional fee fixtures require an isolated database.');
}
if (getenv('BILLING_FIXTURE_ACTION') === 'approve_all') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function (): array {
        $account = FeeAccount::firstOrFail();
        $item = FeeItem::firstOrFail();
        $year = SessionYear::where('default', 1)->firstOrFail();
        foreach (FeeAccountHolder::where('fee_account_id', $account->id)->where('session_year_id', $year->id)->orderBy('user_id')->get() as $holder) {
            $parent = User::findOrFail($holder->user_id);
            $page = app(FeeItemConsents::class)->page($parent, $account->id);
            $offer = collect($page['items'])->firstWhere('fee_item_id', (int) $item->id);
            abort_unless($offer !== null, 422);
            app(FeeItemConsents::class)->change($parent, $account->id, $item->id, ['operation_id' => (string) Str::uuid(), 'session_year_id' => $year->id, 'learner_id' => null, 'revision' => $offer['revision'], 'active' => true, 'fingerprint' => $offer['fingerprint'], 'confirmed' => true]);
        }

        return ['consents' => FeeItemConsent::count(), 'entries' => DB::table('ledger_entries')->where('school_id', app(CurrentSchool::class)->id())->count()];
    });
}
$data = require base_path('e2e/support/fee-item-eligibility-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    return app(CurrentSchool::class)->run((int) getenv('BILLING_FIXTURE_SCHOOL'), fn (): array => $data + ['consents' => FeeItemConsent::count()]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    GuardianLink::whereIn('guardian_user_id', $data['parents'])->whereIn('learner_id', array_column($data['learners'], 'id'))->update(['can_approve' => true]);
    foreach (User::whereIn('id', $data['parents'])->get() as $parent) {
        $parent->forceFill(['status' => 1, 'password_change_required' => false])->save();
        $parent->assignRole('Parent');
    }

    return $data;
});

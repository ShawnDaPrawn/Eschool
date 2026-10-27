<?php

declare(strict_types=1);

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Money\FeeItemBatch;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\FeeItems;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Automatic item fixtures require an isolated database.');
}
if (getenv('BILLING_FIXTURE_ACTION') === 'disable_memberships') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function (): array {
        $ids = ActivityGroup::where('discipline', 'Transport')->pluck('id');
        foreach (ActivityMembership::whereIn('activity_group_id', $ids)->get() as $member) {
            $member->update(['active' => false, 'revision' => $member->revision + 1]);
        }

        return ['memberships_disabled' => true];
    });
}
$data = require base_path('e2e/support/fee-items-fixture.php');
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['batches' => FeeItemBatch::count()]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $item = app(FeeItems::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic family route fee', 'scope' => 'family', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active']);
    $group = ActivityGroup::create(['session_year_id' => $data['year'], 'name' => 'Synthetic transport route', 'discipline' => 'Transport', 'season' => 'Synthetic current year', 'starts_on' => substr($data['date'], 0, 4).'-01-01', 'ends_on' => substr($data['date'], 0, 4).'-12-31', 'active' => true, 'revision' => 1]);
    foreach ($data['learners'] as $learner) {
        ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $learner['id'], 'role' => 'member', 'active' => true, 'starts_on' => $data['date'], 'ends_on' => substr($data['date'], 0, 4).'-12-31', 'reason' => 'Synthetic billable route membership', 'revision' => 1]);
    }

    return $data + ['item' => (int) $item->id, 'group' => (int) $group->id];
});

<?php

declare(strict_types=1);

use App\Models\Money\FeeReminder;
use App\Models\Money\FeeReminderPolicy;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeItems;
use App\Services\Money\FeeReminders;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$action = getenv('BILLING_FIXTURE_ACTION');
if ($action === 'run') {
    $database = (string) config('database.connections.'.config('database.default').'.database');
    if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
        throw new RuntimeException('Reminders fixture requires isolated test data.');
    }
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn () => app(FeeReminders::class)->run());
}
$data = require base_path('e2e/support/fee-payments-fixture.php');
if ($action === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, fn (): array => $data + ['reminders' => FeeReminder::count(), 'policy_revision' => FeeReminderPolicy::first()?->revision ?? 0]);
}

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $actor = User::where('email', $data['finance'])->firstOrFail();
    $actor->roles()->firstOrFail()->givePermissionTo('fee-items-manage');
    $item = app(FeeItems::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic overdue reminder item', 'scope' => 'family', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'state' => 'active']);
    $input = ['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $data['account'], 'session_year_id' => $data['year'], 'learner_id' => null,
        'charged_on' => substr($data['date'], 0, 4).'-01-01', 'due_on' => now('Africa/Johannesburg')->subDays(8)->toDateString(), 'source_reference' => 'REMINDER-'.$data['school']];
    $review = app(FeeCharges::class)->preview($actor, $input);
    app(FeeCharges::class)->post($actor, $input + ['fingerprint' => $review['fingerprint'], 'confirmed' => true]);
    // Browser tests exercise in-app intents only, with no external transports.
    foreach (['push', 'email'] as $channel) {
        app(NotificationRules::class)->change($actor, 'parent', FeeReminders::TYPE, $channel, 'off');
    }

    return $data;
});

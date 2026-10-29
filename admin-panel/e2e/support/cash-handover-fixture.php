<?php

declare(strict_types=1);

use App\Models\Money\CashHandover;
use App\Models\Money\CashHandoverOutcome;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Money\CashCustody;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$action = getenv('BILLING_FIXTURE_ACTION');
if ($action === 'stats') {
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) getenv('BILLING_FIXTURE_SCHOOL'));

    return app(CurrentSchool::class)->run($school, function (): array {
        $rows = CashHandover::get();
        $first = $rows->firstOrFail();

        return ['handovers' => $rows->count(), 'outcomes' => CashHandoverOutcome::count(), 'sender' => app(CashCustody::class)->available($first->sender_id, 'ZAR'), 'recipient' => app(CashCustody::class)->available($first->recipient_id, 'ZAR'), 'entries' => DB::table('ledger_entries')->where('school_id', app(CurrentSchool::class)->id())->count()];
    });
}
$data = require base_path('e2e/support/cashup-fixture.php');

return app(CurrentSchool::class)->run($data['school'], function () use ($data): array {
    $sender = User::where('email', $data['finance'])->firstOrFail();
    $recipient = User::create(['first_name' => 'Synthetic', 'last_name' => 'Receiving cashier', 'email' => 'cash.receiver.'.Str::uuid().'@example.test', 'password' => bcrypt('NavigateDemo2026'), 'status' => 1, 'password_change_required' => false]);
    $recipient->assignRole($sender->roles()->firstOrFail());

    return $data + ['recipient' => $recipient->email, 'recipient_id' => (int) $recipient->id];
});

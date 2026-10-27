<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeReminder;
use App\Models\Money\FeeReminderPolicy;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class FeeReminders
{
    public const TYPE = 'fees.ledger_reminder';

    public function run(): array
    {
        $school = app(CurrentSchool::class)->school();
        if ($school === null || $school->status !== 'active') {
            return ['created' => 0, 'skipped' => 0];
        }
        app(ModuleGate::class)->forget();
        $policy = FeeReminderPolicy::first();
        $now = now($school->timezone ?? 'UTC');
        if ($policy === null || ! $policy->enabled || ! app(ModuleGate::class)->writable('fees') || $now->hour < $policy->local_hour) {
            return ['created' => 0, 'skipped' => 0];
        }
        $counts = ['created' => 0, 'skipped' => 0];
        $ids = SchoolMembership::active()->where('school_id', $school->id)->where('role', 'Parent')->select('user_id');
        User::whereIn('id', $ids)->orderBy('id')->chunkById(100, function ($parents) use (&$counts): void {
            foreach ($parents as $parent) {
                try {
                    $counts['created'] += $this->parent($parent);
                } catch (ModelNotFoundException) {
                    $counts['skipped']++;
                } catch (HttpExceptionInterface $e) {
                    if (! in_array($e->getStatusCode(), [403, 404], true)) {
                        throw $e;
                    }$counts['skipped']++;
                }
            }
        });

        return $counts;
    }

    public function parent(User $parent): int
    {
        return DB::transaction(function () use ($parent): int {
            $school = School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(CurrentSchool::class)->school()->refresh();
            app(ModuleGate::class)->forget();
            $policy = FeeReminderPolicy::lockForUpdate()->first();
            $now = now($school->timezone ?? 'UTC');
            $today = $now->toDateString();
            if ($policy === null || ! $policy->enabled || $school->status !== 'active' || ! app(ModuleGate::class)->writable('fees', locked: true) || $now->hour < $policy->local_hour) {
                return 0;
            }
            $accounts = app(ParentFeeStatements::class)->reminderAccounts($parent, $policy->min_days_overdue);
            $created = 0;
            foreach ($accounts as $account) {
                $prior = FeeReminder::where('fee_account_id', $account)->where('recipient_id', $parent->id)->orderByDesc('issued_on')->lockForUpdate()->first();
                if ($prior !== null && ($prior->issued_on->toDateString() >= $today || FeeAgeing::days($prior->issued_on->toDateString(), $today) < $policy->cadence_days)) {
                    continue;
                }
                $reminder = FeeReminder::create(['fee_account_id' => $account, 'recipient_id' => $parent->id, 'policy_revision' => $policy->revision, 'issued_on' => $today,
                    'expires_at' => $now->copy()->addDays(min(30, $policy->cadence_days))->utc(), 'created_at' => now('UTC')]);
                $notice = app(Notifier::class)->makeNotice(self::TYPE, __('Fee statement available'), __('Open NavigateEd to review your fee statement.'), '/fee-statements');
                $notice->forceFill(['delivery_payload' => ['link' => '/fee-statements', 'fee_reminder_id' => (int) $reminder->id]])->save();
                $reminder->update(['notification_id' => $notice->id, 'bound_at' => now('UTC')]);
                app(Notifier::class)->assign($notice, [(int) $parent->id]);
                Audit::record('money.fee_reminder_queued', $reminder, [], ['fee_account_id' => $account, 'policy_revision' => $policy->revision]);
                $created++;
            }

            return $created;
        }, 3);
    }
}

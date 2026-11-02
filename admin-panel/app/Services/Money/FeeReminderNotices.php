<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeReminder;
use App\Models\Money\FeeReminderPolicy;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\Money\ParentFeePolicy;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Saas\PlatformPackages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Inbox retains generic prompts until expiry; delivery rechecks current overdue shares. */
final class FeeReminderNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        app(ParentFeePolicy::class)->authorize($actor);
        app(ModuleGate::class)->forget();
        if (! app(ModuleGate::class)->writable('fees')) {
            $notifications->whereRaw('1 = 0');

            return;
        }
        $school = app(CurrentSchool::class)->id();
        // Keep prepared count/list queries scoped after authority changes.
        $authority = DB::table('users as parent')->where('parent.id', $actor->id)->where('parent.status', 1)->where('parent.password_change_required', false)->whereNull('parent.deleted_at')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('platform_staff_identities as support')->where('support.user_id', $actor->id)->where('support.active', true))
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->where('mr.school_id', $school)->where('r.school_id', $school)->where('mr.model_id', $actor->id)->where('mr.model_type', $actor->getMorphClass())->where('r.name', 'Parent'))
            ->whereExists(fn ($q) => app(PlatformPackages::class)->whereSchoolPackageIncludes(
                $q->selectRaw('1')->from('schools as school')->where('school.id', $school)->where('school.status', 'active'), 'school', 'fees'))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('school_modules as module')->where('module.school_id', $school)->where('module.module', 'fees')
                ->where(fn ($q) => $q->where('module.enabled', false)->orWhere('module.read_only', true)->orWhere('module.in_package', false)));
        $reminders = FeeReminder::whereExists($authority->selectRaw('1'))->where('recipient_id', $actor->id)->where('expires_at', '>', now('UTC'))->whereNotNull('notification_id')
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('fee_reminder_policies as policy')->where('policy.school_id', $school)->where('policy.enabled', true))
            ->whereIn('recipient_id', SchoolMembership::active()->where('school_id', $school)->where('role', 'Parent')->select('user_id'))
            ->whereExists(function ($q) use ($school, $actor): void {
                $q->selectRaw('1')->from('invoice_lines as l')->join('invoices as i', function ($j) {
                    $j->on('i.school_id', '=', 'l.school_id')->on('i.id', '=', 'l.invoice_id');
                })
                    ->join('bill_runs as b', function ($j) {
                        $j->on('b.school_id', '=', 'i.school_id')->on('b.id', '=', 'i.bill_run_id');
                    })
                    ->join('guardian_links as g', function ($j) {
                        $j->on('g.school_id', '=', 'l.school_id')->on('g.learner_id', '=', 'l.learner_id');
                    })
                    ->join('students as s', function ($j) {
                        $j->on('s.school_id', '=', 'l.school_id')->on('s.id', '=', 'l.learner_id');
                    })->join('users as u', 'u.id', '=', 's.user_id')
                    ->where('l.school_id', $school)->whereColumn('i.fee_account_id', 'fee_reminders.fee_account_id')->whereNotNull('i.sealed_at')->where('b.state', 'posted')
                    ->where('g.guardian_user_id', $actor->id)->where('g.status', 'active')->where('g.can_see_fees', true)->whereNull('s.deleted_at')->whereNull('u.deleted_at')
                    ->whereRaw("JSON_CONTAINS(l.holder_shares, JSON_OBJECT('user_id', ?))", [(int) $actor->id]);
            })->select('notification_id');
        $notifications->where('type', FeeReminders::TYPE)->whereIn($notifications->qualifyColumn('id'), $reminders);
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        $id = $payload['fee_reminder_id'] ?? null;
        if ($type !== FeeReminders::TYPE || ($payload['link'] ?? null) !== '/fee-statements' || (! is_int($id) && ! is_string($id)) || ! preg_match('/\A[1-9][0-9]*\z/D', (string) $id)) {
            return false;
        }
        try {
            return DB::transaction(function () use ($actor, $id): bool {
                School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                app(CurrentSchool::class)->school()->refresh();
                $policy = FeeReminderPolicy::lockForUpdate()->first();
                app(ModuleGate::class)->forget();
                if ($policy === null || ! $policy->enabled || ! app(ModuleGate::class)->writable('fees', locked: true)) {
                    return false;
                }
                $reminder = FeeReminder::where('recipient_id', $actor->id)->where('expires_at', '>', now('UTC'))->whereNotNull('notification_id')->find($id);

                return $reminder !== null && in_array($reminder->fee_account_id, app(ParentFeeStatements::class)->reminderAccounts($actor, $policy->min_days_overdue), true);
            });
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}

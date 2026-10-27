<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\Money\FeeReminder;
use App\Models\Money\FeeReminderPolicy;
use App\Models\Money\InvoiceLine;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeeItems;
use App\Services\Money\FeePayments;
use App\Services\Money\FeeReminderNotices;
use App\Services\Money\FeeReminders;
use App\Services\Money\FeeReminderSettings;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeScopes;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FeeRemindersTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private FeeAccount $account;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        Enrolment::where('session_year_id', $this->year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic receipt allocation']);
        }
        foreach (['asha', 'cara'] as $learner) {
            foreach (['farid', 'mona'] as $parent) {
                GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->schoolA->ids[$parent.'_user']],
                    ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
            }
        }
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['gina_user']],
            ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        $this->account = app(FeeAccounts::class)->save($this->admin, $this->family());
        Bus::fake([DispatchOutbox::class]);
    }

    public function test_reminders_are_off_until_finance_configures_a_schedule_and_never_post_money(): void
    {
        $this->charge();
        $before = DB::table('ledger_entries')->count();
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        $policy = $this->settings();
        $this->assertTrue($policy->enabled);
        $this->assertSame(2, app(FeeReminders::class)->run()['created']);
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertSame(2, DB::table('domain_events')->where('type', 'notification.requested')->count());
        foreach (Notification::where('type', FeeReminders::TYPE)->get() as $notice) {
            $this->assertSame('Fee statement available', $notice->title);
            $this->assertSame('Open NavigateEd to review your fee statement.', $notice->message);
            $this->assertSame(['link', 'fee_reminder_id'], array_keys($notice->delivery_payload));
            $this->assertSame('/fee-statements', $notice->delivery_payload['link']);
            $this->assertStringNotContainsString('Synthetic split family', json_encode($notice->delivery_payload));
        }
    }

    public function test_school_calendar_hour_minimum_overdue_and_cadence_boundaries_are_exact(): void
    {
        $this->charge('2026-06-08');
        $this->settings(['local_hour' => 9, 'min_days_overdue' => 7, 'cadence_days' => 7]);
        $this->travelTo(now('UTC')->setDateTime(2026, 6, 15, 6, 59, 0));
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        $this->travelTo(now('UTC')->setDateTime(2026, 6, 15, 7, 0, 0));
        $this->assertSame(2, app(FeeReminders::class)->run()['created']);
        $this->travelTo(now('UTC')->setDateTime(2026, 6, 21, 21, 59, 0));
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        $this->travelTo(now('UTC')->setDateTime(2026, 6, 22, 7, 0, 0));
        $this->assertSame(2, app(FeeReminders::class)->run()['created']);
        $this->assertSame(['2026-06-15', '2026-06-22'], FeeReminder::select('issued_on')->distinct()->pluck('issued_on')->map(fn ($v) => $v->toDateString())->all());
    }

    public function test_due_today_future_and_not_yet_minimum_days_do_not_prompt(): void
    {
        foreach (['2026-06-15', '2026-06-16', '2026-06-09'] as $due) {
            $this->charge($due);
        }
        $this->settings(['min_days_overdue' => 7]);
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        $this->charge('2026-06-08');
        $this->assertSame(2, app(FeeReminders::class)->run()['created']);
    }

    public function test_only_current_holders_with_visible_children_get_their_own_prompt(): void
    {
        $this->charge();
        $this->settings();
        $farid = $this->parent();
        GuardianLink::where('guardian_user_id', $farid->id)->update(['can_see_fees' => false]);
        $this->assertSame(1, app(FeeReminders::class)->run()['created']);
        $this->assertSame([$this->schoolA->ids['mona_user']], FeeReminder::pluck('recipient_id')->all());
        $this->assertSame(0, app(FeeReminders::class)->parent(User::findOrFail($this->schoolA->ids['gina_user'])));
        $this->refuses(fn () => app(FeeReminders::class)->parent($this->admin), 403);
        $this->refuses(fn () => app(FeeReminders::class)->parent(User::findOrFail($this->schoolA->ids['tara_user'])), 403);
    }

    public function test_delivery_and_prepared_inbox_recheck_revoked_guardian_links(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $notice = $this->notice($actor);
        $query = app(PrivateNoticeScopes::class)->apply(Notification::query(), $actor->id)->where('type', FeeReminders::TYPE);
        $this->assertSame(1, (clone $query)->count());
        $this->assertTrue(app(FeeReminderNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
        GuardianLink::where('guardian_user_id', $actor->id)->update(['status' => 'ended']);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(FeeReminderNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
        $this->assertSame([], app(Notifier::class)->send($actor, $notice->type, $notice->title, $notice->message, '/fee-statements', $notice, ['in_app']));
    }

    public function test_full_credit_suppresses_queued_delivery_without_destroying_history(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $notice = $this->notice($actor);
        foreach (InvoiceLine::all() as $line) {
            $share = collect($line->holder_shares)->firstWhere('user_id', $actor->id);
            $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $actor->id,
                'amount' => sprintf('%d.%02d', intdiv($share['amount_cents'], 100), $share['amount_cents'] % 100), 'reason_code' => 'billing_error', 'reason' => 'Synthetic complete correction'];
            $review = app(FeeCreditNotes::class)->preview($this->admin, $input);
            app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $review['fingerprint'], 'confirmed' => true]);
        }
        $this->assertFalse(app(FeeReminderNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        $this->assertSame(2, FeeReminder::count());
    }

    public function test_other_owner_real_school_and_bad_payloads_cannot_deliver_private_notices(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $notice = $this->notice($actor);
        $guard = app(FeeReminderNotices::class);
        foreach (['mona_user', 'gina_user', 'tara_user', 'asha_user'] as $key) {
            $this->assertFalse($guard->allows(User::findOrFail($this->schoolA->ids[$key]), $notice->type, $notice->delivery_payload));
        }
        $this->assertFalse(app(CurrentSchool::class)->run(2, fn () => $guard->allows($actor, $notice->type, $notice->delivery_payload)));
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => FeeReminder::count()));
        foreach ([[], ['fee_reminder_id' => []], ['fee_reminder_id' => '1e0'], ['fee_reminder_id' => 0], array_replace($notice->delivery_payload, ['link' => 'https://example.invalid'])] as $payload) {
            $this->assertFalse($guard->allows($actor, $notice->type, $payload));
        }
        $this->assertFalse($guard->allows($actor, 'fees.other', $notice->delivery_payload));
    }

    public function test_schedule_disable_and_module_readonly_suppress_generation_inbox_and_delivery(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $notice = $this->notice($actor);
        $query = app(PrivateNoticeScopes::class)->apply(Notification::query(), $actor->id)->where('type', FeeReminders::TYPE);
        $this->settings(['revision' => 1, 'enabled' => false]);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(FeeReminderNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
        $this->settings(['revision' => 2, 'enabled' => true]);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        $this->assertFalse(app(FeeReminderNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
        $this->actingAs($this->admin)->get('/fee-reminders')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/fee-reminders', $this->input(['revision' => 3]) + ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1])->assertForbidden();
    }

    public function test_school_rules_and_parent_notification_preferences_remain_authoritative(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $notice = $this->notice($actor);
        $rules = app(NotificationRules::class);
        $this->assertTrue($rules->allows($actor, FeeReminders::TYPE, 'in_app'));
        $this->assertFalse($rules->allows($actor, FeeReminders::TYPE, 'email'));
        $rules->choose($actor, FeeReminders::TYPE, 'in_app', false);
        $this->assertSame([], app(Notifier::class)->send($actor, $notice->type, $notice->title, $notice->message, '/fee-statements', $notice, ['in_app']));
        $rules->choose($actor, FeeReminders::TYPE, 'in_app', true);
        $rules->change($this->admin, 'parent', FeeReminders::TYPE, 'in_app', 'off');
        $this->assertSame([], app(Notifier::class)->send($actor, $notice->type, $notice->title, $notice->message, '/fee-statements', $notice, ['in_app']));
    }

    public function test_notification_retention_preserves_cadence_and_intent_cannot_be_rebound_or_deleted(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $notice = $this->notice($actor);
        $reminder = FeeReminder::where('recipient_id', $actor->id)->firstOrFail();
        $notice->forceDelete();
        $this->assertNull($reminder->fresh()->notification_id);
        $this->assertNotNull($reminder->fresh()->bound_at);
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
        foreach ([fn () => DB::table('fee_reminders')->where('id', $reminder->id)->update(['issued_on' => '2026-06-14']), fn () => DB::table('fee_reminders')->where('id', $reminder->id)->delete()] as $write) {
            try {
                $write();
                $this->fail('Immutable reminder history');
            } catch (QueryException $e) {
                $this->assertStringContainsString('Fee reminder', $e->getMessage());
            }
        }
        $migration = require database_path('migrations/2026_10_27_700000_create_fee_reminders.php');
        try {
            $migration->down();
            $this->fail('Populated rollback must refuse');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retain fee reminder', $e->getMessage());
        }
    }

    public function test_late_notice_failure_rolls_back_intent_notice_outbox_and_retry_is_safe(): void
    {
        $this->charge();
        $this->settings();
        $before = [FeeReminder::count(), Notification::count(), DB::table('domain_events')->count()];
        $dispatcher = Notification::getEventDispatcher();
        Notification::setEventDispatcher(clone $dispatcher);
        Notification::created(static function (): void {
            throw new \RuntimeException('Synthetic late notification failure');
        });
        try {
            app(FeeReminders::class)->parent($this->parent());
            $this->fail('Expected atomic rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('Synthetic late notification failure', $e->getMessage());
        } finally {
            Notification::setEventDispatcher($dispatcher);
        }
        $this->assertSame($before, [FeeReminder::count(), Notification::count(), DB::table('domain_events')->count()]);
        $this->assertSame(1, app(FeeReminders::class)->parent($this->parent()));
        $this->assertSame(0, app(FeeReminders::class)->parent($this->parent()));
    }

    public function test_finance_panel_revisions_private_binding_wrong_role_and_school_isolation(): void
    {
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $input = $this->input();
        $this->actingAs($this->admin)->get('/fee-reminders')->assertOk()->assertSee('Fee reminder schedule')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/fee-reminders', $input + $bind)->assertStatus(303);
        $this->post('/fee-reminders', $input + $bind)->assertStatus(303);
        $this->assertSame(1, FeeReminderPolicy::firstOrFail()->revision);
        $this->post('/fee-reminders', $this->input() + $bind)->assertStatus(409);
        $this->post('/fee-reminders', $this->input(['revision' => 1]) + ['expected_account_id' => $this->admin->id, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-reminders?unsafe=1', $this->input(['revision' => 1]) + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/fee-reminders')->assertForbidden();
        $this->refuses(fn () => app(FeeReminderSettings::class)->save($teacher, $this->input()), 403);
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => FeeReminderPolicy::count()));
    }

    public function test_prepared_inbox_drops_a_revoked_parent_role(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $query = app(PrivateNoticeScopes::class)->apply(Notification::query(), $actor->id)->where('type', FeeReminders::TYPE);
        $this->assertSame(1, (clone $query)->count());
        DB::table('model_has_roles')->where('school_id', 1)->where('model_id', $actor->id)->delete();
        $this->assertSame(0, (clone $query)->count());
    }

    public function test_prepared_inbox_drops_parent_password_reset_and_module_readonly(): void
    {
        $this->charge();
        $this->settings();
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $query = app(PrivateNoticeScopes::class)->apply(Notification::query(), $actor->id)->where('type', FeeReminders::TYPE);
        $this->assertSame(1, (clone $query)->count());
        User::whereKey($actor->id)->update(['password_change_required' => true]);
        $this->assertSame(0, (clone $query)->count());
        User::whereKey($actor->id)->update(['password_change_required' => false]);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->assertSame(0, (clone $query)->count());
    }

    public function test_expired_prompt_is_hidden_and_does_not_bypass_cadence(): void
    {
        $this->charge();
        $this->settings(['cadence_days' => 60]);
        app(FeeReminders::class)->run();
        $actor = $this->parent();
        $notice = $this->notice($actor);
        $this->travelTo(now('UTC')->addDays(31));
        $this->assertFalse(app(FeeReminderNotices::class)->allows($actor, $notice->type, $notice->delivery_payload));
        $this->assertSame(0, app(PrivateNoticeScopes::class)->apply(Notification::query(), $actor->id)->where('type', FeeReminders::TYPE)->count());
        $this->assertSame(0, app(FeeReminders::class)->run()['created']);
    }

    public function test_actual_foreign_school_notice_cannot_be_bound_to_this_school_intent(): void
    {
        $this->charge();
        $this->settings();
        $notice = app(CurrentSchool::class)->run(2, fn () => app(Notifier::class)->makeNotice(FeeReminders::TYPE, 'Baobab', 'Baobab', '/fee-statements'));
        $reminder = FeeReminder::create(['fee_account_id' => $this->account->id, 'recipient_id' => $this->parent()->id, 'policy_revision' => 1, 'issued_on' => '2026-06-15', 'created_at' => now('UTC'), 'expires_at' => now('UTC')->addDays(7)]);
        try {
            $reminder->update(['notification_id' => $notice->id, 'bound_at' => now('UTC')]);
            $this->fail('Foreign notice must not bind');
        } catch (QueryException $e) {
            $this->assertStringContainsString('private fee notice', $e->getMessage());
        }
        $this->assertNull($reminder->fresh()->notification_id);
    }

    private function parent(): User
    {
        return User::findOrFail($this->schoolA->ids['farid_user']);
    }

    private function notice(User $actor): Notification
    {
        return Notification::findOrFail(FeeReminder::where('recipient_id', $actor->id)->firstOrFail()->notification_id);
    }

    private function input(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'min_days_overdue' => 7, 'cadence_days' => 7, 'local_hour' => 9], $changes);
    }

    private function settings(array $changes = []): FeeReminderPolicy
    {
        return app(FeeReminderSettings::class)->save($this->admin, $this->input($changes));
    }

    private function family(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
    }

    private function charge(string $due = '2026-03-01', string $currency = 'ZAR', ?int $accountId = null): void
    {
        $item = app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic report charge', 'scope' => 'family', 'amount' => '10.01', 'currency' => $currency, 'tax_treatment' => 'no_vat', 'state' => 'active']);
        $input = ['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $accountId ?? $this->account->id, 'session_year_id' => $this->year,
            'learner_id' => null, 'charged_on' => '2026-01-01', 'due_on' => $due, 'source_reference' => 'REPORT-'.Str::uuid()];
        $preview = app(FeeCharges::class)->preview($this->admin, $input);
        app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
    }

    private function receipt(string $amount = '4.00', ?int $pin = null): FeePayment
    {
        return app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => $pin,
            'method' => 'eft', 'source_reference' => 'REPORT-'.Str::uuid(), 'amount' => $amount, 'received_on' => '2026-06-15', 'confirmed' => true]);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected financial operation refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }
}

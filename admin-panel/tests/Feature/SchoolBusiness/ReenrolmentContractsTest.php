<?php

declare(strict_types=1);

namespace Tests\Feature\SchoolBusiness;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SchoolBusiness\BusinessOperation;
use App\Models\SchoolBusiness\ReenrolmentAcceptance;
use App\Models\SchoolBusiness\ReenrolmentConfirmation;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\People\GradePromotion;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\ReenrolmentContracts;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ReenrolmentContractsTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    private int $next;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->next = (int) DB::table('session_years')->insertGetId(['school_id' => 1, 'name' => 'Next year', 'start_date' => '2027-01-15', 'end_date' => '2027-12-10', 'default' => 0, 'created_at' => now(), 'updated_at' => now()]);
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        Enrolment::where('session_year_id', $year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $year, 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic re-enrolment fixture']);
        }
        // Asha: Farid pays fees, Mona does not. Ben: Gina pays fees.
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user']], ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['mona_user']], ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => false, 'can_approve' => true]);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['gina_user']], ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        User::whereIn('id', [$this->schoolA->ids['farid_user'], $this->schoolA->ids['mona_user'], $this->schoolA->ids['gina_user']])->update(['email_verified_at' => now()]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'reenrolment'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
    }

    private function user(string $name): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    private function publish(string $rule = 'all_fee_payers', array $changes = []): array
    {
        return app(ReenrolmentContracts::class)->publish($this->admin, array_replace(['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->next,
            'title' => 'Re-enrolment contract 2027', 'body' => 'We agree to the school fees and the code of conduct for 2027.', 'acceptance_rule' => $rule, 'respond_by' => '2026-09-30'], $changes));
    }

    private function accept(string $who, string $learner, array $contract, array $changes = []): array
    {
        $person = $this->user($who);

        return app(ReenrolmentContracts::class)->accept($person, $this->schoolA->ids[$learner], array_replace(['operation_id' => (string) Str::uuid(), 'contract_id' => $contract['id'],
            'version' => $contract['version'], 'text_hash' => $contract['text_hash'], 'signed_name' => $person->full_name, 'acknowledged' => true], $changes), '203.0.113.7');
    }

    /** @param int|list<int> $status */
    private function refuses(callable $call, int|array $status): void
    {
        try {
            $call();
            $this->fail('Expected HTTP '.json_encode($status));
        } catch (HttpExceptionInterface $error) {
            $this->assertContains($error->getStatusCode(), (array) $status, $error->getMessage());
        } catch (ModelNotFoundException) {
            // Rendered as 404 over HTTP.
            $this->assertContains(404, (array) $status);
        }
    }

    public function test_fee_payer_accepts_exact_version_records_evidence_and_confirms_once(): void
    {
        $contract = $this->publish();
        $this->assertSame(1, $contract['version']);
        $this->assertSame(hash('sha256', "Re-enrolment contract 2027\n\nWe agree to the school fees and the code of conduct for 2027."), $contract['text_hash']);
        $view = app(ReenrolmentContracts::class)->forGuardian($this->user('farid'), $this->schoolA->ids['asha']);
        $this->assertSame('awaiting', $view['contracts'][0]['status']);
        $this->assertTrue($view['contracts'][0]['you_are_fee_responsible']);
        $result = $this->accept('farid', 'asha', $contract);
        $this->assertSame('confirmed', $result['status']);
        $row = ReenrolmentAcceptance::firstOrFail();
        $this->assertSame([$this->schoolA->ids['farid_user'], 1, $contract['text_hash'], '203.0.113.7', true], [$row->guardian_user_id, $row->version, $row->text_hash, $row->ip_address, $row->fee_responsible]);
        $this->assertNotNull($row->accepted_at);
        $this->assertSame(1, ReenrolmentConfirmation::count());
        // Statuses read by the year-end rollover, without changing it.
        $statuses = app(ReenrolmentContracts::class)->statuses(SessionYear::findOrFail($this->next), [$this->schoolA->ids['asha'], $this->schoolA->ids['ben']]);
        $this->assertSame([$this->schoolA->ids['asha'] => 'confirmed', $this->schoolA->ids['ben'] => 'awaiting'], $statuses);
        // Accepted rows cannot be changed.
        $this->expectException(QueryException::class);
        $row->update(['ip_address' => '198.51.100.1']);
    }

    public function test_non_fee_responsible_guardian_cannot_accept_alone_but_may_join_after_the_payer(): void
    {
        $contract = $this->publish('fee_payer');
        $this->refuses(fn () => $this->accept('mona', 'asha', $contract), 409);
        $this->assertSame(0, ReenrolmentAcceptance::count());
        $view = app(ReenrolmentContracts::class)->forGuardian($this->user('mona'), $this->schoolA->ids['asha']);
        $this->assertTrue($view['contracts'][0]['waiting_for_fee_payer']);
        $this->accept('farid', 'asha', $contract);
        $this->assertSame('confirmed', $this->accept('mona', 'asha', $contract)['status']);
        $this->assertFalse(ReenrolmentAcceptance::where('guardian_user_id', $this->schoolA->ids['mona_user'])->firstOrFail()->fee_responsible);
    }

    public function test_any_guardian_rule_lets_an_approving_guardian_accept_and_all_payers_rule_waits_for_each_payer(): void
    {
        $contract = $this->publish('any_guardian');
        $this->assertSame('confirmed', $this->accept('mona', 'asha', $contract)['status']);
        // A new version needs new acceptances; both payers on the family account must accept.
        $account = FeeAccount::create(['reference' => 'SYN-REENROL', 'name' => 'Synthetic family', 'currency' => 'ZAR', 'active' => true, 'revision' => 1]);
        FeeAccountLearner::create(['fee_account_id' => $account->id, 'learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year']]);
        foreach (['farid' => 5000, 'mona' => 5000] as $who => $share) {
            FeeAccountHolder::create(['fee_account_id' => $account->id, 'session_year_id' => $this->schoolA->ids['session_year'], 'user_id' => $this->schoolA->ids[$who.'_user'], 'share_bp' => $share]);
        }
        $second = $this->publish('all_fee_payers', ['body' => 'We agree to the revised school fees and the code of conduct for 2027.']);
        $this->assertSame(2, $second['version']);
        $this->assertSame('superseded', DB::table('reenrolment_contracts')->where('id', $contract['id'])->value('state'));
        $this->refuses(fn () => $this->accept('farid', 'asha', $contract), 409);
        $this->assertSame('awaiting', $this->accept('farid', 'asha', $second)['status']);
        $this->assertSame('confirmed', $this->accept('mona', 'asha', $second)['status']);
        $this->assertSame(['confirmed'], array_values(app(ReenrolmentContracts::class)->statuses(SessionYear::findOrFail($this->next), [$this->schoolA->ids['asha']])));
    }

    public function test_changed_wording_name_and_wrong_guardian_are_refused(): void
    {
        $contract = $this->publish();
        $this->refuses(fn () => $this->accept('farid', 'asha', $contract, ['text_hash' => str_repeat('a', 64)]), 409);
        $this->refuses(fn () => $this->accept('farid', 'asha', $contract, ['signed_name' => 'Somebody Else']), 422);
        // Gina is not Asha's guardian; Farid's link without approval rights cannot act.
        $this->refuses(fn () => $this->accept('gina', 'asha', $contract), 404);
        GuardianLink::where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['can_approve' => false]);
        $this->refuses(fn () => $this->accept('farid', 'asha', $contract), 404);
        $this->refuses(fn () => app(ReenrolmentContracts::class)->forGuardian($this->user('gina'), $this->schoolA->ids['asha']), 404);
        $this->assertSame(0, ReenrolmentAcceptance::count());
    }

    public function test_wrong_role_and_wrong_school_and_disabled_module_are_refused(): void
    {
        $this->refuses(fn () => app(ReenrolmentContracts::class)->publish($this->user('tara'), ['operation_id' => (string) Str::uuid()]), 403);
        $this->refuses(fn () => app(ReenrolmentContracts::class)->publish($this->user('farid'), ['operation_id' => (string) Str::uuid()]), 403);
        $contract = $this->publish();
        $this->refuses(fn () => app(ReenrolmentContracts::class)->outstanding($this->user('tara'), $contract['id']), 403);
        app(CurrentSchool::class)->run(2, function () use ($contract) {
            SchoolModule::updateOrCreate(['school_id' => 2, 'module' => 'reenrolment'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
            $other = User::findOrFail($this->schoolB->ids['farid_user']);
            User::whereKey($other->id)->update(['email_verified_at' => now()]);
            $other->refresh();
            Enrolment::where('learner_id', $this->schoolB->ids['asha'])->delete();
            Enrolment::create(['learner_id' => $this->schoolB->ids['asha'], 'class_section_id' => $this->schoolB->ids['section_a'], 'session_year_id' => $this->schoolB->ids['session_year'],
                'starts_on' => now()->toDateString(), 'legacy_dates' => false, 'reason' => 'Synthetic re-enrolment fixture']);
            // School B cannot see or accept School A's contract.
            $this->assertSame([], app(ReenrolmentContracts::class)->forGuardian($other, $this->schoolB->ids['asha'])['contracts']);
            $this->refuses(fn () => app(ReenrolmentContracts::class)->accept($other, $this->schoolB->ids['asha'], ['operation_id' => (string) Str::uuid(), 'contract_id' => $contract['id'],
                'version' => 1, 'text_hash' => $contract['text_hash'], 'signed_name' => $other->full_name, 'acknowledged' => true], '203.0.113.8'), 404);
            $this->refuses(fn () => app(ReenrolmentContracts::class)->accept($other, $this->schoolA->ids['asha'], ['operation_id' => (string) Str::uuid()], '203.0.113.8'), 404);
        });
        SchoolModule::where('school_id', 1)->where('module', 'reenrolment')->update(['enabled' => false]);
        $this->refuses(fn () => $this->accept('farid', 'asha', $contract), 404);
        $this->assertSame([], app(ReenrolmentContracts::class)->statuses(SessionYear::findOrFail($this->next), [$this->schoolA->ids['asha']]));
    }

    public function test_exact_retry_returns_the_first_receipt_and_changed_retry_conflicts(): void
    {
        $operation = (string) Str::uuid();
        $first = $this->publish('all_fee_payers', ['operation_id' => $operation]);
        $this->assertSame($first, $this->publish('all_fee_payers', ['operation_id' => $operation]));
        $this->refuses(fn () => $this->publish('fee_payer', ['operation_id' => $operation]), 409);
        $this->assertSame(1, DB::table('reenrolment_contracts')->count());
        $accept = (string) Str::uuid();
        $once = $this->accept('farid', 'asha', $first, ['operation_id' => $accept]);
        $this->assertSame($once, $this->accept('farid', 'asha', $first, ['operation_id' => $accept]));
        // A fresh operation for the same acceptance returns the same signed record.
        $this->assertSame($once['id'], $this->accept('farid', 'asha', $first)['id']);
        $this->assertSame(1, ReenrolmentAcceptance::count());
        $this->assertSame(1, ReenrolmentConfirmation::count());
        $receipt = BusinessOperation::where('operation_id', $accept)->firstOrFail()->receipt;
        $this->assertArrayNotHasKey('signed_name', $receipt);
    }

    public function test_outstanding_lists_awaited_payers_and_reminders_ask_fee_payers_once_a_day(): void
    {
        $contract = $this->publish('fee_payer');
        $outstanding = app(ReenrolmentContracts::class)->outstanding($this->admin, $contract['id']);
        $this->assertSame([3, 0, 3], [$outstanding['audience'], $outstanding['confirmed'], $outstanding['outstanding']]);
        $asha = collect($outstanding['items'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertSame([$this->schoolA->ids['farid_user']], array_column($asha['awaiting'], 'user_id'));
        $sent = app(ReenrolmentContracts::class)->remind($this->admin, $contract['id'], ['operation_id' => (string) Str::uuid()]);
        $notified = Notification::where('type', ReenrolmentContracts::NOTICE)->pluck('source_key')->all();
        $this->assertContains('reenrolment:'.$contract['id'].':'.$this->schoolA->ids['farid_user'].':2026-06-15', $notified);
        $this->assertContains('reenrolment:'.$contract['id'].':'.$this->schoolA->ids['gina_user'].':2026-06-15', $notified);
        $this->assertNotContains('reenrolment:'.$contract['id'].':'.$this->schoolA->ids['mona_user'].':2026-06-15', $notified, 'Fee payers are asked first');
        $this->assertSame(count($notified), $sent['notices']);
        foreach (Notification::where('type', ReenrolmentContracts::NOTICE)->get() as $notice) {
            $this->assertStringNotContainsString('Asha', $notice->title.$notice->message.(string) $notice->excerpt);
        }
        $this->assertSame(0, app(ReenrolmentContracts::class)->remind($this->admin, $contract['id'], ['operation_id' => (string) Str::uuid()])['notices']);
        $this->accept('farid', 'asha', $contract);
        $this->assertSame(1, app(ReenrolmentContracts::class)->outstanding($this->admin, $contract['id'])['confirmed']);
    }

    public function test_reminder_batches_move_past_the_first_learners_and_keep_the_daily_rule(): void
    {
        config(['school_business.reminder_batch' => 1]);
        $contract = $this->publish('fee_payer');
        $remind = fn () => app(ReenrolmentContracts::class)->remind($this->admin, $contract['id'], ['operation_id' => (string) Str::uuid()]);
        $key = fn (string $who) => 'reenrolment:'.$contract['id'].':'.$this->schoolA->ids[$who.'_user'].':2026-06-15';
        $first = $remind();
        $this->assertSame(1, $first['notices']);
        $this->assertTrue(Notification::where('source_key', $key('farid'))->exists());
        // The next request reaches Ben's fee payer, past the first batch.
        $second = $remind();
        $this->assertTrue(Notification::where('source_key', $key('gina'))->exists(), 'Learners past the first batch are reminded');
        $this->assertSame(1, $second['notices']);
        $this->assertTrue($first['more']);
        $third = $remind();
        $this->assertSame([0, false], [$third['notices'], $third['more']]);
        $this->assertSame(2, Notification::where('type', ReenrolmentContracts::NOTICE)->count(), 'Still one notice per guardian, contract and day');
    }

    public function test_grade_promotion_reads_reenrolment_without_changing_its_fingerprint(): void
    {
        $from = SessionYear::findOrFail($this->schoolA->ids['session_year']);
        $to = SessionYear::findOrFail($this->next);
        $before = app(GradePromotion::class)->plan($from, $to);
        $this->assertNull($before['reenrolment']);
        $contract = $this->publish('fee_payer');
        $this->accept('farid', 'asha', $contract);
        $after = app(GradePromotion::class)->plan($from, $to);
        $this->assertSame($before['fingerprint'], $after['fingerprint']);
        $this->assertSame(['confirmed' => 1, 'awaiting' => 2], $after['reenrolment']);
        $rows = collect($after['homerooms'])->flatMap(fn ($room) => $room['learners'])->keyBy('id');
        $this->assertSame('confirmed', $rows[$this->schoolA->ids['asha']]['reenrolment']);
        $this->assertSame($before['totals'], $after['totals']);
    }

    public function test_api_guardian_view_and_acceptance_match_the_contract(): void
    {
        $contract = $this->publish();
        $farid = $this->user('farid');
        Sanctum::actingAs($farid);
        $asha = $this->schoolA->ids['asha'];
        $this->getJson("/api/v2/reenrolment/learners/$asha", ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.contracts.0.version', 1)->assertJsonPath('data.contracts.0.can_accept', true);
        $this->postJson("/api/v2/reenrolment/learners/$asha/acceptances", ['operation_id' => (string) Str::uuid(), 'contract_id' => $contract['id'], 'version' => 1,
            'text_hash' => $contract['text_hash'], 'signed_name' => $farid->full_name, 'acknowledged' => true], ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.status', 'confirmed');
        $this->getJson('/api/v2/reenrolment/contracts', ['X-School-Id' => '1'])->assertStatus(403);
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v2/reenrolment/contracts', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.items.0.confirmed', 1);
        $this->getJson('/api/v2/reenrolment/contracts/'.$contract['id'].'/outstanding', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.outstanding', 2);
        $this->postJson('/api/v2/reenrolment/contracts/'.$contract['id'].'/reminders', ['operation_id' => (string) Str::uuid()], ['X-School-Id' => '1'])->assertOk();
        $this->postJson('/api/v2/reenrolment/contracts/'.$contract['id'].'/withdraw', ['operation_id' => (string) Str::uuid(), 'revision' => 1], ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.state', 'withdrawn');
        $this->postJson('/api/v2/reenrolment/contracts', ['operation_id' => (string) Str::uuid(), 'unknown' => 1], ['X-School-Id' => '1'])->assertStatus(422);
    }
}

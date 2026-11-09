<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Growth\ProgressBandAlert;
use App\Models\Growth\ProgressBandState;
use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\Platform\Subscriptions\LearnerSubscriptionEvent;
use App\Models\Platform\Subscriptions\LearnerSubscriptionPayment;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\MarkSnapshots;
use App\Services\Learning\TutorAccess;
use App\Services\Platform\Groups\SchoolGroupBilling;
use App\Services\Platform\Groups\SchoolGroups;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformBillingAccounts;
use App\Services\Platform\Subscriptions\Entitlements;
use App\Services\Platform\Subscriptions\Gateways\GatewayEvent;
use App\Services\Platform\Subscriptions\Gateways\SubscriptionGateway;
use App\Services\Platform\Subscriptions\SubscriptionPrompts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Learner subscriptions, entitlements and their revenue (Action Plan 3.20,
 * SA.8 Build 3). engineering/specs/platform/learner-subscriptions.md.
 */
class LearnerSubscriptionsTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesPracticeScenario;
    use ValidatesOpenApiResponses;

    private int $staffCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'tutor'], ['enabled' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        config(['ai.inference.enabled' => true, 'ai.inference.features.tutor.enabled' => true, 'ai.inference.features.tutor.model' => 'protocol-test',
            'ai.providers.openai.qualified' => true, 'ai.providers.openai.key' => 'synthetic']);
    }

    // ---- helpers ----

    /** The school's subscriptions flag, on for the schools given (beta by name). */
    private function requireAt(int ...$schools): void
    {
        $flag = DB::table('platform_feature_flags')->where('key', 'mastery_subscriptions')->value('id')
            ?? DB::table('platform_feature_flags')->insertGetId(['key' => 'mastery_subscriptions', 'description' => 'Paid Mastery features', 'rollout' => 'beta', 'created_at' => now(), 'updated_at' => now()]);
        foreach ($schools as $school) {
            DB::table('platform_feature_flag_schools')->updateOrInsert(['flag_id' => $flag, 'school_id' => $school], ['enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function asA(string $person, ?int $school = null): self
    {
        $this->app['auth']->forgetGuards();
        $request = $this->flushHeaders()->withToken($this->schoolA->tokens[$person])->withHeaders(['X-Account-Id' => (string) $this->actor($person)->id]);

        return $school === null ? $request : $request->withHeader('X-School-Id', (string) $school);
    }

    private function asB(string $person): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->schoolB->tokens[$person])->withHeader('X-School-Id', (string) self::SCHOOL_B_ID);
    }

    private function asSuperAdmin(): self
    {
        $this->app['auth']->forgetGuards();
        $admin = $this->superAdmin()->forceFill(['password_change_required' => false]);
        $admin->save();

        return $this->flushHeaders()->withToken($admin->createToken('test')->plainTextToken)->withHeader('X-School-Id', '1');
    }

    private function staff(string $role): User
    {
        $this->staffCount++;
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.$this->staffCount.'@plans.example',
            'mobile' => '', 'password' => 'Synthetic-plans-password-731!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addYear()]);

        return $user;
    }

    /** An HQ request: no school context. */
    private function hq(User $staff): self
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->flushHeaders();
        app(CurrentSchool::class)->forget();

        return $this->signInAs($staff);
    }

    /** A request from outside: no token, no session, the web guard. */
    private function webhook(): self
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->flushHeaders();

        return $this;
    }

    private function inA(callable $call): mixed
    {
        return app(CurrentSchool::class)->run(1, $call);
    }

    private function approveCara(): void
    {
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['cara'], 'guardian_user_id' => $this->actor('farid')->id], ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['cara'], 'guardian_user_id' => $this->actor('farid')->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
    }

    private function entitled(string $learner): array
    {
        return $this->inA(function () use ($learner): array {
            $entitlements = app(Entitlements::class);
            $entitlements->forget();

            return $entitlements->forLearner(Students::query()->findOrFail($this->schoolA->ids[$learner]));
        });
    }

    private function ask(string $guardian, string $learner, ?string $operation = null): TestResponse
    {
        return $this->asA($guardian, 1)->postJson('/api/v2/subscriptions/learners/'.$this->schoolA->ids[$learner].'/requests', ['operation_id' => $operation ?? (string) Str::uuid()]);
    }

    private function pay(User $finance, int $subscription, string $amount, string $reference, int $school = 1, string $received = '2026-02-10'): TestResponse
    {
        return $this->hq($finance)->from('/saas/subscriptions')->post('/saas/subscriptions/schools/'.$school.'/'.$subscription.'/payments', ['amount' => $amount, 'received_on' => $received, 'bank_reference' => $reference]);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected a refusal');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    // ---- entitlements ----

    public function test_daily_practice_stays_free_while_the_tutor_and_parent_insights_need_an_entitlement(): void
    {
        $asha = $this->schoolA->ids['asha'];
        $insight = '/api/v2/growth/mastery/learners/'.$asha.'/subjects';
        $summary = '/api/v2/tutor/parent/learners/'.$asha.'/subjects/'.$this->subject.'/summary';
        // Without the school's flag nothing changes for anyone.
        $this->asA('asha', 1)->getJson('/api/v2/tutor/configuration')->assertOk();
        $this->asA('farid', 1)->getJson($insight)->assertOk();
        $this->asA('farid', 1)->getJson($summary)->assertOk();

        $this->requireAt(1);
        // The learner without an entitlement: no tutor, and the app is told so by the module list.
        $this->asA('asha', 1)->getJson('/api/v2/tutor/configuration')->assertNotFound();
        $this->asA('asha', 1)->postJson('/api/v2/tutor/sessions', ['operation_id' => (string) Str::uuid(), 'group_id' => $this->group, 'source_version_ids' => [$this->source]])->assertNotFound();
        $context = $this->asA('asha', 1)->getJson('/api/v2/me/context')->assertOk()->json();
        $this->assertNotContains('tutor', array_column((array) data_get($context, 'data.configuration.modules', data_get($context, 'data.modules', [])), 'key'));
        $this->assertContains('daily_practice', array_column((array) data_get($context, 'data.configuration.modules', data_get($context, 'data.modules', [])), 'key'));
        $this->inA(fn () => $this->refuses(fn () => app(TutorAccess::class)->learner($this->actor('asha')), 403));
        // The guardian's insights need the child's entitlement; the learner's own view stays free.
        $this->asA('farid', 1)->getJson($insight)->assertForbidden();
        $this->asA('farid', 1)->getJson($summary)->assertForbidden();
        $this->asA('farid', 1)->getJson('/api/v2/learning/paths/learners/'.$asha.'/summary')->assertForbidden();
        $this->asA('asha', 1)->getJson($insight)->assertOk();
        // Daily practice is free for every learner: access, a new set and an answer.
        $this->inA(fn () => $this->approve());
        $this->asA('asha', 1)->getJson('/api/v2/learning/practice/access')->assertOk();
        $set = $this->asA('asha', 1)->postJson('/api/v2/learning/practice/learners/'.$asha.'/groups/'.$this->group.'/daily-sets', ['operation_id' => (string) Str::uuid(), 'practice_on' => '2026-02-10'])
            ->assertOk()->json('data');
        $this->asA('asha', 1)->postJson('/api/v2/learning/practice/learners/'.$asha.'/daily-sets/'.$set['id'].'/entries/'.$set['items'][0]['id'].'/review',
            ['operation_id' => (string) Str::uuid(), 'answer' => '0.5', 'rating' => 3])->assertOk();
        $this->assertFalse($this->entitled('asha')['entitled']);

        // A paid plan opens both.
        $id = $this->ask('farid', 'asha')->assertOk()->json('data.subscription.id');
        $this->pay($this->staff('finance'), $id, '89.00', 'FNB 0001')->assertSessionHasNoErrors();
        $this->assertSame(['required' => true, 'entitled' => true, 'source' => 'subscription', 'until' => '2026-03-09'], $this->entitled('asha'));
        $this->asA('asha', 1)->getJson('/api/v2/tutor/configuration')->assertOk();
        $this->asA('farid', 1)->getJson($insight)->assertOk();
        $this->asA('farid', 1)->getJson($summary)->assertOk();
        // The day after the paid period, both close again; practice does not.
        $this->travelTo(now()->setDate(2026, 3, 10));
        $this->asA('asha', 1)->getJson('/api/v2/tutor/configuration')->assertNotFound();
        $this->asA('farid', 1)->getJson($insight)->assertForbidden();
        $this->asA('asha', 1)->getJson('/api/v2/learning/practice/access')->assertOk();
    }

    public function test_a_guardian_asks_for_plans_with_the_family_price_hq_records_the_eft_once_and_cancelling_keeps_the_paid_period(): void
    {
        $this->requireAt(1);
        $this->approveCara();
        $overview = $this->asA('farid', 1)->getJson('/api/v2/subscriptions')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->assertTrue($overview['required']);
        $this->assertFalse($overview['pricing_confirmed']);
        $this->assertSame([['plan' => 'per_child', 'price_cents' => 8900, 'period_months' => 1], ['plan' => 'family', 'price_cents' => 6900, 'period_months' => 1]], $overview['plans']);
        $this->assertEqualsCanonicalizing([$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], array_column($overview['children'], 'learner_id'));
        $this->assertFalse($overview['children'][0]['entitlement']['entitled']);

        $operation = (string) Str::uuid();
        $first = $this->ask('farid', 'asha', $operation)->assertOk()->assertJsonPath('data.created', true)->assertJsonPath('data.subscription.plan', 'per_child')
            ->assertJsonPath('data.subscription.price_cents', 8900)->assertJsonPath('data.subscription.status', 'requested')->json('data.subscription');
        $this->assertMatchesRegularExpression('/^NES-\d{6}$/', $first['reference']);
        // A retried request returns the same subscription; the same key for another child is refused.
        $this->ask('farid', 'asha', $operation)->assertOk()->assertJsonPath('data.created', false)->assertJsonPath('data.subscription.id', $first['id']);
        $this->ask('farid', 'cara', $operation)->assertStatus(409);
        $this->ask('farid', 'asha')->assertStatus(409);
        $second = $this->ask('farid', 'cara')->assertOk()->assertJsonPath('data.subscription.plan', 'family')->assertJsonPath('data.subscription.price_cents', 6900)->json('data.subscription');

        $finance = $this->staff('finance');
        $this->pay($finance, $first['id'], '89.00', 'fnb  eft 1001')->assertSessionHasNoErrors();
        $this->inA(fn () => $this->assertSame(['status' => 'active', 'end' => '2026-03-09'], ['status' => LearnerSubscription::findOrFail($first['id'])->status, 'end' => LearnerSubscription::findOrFail($first['id'])->current_period_end->toDateString()]));
        // One bank line, once: the same line (however it is typed) and the wrong amount are refused, and nothing is written.
        $this->pay($finance, $second['id'], '69.00', 'FNB EFT 1001')->assertSessionHasErrors('bank_reference');
        $this->pay($finance, $second['id'], '89.00', 'FNB EFT 1002')->assertSessionHasErrors('amount');
        $this->assertSame(1, DB::table('learner_subscription_payments')->count());
        $this->assertSame(1, DB::table('platform_bank_receipts')->count());
        $this->pay($finance, $second['id'], '69.00', 'FNB EFT 1002')->assertSessionHasNoErrors();
        // Renewal adds the next period from the day after the paid one.
        $this->pay($finance, $first['id'], '89.00', 'FNB EFT 1003')->assertSessionHasNoErrors();
        $this->assertSame('2026-04-09', $this->entitled('asha')['until']);
        $this->assertSame(['FNB EFT 1001', 'FNB EFT 1002', 'FNB EFT 1003'], DB::table('platform_bank_receipts')->orderBy('id')->pluck('source_reference')->all());
        $this->assertSame(1, DB::table('audit_log')->where('action', 'platform_subscription_payment_recorded')->where('school_id', 1)->where('after->bank_reference', 'FNB EFT 1001')->count());
        // A school's invoice payment cannot reuse a learning plan's bank line.
        try {
            app(PlatformBillingAccounts::class)->record(1, ['amount' => '10.00', 'received_on' => '2026-02-10', 'bank_reference' => 'FNB EFT 1001'], $finance);
            $this->fail('A school payment reused a learning plan bank line');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('bank_reference', $e->errors());
        }
        $this->assertSame(0, DB::table('platform_payments')->count());

        // Cancelling keeps what was paid for; a replay changes nothing.
        $cancel = (string) Str::uuid();
        $this->asA('farid', 1)->postJson('/api/v2/subscriptions/plans/'.$first['id'].'/cancel', ['operation_id' => $cancel])->assertOk()->assertJsonPath('data.subscription.status', 'cancelled')
            ->assertJsonPath('data.subscription.current_period_end', '2026-04-09');
        $this->asA('farid', 1)->postJson('/api/v2/subscriptions/plans/'.$first['id'].'/cancel', ['operation_id' => $cancel])->assertOk();
        $this->asA('farid', 1)->postJson('/api/v2/subscriptions/plans/'.$first['id'].'/cancel', ['operation_id' => (string) Str::uuid()])->assertStatus(409);
        $this->assertTrue($this->entitled('asha')['entitled']);
        $this->pay($finance, $first['id'], '89.00', 'FNB EFT 1004')->assertSessionHasErrors('subscription');
        $this->travelTo(now()->setDate(2026, 4, 10));
        $this->assertFalse($this->entitled('asha')['entitled']);
        $this->assertTrue($this->entitled('cara')['entitled'] === false);

        // The trail: requested, activated, renewed, cancelled; never a name.
        $this->inA(fn () => $this->assertSame(['requested', 'activated', 'renewed', 'cancelled'], LearnerSubscriptionEvent::query()->where('subscription_id', $first['id'])->orderBy('id')->pluck('event')->all()));
        $this->assertStringNotContainsString('Asha', (string) DB::table('learner_subscription_events')->pluck('detail')->implode(' '));
        // Payments, receipts and the trail never change.
        $this->inA(function (): void {
            $payment = LearnerSubscriptionPayment::query()->firstOrFail();
            try {
                $payment->forceFill(['amount_cents' => 1])->save();
                $this->fail('A payment changed');
            } catch (LogicException) {
            }
        });
        foreach (['UPDATE learner_subscription_payments SET amount_cents = 1', 'DELETE FROM platform_bank_receipts', 'UPDATE learner_subscription_events SET event = \'x\'', 'DELETE FROM learner_subscriptions'] as $sql) {
            try {
                DB::statement($sql);
                $this->fail('Changed: '.$sql);
            } catch (QueryException) {
            }
        }
        // A cancelled plan never goes back to running, and its price never changes.
        $this->expectException(QueryException::class);
        DB::table('learner_subscriptions')->where('id', $first['id'])->update(['price_cents' => 1]);
    }

    public function test_a_school_licence_entitles_every_learner_there_and_sponsored_places_only_the_named_learners(): void
    {
        $this->requireAt(1, 2);
        $finance = $this->staff('finance');
        $this->hq($finance)->post('/saas/subscriptions/schools/1/licences', ['amount' => '2000.00'])->assertSessionHasNoErrors();
        $this->hq($finance)->post('/saas/subscriptions/schools/1/licences', ['amount' => '2000.00'])->assertSessionHasErrors('amount');
        $licence = $this->inA(fn () => LearnerSubscription::query()->where('plan', 'school_licence')->sole());
        $this->assertFalse($this->entitled('asha')['entitled']);
        $this->pay($finance, $licence->id, '2000.00', 'SCHOOL A LICENCE FEB')->assertSessionHasNoErrors();
        $this->assertSame('school_licence', $this->entitled('asha')['source']);
        $this->assertSame('school_licence', $this->entitled('ben')['source']);
        // School B's learners are not covered by School A's licence.
        app(CurrentSchool::class)->run(2, function (): void {
            app(Entitlements::class)->forget();
            $this->assertFalse(app(Entitlements::class)->forLearner(Students::query()->findOrFail($this->schoolB->ids['asha']))['entitled']);
        });
        // A guardian cannot buy what the school already has.
        $this->ask('farid', 'asha')->assertStatus(409);
        $this->hq($finance)->post('/saas/subscriptions/schools/1/'.$licence->id.'/end', ['reason' => 'Synthetic licence ended early'])->assertSessionHasNoErrors();
        $this->assertFalse($this->entitled('ben')['entitled']);

        $this->hq($finance)->post('/saas/subscriptions/schools/1/sponsorships', ['sponsor_name' => 'Synthetic Trust', 'places' => 1, 'amount' => '89.00'])->assertSessionHasNoErrors();
        $sponsored = $this->inA(fn () => LearnerSubscription::query()->where('plan', 'sponsored')->sole());
        $this->pay($finance, $sponsored->id, '89.00', 'SYNTHETIC TRUST FEB')->assertSessionHasNoErrors();
        $this->assertFalse($this->entitled('asha')['entitled']);
        $path = '/api/v2/school/sponsored-places/'.$sponsored->id.'/learners';
        $this->asSuperAdmin()->postJson($path, ['learner_id' => $this->schoolA->ids['asha']])->assertOk()->assertJsonPath('data.sponsorship.learners.0.learner_id', $this->schoolA->ids['asha']);
        $this->asSuperAdmin()->postJson($path, ['learner_id' => $this->schoolA->ids['asha']])->assertOk();
        $this->assertSame('sponsored', $this->entitled('asha')['source']);
        $this->assertFalse($this->entitled('ben')['entitled']);
        $this->asSuperAdmin()->postJson($path, ['learner_id' => $this->schoolA->ids['ben']])->assertStatus(409);
        // Another school's learner is not found; a teacher cannot name anyone.
        $this->asSuperAdmin()->postJson($path, ['learner_id' => $this->schoolB->ids['asha']])->assertNotFound();
        $this->asA('tara', 1)->postJson($path, ['learner_id' => $this->schoolA->ids['ben']])->assertForbidden();
        $this->asA('tara', 1)->getJson('/api/v2/school/sponsored-places')->assertForbidden();
        $this->asSuperAdmin()->postJson($path.'/'.$this->schoolA->ids['asha'].'/remove')->assertOk()->assertJsonPath('data.sponsorship.learners', []);
        $this->asSuperAdmin()->postJson($path, ['learner_id' => $this->schoolA->ids['ben']])->assertOk();
        $this->assertFalse($this->entitled('asha')['entitled']);
        $this->assertSame('sponsored', $this->entitled('ben')['source']);
        $listing = $this->asSuperAdmin()->getJson('/api/v2/school/sponsored-places')->assertOk()->assertJsonPath('data.sponsorships.0.places', 1)->assertJsonPath('data.sponsorships.0.sponsor_name', 'Synthetic Trust');
        $listing->assertDontSee(self::SCHOOL_B_MARKER);
        $this->inA(fn () => $this->assertSame(['requested', 'activated', 'member_added', 'member_removed', 'member_added'], LearnerSubscriptionEvent::query()->where('subscription_id', $sponsored->id)->orderBy('id')->pluck('event')->all()));
    }

    // ---- refusals ----

    public function test_wrong_role_wrong_school_and_wrong_owner_are_refused_and_nothing_is_written(): void
    {
        $this->requireAt(1, 2);
        $asha = $this->schoolA->ids['asha'];
        $mine = $this->ask('farid', 'asha')->assertOk()->json('data.subscription.id');
        $count = DB::table('learner_subscriptions')->count();
        // Wrong owner: another guardian at the same school.
        $this->asA('gina', 1)->getJson('/api/v2/subscriptions/learners/'.$asha)->assertNotFound();
        $this->ask('gina', 'asha')->assertNotFound();
        $this->asA('gina', 1)->postJson('/api/v2/subscriptions/plans/'.$mine.'/cancel', ['operation_id' => (string) Str::uuid()])->assertNotFound();
        $this->asA('gina', 1)->getJson('/api/v2/subscriptions')->assertOk()->assertJsonMissing(['learner_id' => $asha])->assertJsonPath('data.subscriptions', []);
        // Wrong role: a teacher, a learner, the school's Super Admin.
        $this->asA('tara', 1)->getJson('/api/v2/subscriptions')->assertForbidden();
        $this->asA('asha', 1)->postJson('/api/v2/subscriptions/learners/'.$asha.'/requests', ['operation_id' => (string) Str::uuid()])->assertForbidden();
        $this->asSuperAdmin()->getJson('/api/v2/subscriptions')->assertForbidden();
        // Wrong school: School A's guardian in School B, and School B's guardian asking about School A's child.
        $this->asA('farid', self::SCHOOL_B_ID)->getJson('/api/v2/subscriptions')->assertForbidden();
        $this->asB('farid')->getJson('/api/v2/subscriptions/learners/'.$asha)->assertNotFound();
        $this->asB('farid')->postJson('/api/v2/subscriptions/learners/'.$asha.'/requests', ['operation_id' => (string) Str::uuid()])->assertNotFound();
        $this->asB('farid')->postJson('/api/v2/subscriptions/plans/'.$mine.'/cancel', ['operation_id' => (string) Str::uuid()])->assertNotFound();
        $this->asB('farid')->getJson('/api/v2/subscriptions')->assertOk()->assertJsonPath('data.subscriptions', []);
        // Undocumented fields are refused.
        $this->asA('farid', 1)->postJson('/api/v2/subscriptions/learners/'.$asha.'/requests', ['operation_id' => (string) Str::uuid(), 'price_cents' => 1])->assertStatus(422);
        $this->assertSame($count, DB::table('learner_subscriptions')->count());
        $this->inA(fn () => $this->assertSame('requested', LearnerSubscription::findOrFail($mine)->status));

        // HQ: only billing.manage records; another school's subscription is not found through this school's URL.
        $operations = $this->staff('operations');
        $this->hq($operations)->get('/saas/subscriptions')->assertOk()->assertSee(__('Learning plans'))->assertDontSee(__('Record an EFT'))->assertDontSee('Asha');
        foreach (['sales', 'support', 'content'] as $role) {
            $this->hq($this->staff($role))->get('/saas/subscriptions')->assertForbidden();
            $this->hq($this->staff($role))->getJson('/saas/subscriptions/revenue')->assertForbidden();
        }
        $this->pay($operations, $mine, '89.00', 'FNB OPS 1')->assertForbidden();
        $this->pay($this->staff('finance'), $mine, '89.00', 'FNB WRONG SCHOOL', self::SCHOOL_B_ID)->assertNotFound();
        $this->hq($this->staff('finance'))->post('/saas/subscriptions/schools/2/'.$mine.'/decline', ['reason' => 'Synthetic wrong school'])->assertNotFound();
        $this->assertSame(0, DB::table('learner_subscription_payments')->count());
        // A school account never reaches /saas.
        $this->webhook()->signInAs($this->superAdmin())->get('/saas/subscriptions')->assertForbidden();
        $this->post('/saas/subscriptions/schools/1/'.$mine.'/decline', ['reason' => 'A school account cannot do this'])->assertForbidden();

        $finance = $this->staff('finance');
        $this->hq($finance)->get('/saas/subscriptions')->assertOk()->assertSee(__('Record an EFT'));
        $this->hq($finance)->post('/saas/subscriptions/schools/1/'.$mine.'/decline', ['reason' => 'short'])->assertSessionHasErrors('reason');
        $this->hq($finance)->post('/saas/subscriptions/schools/1/'.$mine.'/decline', ['reason' => 'No EFT arrived within the month'])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('audit_log')->where('action', 'platform_subscription_declined')->where('school_id', 1)->count());
        $this->asA('farid', 1)->getJson('/api/v2/subscriptions')->assertOk()->assertJsonPath('data.subscriptions.0.status', 'declined');
    }

    public function test_hq_revenue_counts_collected_running_families_and_churn_without_names(): void
    {
        $this->requireAt(1);
        $this->approveCara();
        $finance = $this->staff('finance');
        $asha = $this->ask('farid', 'asha')->json('data.subscription.id');
        $cara = $this->ask('farid', 'cara')->json('data.subscription.id');
        $this->pay($finance, $asha, '89.00', 'R 1');
        $this->pay($finance, $cara, '69.00', 'R 2');
        $this->hq($finance)->post('/saas/subscriptions/schools/1/sponsorships', ['sponsor_name' => 'Synthetic Trust', 'places' => 2, 'amount' => '150.00']);
        $sponsored = $this->inA(fn () => LearnerSubscription::query()->where('plan', 'sponsored')->value('id'));
        $this->pay($finance, $sponsored, '150.00', 'R 3');
        $revenue = $this->hq($finance)->getJson('/saas/subscriptions/revenue?month=2026-02')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json();
        $this->assertSame(30800, $revenue['collected_cents']);
        $this->assertSame(30800, $revenue['mrr_cents']);
        $this->assertSame(369600, $revenue['arr_cents']);
        $this->assertSame(1, $revenue['families']);
        $this->assertSame(15800, $revenue['per_family_cents']);
        $this->assertSame(4, $revenue['paying_learners']);
        $this->assertSame(7700, $revenue['per_paying_learner_cents']);
        $this->assertSame(['running' => 1, 'mrr_cents' => 8900, 'collected_cents' => 8900], $revenue['plans']['per_child']);
        $this->assertCount(1, $revenue['schools']);
        $this->assertSame(['school_id' => 1, 'running' => 3, 'mrr_cents' => 30800, 'collected_cents' => 30800], array_diff_key($revenue['schools'][0], ['school' => true]));
        $this->assertStringNotContainsString('Asha', json_encode($revenue));
        // March: Asha's plan is cancelled in the month and not renewed.
        $this->travelTo(now()->setDate(2026, 3, 2));
        $this->asA('farid', 1)->postJson('/api/v2/subscriptions/plans/'.$asha.'/cancel', ['operation_id' => (string) Str::uuid()])->assertOk();
        $march = $this->hq($finance)->getJson('/saas/subscriptions/revenue?month=2026-03')->assertOk()->json();
        $this->assertSame(['cancelled_or_ended' => 1, 'running_at_start' => 3, 'rate_bp' => 3333], $march['churn']);
        $this->assertSame(0, $march['collected_cents']);
        $this->hq($finance)->get('/saas/subscriptions?month=2026-03&status=cancelled')->assertOk()->assertSee(__('Cancelled'));
    }

    /** An issued group invoice for a group School A bills through. @return array{0: int, 1: int} group id and invoice id */
    private function groupInvoice(User $finance): array
    {
        $ops = $this->staff('operations');
        app(CurrentSchool::class)->forget();
        $groups = app(SchoolGroups::class);
        $group = $groups->create($ops, ['name' => 'Synthetic Schools Trust']);
        $member = $groups->invite($ops, $group->id, ['school_id' => 1]);
        app(CurrentSchool::class)->run(1, fn () => $groups->agree($this->superAdmin(), $member->id));
        $groups->setBilling($finance, $group->id, $member->id, ['billing' => 'group']);
        DB::table('platform_prices')->insert(['package_id' => DB::table('platform_packages')->where('package_key', 'legacy')->value('id'), 'kind' => 'base',
            'product_module' => null, 'amount_cents' => 5000, 'effective_from' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]);
        $month = substr(PlatformBilling::today(), 0, 7);
        app(PlatformBilling::class)->run(['period' => $month], $finance);
        app(PlatformBilling::class)->issueMonth(['period' => $month], $finance);
        $invoice = app(SchoolGroupBilling::class)->compose($finance, $group->id, ['period' => $month]);
        app(SchoolGroupBilling::class)->issue($finance, $group->id, $invoice->id);

        return [(int) $group->id, (int) $invoice->id];
    }

    private function sqlRefuses(callable $write): void
    {
        try {
            $write();
            $this->fail('The database accepted a bank line used twice');
        } catch (QueryException $e) {
            $this->assertStringContainsString('45000', $e->getMessage());
        }
    }

    public function test_one_bank_line_is_used_once_across_learning_plans_school_invoices_and_group_invoices(): void
    {
        $this->requireAt(1);
        $this->approveCara();
        $finance = $this->staff('finance');
        $asha = $this->ask('farid', 'asha')->assertOk()->json('data.subscription.id');
        $cara = $this->ask('farid', 'cara')->assertOk()->json('data.subscription.id');
        [$group, $invoice] = $this->groupInvoice($finance);
        $groupPay = fn (string $reference) => app(SchoolGroupBilling::class)->recordPayment($finance, $group, $invoice, ['amount' => '10.00', 'received_on' => '2026-02-10', 'bank_reference' => $reference]);

        // A learning plan first: the same line as a group or school payment is refused, however it is typed, and nothing is written.
        $this->pay($finance, $asha, '89.00', 'trust  eft 77')->assertSessionHasNoErrors();
        $this->assertSame(['TRUST EFT 77'], DB::table('platform_bank_receipts')->pluck('source_reference')->all());
        try {
            app(CurrentSchool::class)->forget();
            $groupPay(' Trust EFT  77 ');
            $this->fail('A group payment reused a learning plan bank line');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('bank_reference', $e->errors());
        }
        $this->sqlRefuses(fn () => DB::table('school_group_payments')->insert(['school_group_id' => $group, 'group_invoice_id' => $invoice, 'received_on' => '2026-02-10',
            'amount_cents' => 1000, 'currency' => 'ZAR', 'bank_reference' => 'trust eft 77', 'created_at' => now(), 'updated_at' => now()]));
        $this->sqlRefuses(fn () => DB::table('platform_payments')->insert(['school_id' => 1, 'received_on' => '2026-02-10', 'amount_cents' => 1000, 'currency' => 'ZAR',
            'bank_reference' => 'TRUST  EFT 77', 'recorded_by' => $finance->id, 'created_at' => now(), 'updated_at' => now()]));
        $this->assertSame(0, DB::table('school_group_payments')->count());
        $this->assertSame(0, DB::table('platform_payments')->count());

        // A group payment first: the same line on a learning plan is refused by the service and by the database.
        app(CurrentSchool::class)->forget();
        $groupPay('Group Line 5');
        $this->pay($finance, $cara, '69.00', ' group  LINE 5')->assertSessionHasErrors('bank_reference');
        $this->sqlRefuses(fn () => $this->inA(fn () => DB::transaction(fn () => LearnerSubscriptionPayment::query()->create(['subscription_id' => $cara, 'method' => 'eft', 'provider' => 'manual',
            'source_reference' => 'GROUP LINE 5', 'amount_cents' => 6900, 'currency' => 'ZAR', 'received_on' => '2026-02-10', 'period_start' => '2026-02-10',
            'period_end' => '2026-03-09', 'recorded_by' => $finance->id, 'created_at' => now()]))));
        $this->assertSame(1, DB::table('learner_subscription_payments')->count());
        $this->assertSame(1, DB::table('platform_bank_receipts')->count());
        $this->inA(fn () => $this->assertSame('requested', LearnerSubscription::findOrFail($cara)->status));
        // A reversed group payment frees its line for a learning plan.
        app(CurrentSchool::class)->forget();
        $payment = (int) DB::table('school_group_payments')->value('id');
        app(SchoolGroupBilling::class)->reversePayment($finance, $group, $invoice, $payment, ['reason' => 'Recorded against the wrong account']);
        $this->pay($finance, $cara, '69.00', 'GROUP LINE 5')->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('platform_bank_receipts')->count());
    }

    public function test_revenue_is_built_from_the_paid_periods_and_the_trail_so_later_changes_never_rewrite_a_day(): void
    {
        $this->requireAt(1);
        $this->approveCara();
        $finance = $this->staff('finance');
        $revenue = fn (string $month): array => $this->hq($finance)->getJson('/saas/subscriptions/revenue?month='.$month)->assertOk()->json();
        $asha = $this->ask('farid', 'asha')->json('data.subscription.id');
        $cara = $this->ask('farid', 'cara')->json('data.subscription.id');
        // An early renewal: the next period starts after the one in use, which still counts today.
        $this->pay($finance, $asha, '89.00', 'REV 1')->assertSessionHasNoErrors();
        $this->pay($finance, $asha, '89.00', 'REV 2')->assertSessionHasNoErrors();
        $this->inA(fn () => $this->assertSame('2026-03-10', LearnerSubscription::findOrFail($asha)->current_period_start->toDateString()));
        $this->pay($finance, $cara, '69.00', 'REV 3')->assertSessionHasNoErrors();
        $today = $revenue('2026-02');
        $this->assertEquals(['as_of' => '2026-02-10', 'running' => 2, 'mrr_cents' => 15800, 'families' => 1, 'collected_cents' => 24700],
            array_intersect_key($today, array_flip(['as_of', 'running', 'mrr_cents', 'families', 'collected_cents'])));
        // Cara's plan is cancelled: it counts until its paid period ends (9 March), then stops.
        $this->travelTo(now()->setDate(2026, 2, 12));
        $this->asA('farid', 1)->postJson('/api/v2/subscriptions/plans/'.$cara.'/cancel', ['operation_id' => (string) Str::uuid()])->assertOk();
        $this->travelTo(now()->setDate(2026, 3, 9));
        $this->assertSame(2, $revenue('2026-03')['running']);
        $this->travelTo(now()->setDate(2026, 3, 10));
        $this->assertSame([1, 8900], [$revenue('2026-03')['running'], $revenue('2026-03')['mrr_cents']]);
        // Asha's plan lapses, is renewed late, then ended by HQ in April.
        $this->travelTo(now()->setDate(2026, 4, 20));
        $this->pay($finance, $asha, '89.00', 'REV 4', 1, '2026-04-20')->assertSessionHasNoErrors();
        $this->travelTo(now()->setDate(2026, 4, 25));
        $this->hq($finance)->post('/saas/subscriptions/schools/1/'.$asha.'/end', ['reason' => 'Synthetic refund on request'])->assertSessionHasNoErrors();
        $this->travelTo(now()->setDate(2026, 5, 2));
        // February is unchanged by the renewals, the cancellation and the end.
        $february = $revenue('2026-02');
        $this->assertEquals(['as_of' => '2026-02-28', 'running' => 2, 'mrr_cents' => 15800, 'arr_cents' => 189600, 'families' => 1, 'paying_learners' => 2],
            array_intersect_key($february, array_flip(['as_of', 'running', 'mrr_cents', 'arr_cents', 'families', 'paying_learners'])));
        $this->assertSame(['cancelled_or_ended' => 1, 'running_at_start' => 0, 'rate_bp' => 0], $february['churn']);
        // March: only Asha's renewed period (to 9 April) covers the 31st; Cara's ended on the 9th.
        $march = $revenue('2026-03');
        $this->assertSame([1, 8900], [$march['running'], $march['mrr_cents']]);
        $this->assertSame(['cancelled_or_ended' => 0, 'running_at_start' => 2, 'rate_bp' => 0], $march['churn']);
        // April: Asha ended on the 25th, so nothing runs on the 30th; she counted on the 1st.
        $april = $revenue('2026-04');
        $this->assertSame([0, 0, 8900], [$april['running'], $april['mrr_cents'], $april['collected_cents']]);
        $this->assertSame(['cancelled_or_ended' => 1, 'running_at_start' => 1, 'rate_bp' => 10000], $april['churn']);
    }

    // ---- the gateway seam ----

    public function test_a_gateway_notice_is_acted_on_only_when_configured_and_signed_and_only_once(): void
    {
        $this->requireAt(1);
        $subscription = $this->ask('farid', 'asha')->json('data.subscription');
        $body = fn (string $id, string $charge, int $amount = 8900, string $type = GatewayEvent::PAYMENT_SUCCEEDED): string => json_encode(['id' => $id, 'type' => $type,
            'reference' => $subscription['reference'], 'charge' => $charge, 'amount' => $amount, 'currency' => 'ZAR']);
        $post = fn (string $raw, ?string $signature = null) => $this->webhook()->call('POST', '/webhook/subscriptions/testpay', [], [], [], ['CONTENT_TYPE' => 'application/json',
            'HTTP_X_TEST_SIGNATURE' => $signature ?? hash_hmac('sha256', $raw, 'synthetic-webhook-secret')], $raw);

        // No gateway chosen: every notice is refused and nothing is written.
        $post($body('evt_1', 'ch_1'))->assertNotFound();
        config(['subscriptions.gateway' => 'testpay', 'subscriptions.gateways' => ['testpay' => SyntheticTestGateway::class]]);
        $post($body('evt_1', 'ch_1'), 'bad')->assertUnauthorized();
        $this->assertSame(0, DB::table('subscription_gateway_events')->count());
        $post($body('evt_1', 'ch_1'))->assertOk()->assertJsonPath('message', 'Recorded');
        $this->assertTrue($this->entitled('asha')['entitled']);
        // The same notice again, and another notice for the same charge: one payment.
        $post($body('evt_1', 'ch_1'))->assertOk()->assertJsonPath('message', 'Already processed');
        $post($body('evt_2', 'ch_1'))->assertOk()->assertJsonPath('message', 'Ignored');
        $post($body('evt_3', 'ch_3', 100))->assertOk()->assertJsonPath('message', 'Ignored');
        $this->assertSame(1, DB::table('learner_subscription_payments')->count());
        $this->assertSame(['TESTPAY:CH_1'], DB::table('platform_bank_receipts')->pluck('source_reference')->all());
        $this->assertSame(['paid', 'refused_duplicate', 'refused_amount'], DB::table('subscription_gateway_events')->orderBy('id')->pluck('outcome')->all());
        $post($body('evt_4', 'ch_4', 8900, GatewayEvent::SUBSCRIPTION_CANCELLED))->assertOk()->assertJsonPath('message', 'Recorded');
        $this->inA(fn () => $this->assertSame('cancelled', LearnerSubscription::findOrFail($subscription['id'])->status));
        $this->assertTrue($this->entitled('asha')['entitled']);
        // An unknown reference is recorded and ignored.
        $raw = json_encode(['id' => 'evt_5', 'type' => GatewayEvent::PAYMENT_SUCCEEDED, 'reference' => 'NES-999999', 'charge' => 'ch_5', 'amount' => 8900, 'currency' => 'ZAR']);
        $post($raw)->assertOk()->assertJsonPath('message', 'Ignored');
    }

    public function test_the_migration_refuses_to_roll_back_once_it_holds_subscriptions(): void
    {
        $this->requireAt(1);
        $this->ask('farid', 'asha')->assertOk();
        $migration = require database_path('migrations/2026_11_11_310000_create_learner_subscriptions.php');
        try {
            $migration->down();
            $this->fail('The rollback dropped retained subscriptions');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retain learner subscriptions', $e->getMessage());
        }
        $this->assertSame(1, DB::table('learner_subscriptions')->count());
    }

    // ---- prompts ----

    public function test_prompts_are_off_by_default_follow_the_frequency_and_falling_scores_and_never_mention_marks(): void
    {
        $this->requireAt(1);
        $prompts = app(SubscriptionPrompts::class);
        $this->assertSame(['schools' => 1, 'guardians' => 0, 'prompts' => 0], $prompts->run());
        $this->assertSame(0, DB::table('learner_subscription_prompts')->count());
        // The school switches the notice on in its notification rules.
        $this->inA(fn () => app(NotificationRules::class)->change($this->superAdmin(), 'parent', SubscriptionPrompts::TYPE, 'in_app', 'default_on'));
        // Asha's father and mother: one notice each.
        $this->assertSame(['schools' => 1, 'guardians' => 2, 'prompts' => 2], $prompts->run());
        $notice = $this->inA(fn () => Notification::query()->where('type', SubscriptionPrompts::TYPE)->firstOrFail());
        $text = $notice->title.' '.$notice->message;
        foreach (['Asha', 'mark', 'score', '%', 'Maths', 'support'] as $private) {
            $this->assertStringNotContainsStringIgnoringCase($private, $text);
        }
        $this->assertSame('frequency', DB::table('learner_subscription_prompts')->value('reason'));
        $this->assertSame(0, $prompts->run()['prompts'], 'Not twice on one day');
        $this->travelTo(now()->addDays(10));
        $this->assertSame(0, $prompts->run()['prompts'], 'Not again within the frequency');
        // Results move into the support band: a prompt sooner, but not within the minimum gap.
        $this->supportBand();
        $this->assertSame(2, $prompts->run()['prompts']);
        $this->assertSame('scores_fell', DB::table('learner_subscription_prompts')->orderByDesc('id')->value('reason'));
        $this->travelTo(now()->addDays(3));
        $this->supportBand();
        $this->assertSame(0, $prompts->run()['prompts']);
        // An entitled child, a child waiting for payment and a school without the flag get none.
        $id = $this->ask('farid', 'asha')->json('data.subscription.id');
        $this->travelTo(now()->addDays(40));
        $this->assertSame(0, $prompts->run()['prompts']);
        $this->pay($this->staff('finance'), $id, '89.00', 'FNB PROMPT 1');
        $this->assertSame(0, $prompts->run()['prompts']);
        DB::table('platform_feature_flag_schools')->delete();
        $this->assertSame(['schools' => 0, 'guardians' => 0, 'prompts' => 0], $prompts->run());
        // Ben has no approved account, so his guardian was never prompted.
        $this->assertSame(0, DB::table('learner_subscription_prompts')->where('guardian_user_id', $this->actor('gina')->id)->count());
    }

    /** A released result in the family's support band for Asha. */
    private function supportBand(): void
    {
        $this->inA(function (): void {
            $snapshot = DB::table('mark_snapshots')->where('learner_id', $this->schoolA->ids['asha'])->orderByDesc('id')->value('id');
            if ($snapshot === null) {
                $this->capture('1.00', '1.00');
                app(MarkSnapshots::class)->recompute($this->group, $this->term);
                $snapshot = DB::table('mark_snapshots')->where('learner_id', $this->schoolA->ids['asha'])->orderByDesc('id')->value('id');
            }
            $state = ProgressBandState::query()->firstOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'subject_id' => $this->subject, 'grade_level_id' => DB::table('mark_snapshots')->where('id', $snapshot)->value('grade_level_id'),
                'session_year_id' => $this->year, 'period_key' => 'term:'.$this->term, 'audience' => 'family'], ['band' => 'secure', 'revision' => 1]);
            $state->forceFill(['band' => 'support', 'revision' => $state->revision + 1])->save();
            ProgressBandAlert::query()->create(['state_id' => $state->id, 'state_revision' => $state->revision, 'snapshot_id' => $snapshot, 'band' => 'support', 'settings_revision' => 1, 'created_at' => now()]);
        });
    }
}

/** A stand-in gateway for the seam: HMAC-SHA256 of the raw body in X-Test-Signature. */
class SyntheticTestGateway implements SubscriptionGateway
{
    public function key(): string
    {
        return 'testpay';
    }

    public function verify(string $rawBody, array $headers): ?GatewayEvent
    {
        if (! hash_equals(hash_hmac('sha256', $rawBody, 'synthetic-webhook-secret'), $headers['x-test-signature'] ?? '')) {
            return null;
        }
        $data = json_decode($rawBody, true, 8, JSON_THROW_ON_ERROR);

        return new GatewayEvent((string) $data['id'], (string) $data['type'], (string) $data['reference'], (string) $data['charge'], (int) $data['amount'], (string) $data['currency'], '2026-02-10');
    }
}

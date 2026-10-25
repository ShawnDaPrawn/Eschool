<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityStaff;
use App\Models\Communication\Message;
use App\Models\Communication\MessageRecipient;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Communication\CommunicationAudiences;
use App\Services\Communication\MessageDelivery;
use App\Services\Communication\MessageFlow;
use App\Services\Communication\MessageQueries;
use App\Services\Communication\RecipientAccess;
use App\Services\ParentPolicyService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class MessageRecipientRoleTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(9, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        Queue::fake();
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'],
            'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic message scope regression']);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user']], ['status' => 'active', 'relationship' => 'father']);
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
    }

    public static function profiles(): array
    {
        return [['asha', 'Student', false], ['farid', 'Parent', true]];
    }

    private function publish(bool $parents, ?int $direct = null): Message
    {
        $audience = ['kind' => 'homeroom', 'session_year_id' => $this->schoolA->ids['session_year'], 'target_id' => $this->schoolA->ids['section_a'], 'parents' => $parents];
        if ($direct !== null) {
            $list = app(CommunicationAudiences::class)->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
                'session_year_id' => $this->schoolA->ids['session_year'], 'name' => 'Explicit named account', 'active' => true, 'user_ids' => [$direct]]);
            $audience = ['kind' => 'custom', 'session_year_id' => $this->schoolA->ids['session_year'], 'target_id' => $list['id'], 'parents' => false];
        }
        $flow = app(MessageFlow::class);
        $receipt = $flow->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'title' => 'Synthetic private cohort message', 'markdown' => 'Synthetic cohort body must remain scoped.', 'channels' => ['in_app'], 'scheduled_local' => null, 'audience' => $audience]);
        $preview = $flow->preview($this->superAdmin(), $receipt['id']);
        $flow->submit($this->superAdmin(), $receipt['id'], ['operation_id' => (string) Str::uuid(), 'revision' => $receipt['revision'], 'fingerprint' => $preview['fingerprint']]);
        app(MessageDelivery::class)->process($receipt['id']);

        return Message::findOrFail($receipt['id']);
    }

    private function facilitator(User $actor, string $oldRole, bool $keep = false): void
    {
        $writer = User::findOrFail($actor->id);
        $writer->syncRoles($keep ? [$oldRole, 'Facilitator'] : ['Facilitator']);
        ParentPolicyService::acceptCurrent($writer);
        SchoolMembership::where('school_id', 1)->where('user_id', $actor->id)->where('role', $oldRole)->update(['status' => $keep ? 'active' : 'ended', 'ends_on' => $keep ? null : '2026-02-09']);
        SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $actor->id, 'role' => 'Facilitator'], ['status' => 'active', 'starts_on' => '2026-01-01']);
        $group = ActivityGroup::create(['session_year_id' => $this->schoolA->ids['session_year'], 'name' => 'Unrelated activity scope', 'discipline' => 'Sport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $actor->id, 'role' => 'facilitator', 'cascade' => false, 'active' => true, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Legitimate unrelated activity assignment']);
    }

    #[DataProvider('profiles')]
    public function test_role_conversion_refuses_retained_cohort_reasons_reader_and_final_delivery(string $person, string $role, bool $parents): void
    {
        $actor = User::findOrFail($this->schoolA->ids[$person.'_user'])->load('roles');
        $message = $this->publish($parents);
        $recipient = MessageRecipient::where('message_id', $message->id)->where('user_id', $actor->id)->firstOrFail();
        $this->assertFalse($recipient->access_reason['direct']);
        $this->assertTrue(app(RecipientAccess::class)->allows($actor, $recipient->access_reason));
        $this->assertSame($message->id, app(MessageQueries::class)->reader($actor, $message->id)['id']);
        for ($i = 0; $i < 20; $i++) {
            $copy = $message->replicate();
            $copy->publication_id = (string) Str::uuid();
            $copy->save();
            $link = $recipient->replicate();
            $link->message_id = $copy->id;
            $link->save();
        }
        $this->assertSame(2, app(MessageQueries::class)->inbox($actor, 1)['last_page']);
        $context = ['communication_message_id' => $message->id, 'communication_publication_id' => $message->publication_id];
        app(NotificationRules::class)->choose($actor, 'communication.message', 'email', true);
        $delivery = NotificationDelivery::create(['school_id' => 1, 'recipient_user_id' => $actor->id, 'recipient_hash' => DeliveryLog::hash($actor->email),
            'channel' => 'email', 'type' => 'email.communication.message', 'delivery_key' => (string) Str::uuid(), 'status' => 'queued']);
        $this->assertTrue(app(DeliveryGuard::class)->allows($delivery, 'communication.message', 'email', $actor->email, $context));
        $this->facilitator($actor, $role);
        $this->assertNotNull(Students::find($this->schoolA->ids['asha']));
        if ($parents) {
            $this->assertNotNull(Parents::find($this->schoolA->ids['farid']));
        }
        $fresh = User::findOrFail($actor->id);
        $token = $fresh->createToken(AppTokens::FACILITATOR)->plainTextToken;
        $this->app['auth']->forgetGuards();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->flushHeaders()->withToken($token)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id])
            ->getJson('/api/v2/communication/messages/'.$message->id)->assertNotFound()->assertDontSee('Synthetic cohort body');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertFalse(app(RecipientAccess::class)->allows($actor, $recipient->access_reason));
        $this->assertSame([], array_values(array_filter($queries, fn ($query) => preg_match('/select .* from `messages`/i', $query['query']))), 'A refused reader must authorize source metadata before loading the message.');
        $this->assertFalse(app(MessageDelivery::class)->allows($actor, 'communication.message', $context));
        $this->assertFalse(app(DeliveryGuard::class)->allows($delivery, 'communication.message', 'email', $actor->email, $context));
        $inbox = app(MessageQueries::class)->inbox($actor, 1);
        $this->assertSame([], $inbox['items']);
        $this->assertSame(1, $inbox['last_page']);
    }

    #[DataProvider('profiles')]
    public function test_valid_dual_role_cohort_and_explicit_named_account_messages_remain_available(string $person, string $role, bool $parents): void
    {
        $actor = User::findOrFail($this->schoolA->ids[$person.'_user']);
        $message = $this->publish($parents);
        $this->facilitator($actor, $role, true);
        $this->assertSame($message->id, app(MessageQueries::class)->reader($actor, $message->id)['id']);
        $direct = $this->publish(false, (int) $actor->id);
        $reason = MessageRecipient::where('message_id', $direct->id)->where('user_id', $actor->id)->firstOrFail()->access_reason;
        $this->assertTrue($reason['direct']);
        $this->facilitator($actor, $role);
        $this->assertTrue(app(RecipientAccess::class)->allows($actor, $reason));
        $this->assertSame($direct->id, app(MessageQueries::class)->reader($actor, $direct->id)['id']);
    }

    public function test_current_other_school_parent_role_does_not_restore_this_school_guardian_cohort(): void
    {
        $actor = User::findOrFail($this->schoolA->ids['farid_user']);
        $message = $this->publish(true);
        $this->facilitator($actor, 'Parent');
        app(CurrentSchool::class)->run(2, function () use ($actor) {
            SchoolMembership::create(['school_id' => 2, 'user_id' => $actor->id, 'role' => 'Parent', 'status' => 'active', 'starts_on' => '2026-01-01']);
            User::findOrFail($actor->id)->assignRole('Parent');
        });
        $reason = MessageRecipient::where('message_id', $message->id)->where('user_id', $actor->id)->firstOrFail()->access_reason;
        $this->assertFalse(app(RecipientAccess::class)->allows($actor, $reason));
    }
}

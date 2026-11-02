<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Communication\Message;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Communication\MessageDelivery;
use App\Services\Communication\MessageFlow;
use App\Services\Communication\MessageQueries;
use App\Services\Communication\MessageReport;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * The sender's delivery report for a published school message (Action Plan
 * 2.4). engineering/specs/communication/delivery-report.md.
 */
class MessageDeliveryReportTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private int $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10)->setTime(9, 0));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        foreach (['asha', 'ben'] as $key) {
            app(EnrolmentService::class)->place($this->schoolA->ids[$key], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic message enrolment');
        }
        $g = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Message fractions group', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->group = (int) $g->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->teacher()->id)->value('id'), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic message author'], $this->group);
        foreach (['asha', 'ben'] as $key) {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids[$key], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic audience member'], $this->group);
        }
        Queue::fake();
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function person(string $key): User
    {
        return User::findOrFail($this->schoolA->ids[$key.'_user']);
    }

    private function data(array $changes = []): array
    {
        return $changes + ['revision' => 0, 'operation_id' => (string) Str::uuid(), 'title' => 'Private school message', 'markdown' => "# Fractions\n\nPrivate family details.",
            'channels' => ['in_app'], 'scheduled_local' => null, 'audience' => ['kind' => 'group', 'session_year_id' => $this->year, 'target_id' => $this->group, 'parents' => true]];
    }

    private function draft(?User $actor = null, array $changes = []): Message
    {
        $r = app(MessageFlow::class)->save($actor ?? $this->teacher(), null, $this->data($changes));

        return Message::findOrFail($r['id']);
    }

    private function submit(Message $m, ?User $actor = null): Message
    {
        $actor ??= $this->teacher();
        $preview = app(MessageFlow::class)->preview($actor, (int) $m->id);
        app(MessageFlow::class)->submit($actor, (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint']]);

        return $m->refresh();
    }

    private function approved(?Message $m = null): Message
    {
        $m = $this->submit($m ?? $this->draft());
        $preview = app(MessageFlow::class)->preview($this->superAdmin(), (int) $m->id);
        app(MessageFlow::class)->decide($this->superAdmin(), (int) $m->id, ['operation_id' => (string) Str::uuid(), 'revision' => $m->revision, 'fingerprint' => $preview['fingerprint'], 'decision' => 'approve', 'reason' => 'Reviewed synthetic audience and message']);

        return $m->refresh();
    }

    private function denied(callable $fn, int $code): void
    {
        try {
            $fn();
            $this->fail('Expected refusal');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($code, $e->getStatusCode());
        } catch (ModelNotFoundException $e) {
            $this->assertSame(404, $code);
        }
    }

    private function headers(string $person = 'tara'): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$this->schoolA->tokens[$person], 'X-School-Id' => '1'];
    }

    /** A message to the group's guardians by in-app and email, delivered, with one email delivered and one failed. */
    private function sent(): Message
    {
        // The group's guardians, named, with school message emails switched on.
        foreach (['farid' => 'Farid Synthetic', 'mona' => 'Mona Synthetic', 'gina' => 'Gina Synthetic'] as $key => $name) {
            [$first, $last] = explode(' ', $name);
            $this->person($key)->forceFill(['first_name' => $first, 'last_name' => $last])->save();
            app(NotificationRules::class)->choose($this->person($key), 'communication.message', 'email', true);
        }
        $m = $this->approved($this->draft(null, ['channels' => ['in_app', 'email']]));
        app(MessageDelivery::class)->process((int) $m->id);
        $m->refresh();
        $mail = NotificationDelivery::query()->where('notification_id', $m->notification_id)->where('channel', DeliveryLog::MAIL)->orderBy('recipient_user_id')->get();
        $this->assertSame(3, $mail->count(), 'each guardian\'s email row is linked to the notice');
        DeliveryLog::sent($mail[0], 'ses', 'synthetic-provider-id');
        DeliveryLog::feedback('synthetic-provider-id', NotificationDelivery::DELIVERED);
        DeliveryLog::failed(DeliveryLog::attempt($mail[1]->delivery_key, 1), 'ses', 'Rejected for farid@example.test');

        return $m;
    }

    public function test_the_author_sees_reads_and_each_channels_result_by_name_only(): void
    {
        $m = $this->sent();
        $mail = NotificationDelivery::query()->where('notification_id', $m->notification_id)->where('channel', DeliveryLog::MAIL)->orderBy('recipient_user_id')->get();
        app(MessageQueries::class)->read($this->person('farid'), (int) $m->id);

        $report = app(MessageReport::class)->for($this->teacher(), (int) $m->id);
        $this->assertSame(3, $report['recipient_count']);
        $this->assertSame(1, $report['read_count']);
        $this->assertSame(1, $report['channels']['email']['delivered']);
        $this->assertSame(1, $report['channels']['email']['failed']);
        $this->assertSame(1, $report['channels']['email']['queued']);
        $this->assertSame(0, array_sum($report['channels']['push']));
        $this->assertCount(3, $report['items']);
        $byName = collect($report['items'])->keyBy('name');
        $first = User::findOrFail($mail[0]->recipient_user_id);
        $second = User::findOrFail($mail[1]->recipient_user_id);
        $this->assertSame('delivered', $byName[trim($first->first_name.' '.$first->last_name)]['email']);
        $this->assertSame('failed', $byName[trim($second->first_name.' '.$second->last_name)]['email']);
        $farid = $this->person('farid');
        $this->assertNotNull($byName[trim($farid->first_name.' '.$farid->last_name)]['read_at']);
        $this->assertSame(['name', 'read_at', 'notice', 'email', 'push'], array_keys($report['items'][0]));

        // The panel page names people and never shows addresses or provider errors.
        $page = $this->actingAs($this->teacher(), 'web')->get('/communication-centre/messages/'.$m->id.'/report')
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('Delivery report')->assertSee($farid->first_name);
        foreach ($mail as $row) {
            $page->assertDontSee((string) User::findOrFail($row->recipient_user_id)->email);
        }
        $page->assertDontSee('Rejected');
        $this->get('/communication-centre?message_id='.$m->id)->assertOk()->assertSee('/communication-centre/messages/'.$m->id.'/report', false);

        // The app sees the same report, to the published contract.
        $this->getJson('/api/v2/communication/drafts/'.$m->id.'/report', $this->headers())->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.read_count', 1)->assertJsonPath('data.channels.email.delivered', 1);
    }

    public function test_a_retry_counts_once_by_its_latest_attempt(): void
    {
        $m = $this->sent();
        $failed = NotificationDelivery::query()->where('notification_id', $m->notification_id)->where('status', NotificationDelivery::FAILED)->firstOrFail();
        DeliveryLog::sent(DeliveryLog::attempt($failed->delivery_key, 2), 'ses', 'synthetic-retry-id');
        $this->assertSame(2, NotificationDelivery::query()->where('delivery_key', $failed->delivery_key)->where('notification_id', $m->notification_id)->count(), 'a retry keeps the link to the notice');

        $report = app(MessageReport::class)->for($this->teacher(), (int) $m->id);
        $this->assertSame(0, $report['channels']['email']['failed']);
        $this->assertSame(1, $report['channels']['email']['sent']);
    }

    public function test_a_reviewer_may_see_it_but_a_recipient_another_teacher_and_another_school_may_not(): void
    {
        $m = $this->sent();
        // The approving reviewer sees the report too.
        $this->assertSame(3, app(MessageReport::class)->for($this->superAdmin(), (int) $m->id)['recipient_count']);
        // Wrong role: a parent who received the message.
        $this->denied(fn () => app(MessageReport::class)->for($this->person('farid'), (int) $m->id), 403);
        $this->getJson('/api/v2/communication/drafts/'.$m->id.'/report', $this->headers('farid'))->assertForbidden()->assertDontSee('read_count');
        // Wrong owner: another teacher at the school who may not review it.
        $this->denied(fn () => app(MessageReport::class)->for($this->person('theo'), (int) $m->id), 404);
        $this->actingAs($this->person('theo'), 'web')->get('/communication-centre/messages/'.$m->id.'/report')->assertNotFound();
        // Wrong school: the same id asked for at School B.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v2/communication/drafts/'.$m->id.'/report', ['Authorization' => 'Bearer '.$this->schoolB->tokens['tara'], 'X-School-Id' => '2'])
            ->assertNotFound()->assertDontSee('Private school message');
        app(CurrentSchool::class)->run(2, fn () => $this->denied(fn () => app(MessageReport::class)->for(User::findOrFail($this->schoolB->ids['tara_user']), (int) $m->id), 404));
    }

    public function test_there_is_no_report_before_a_message_is_published(): void
    {
        $m = $this->draft();
        $this->denied(fn () => app(MessageReport::class)->for($this->teacher(), (int) $m->id), 404);
        $this->actingAs($this->teacher(), 'web')->get('/communication-centre/messages/'.$m->id.'/report')->assertNotFound();
    }

    public function test_deliveries_of_other_notices_and_schools_never_count(): void
    {
        $m = $this->sent();
        // Same recipients, same notice id, but recorded for another school, and a delivery with no notice.
        $row = NotificationDelivery::query()->where('notification_id', $m->notification_id)->firstOrFail();
        NotificationDelivery::create(['school_id' => 2, 'delivery_key' => (string) Str::uuid(), 'notification_id' => $m->notification_id, 'type' => 'communication.message',
            'channel' => DeliveryLog::PUSH, 'recipient_user_id' => $row->recipient_user_id, 'status' => NotificationDelivery::SENT, 'attempt' => 1]);
        DeliveryLog::queued(DeliveryLog::PUSH, 'push.communication.message', (int) $row->recipient_user_id, 'synthetic-token');
        $this->assertSame(0, array_sum(app(MessageReport::class)->for($this->teacher(), (int) $m->id)['channels']['push']));
    }
}

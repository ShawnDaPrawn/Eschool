<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Notification;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\ParentPolicyService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeReadState;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Notifications\StaffPrivateNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class StaffInboxFixtureScope implements PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->where('title', 'allowed')->orWhere('title', 'also allowed');
    }
}

class StaffInboxContextReplacement implements PrivateNoticeReadState
{
    public function applyUnread(Builder $notifications, User $actor): void {}

    public function readStates(User $actor, array $payloads): array
    {
        return [];
    }

    public function recordRead(User $actor, int $notificationId, array $payload): void
    {
        app(CurrentSchool::class)->set(2);
    }
}

class StaffPrivateNoticesTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        $this->staff = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->staff->forceFill(['password_change_required' => false])->save();
        ParentPolicyService::acceptCurrent($this->staff);
        $types = config('notification_types');
        $types['private.updated'] = $types['wellbeing.staff_update'];
        $types['private.updated']['module'] = 'communication';
        config(['notification_types' => $types, 'private_notices.scopes' => ['private.' => StaffInboxFixtureScope::class],
            'private_notices.read_states' => [], 'private_notices.staff_inbox' => ['private.updated' => ['feature' => 'progress', 'title' => 'Private school update', 'message' => 'Open your private school area to review an available update.']]]);
    }

    private function notice(string $title = 'allowed', ?int $recipient = null, string $type = 'private.updated'): Notification
    {
        $notice = app(Notifier::class)->makeNotice($type, $title, 'PRIVATE stored body must not be selected', '/private-source');
        UserNotification::create(['notification_id' => $notice->id, 'user_id' => $recipient ?? $this->staff->id]);

        return $notice;
    }

    public function test_source_recipient_school_and_exact_type_scope_precedes_pagination_counts_and_body_load(): void
    {
        for ($i = 0; $i < 26; $i++) {
            $this->notice();
        }
        $this->notice('denied');
        $this->notice('also allowed', (int) $this->schoolA->ids['farid_user']);
        $this->notice('allowed', null, 'wellbeing.report_reply');
        app(CurrentSchool::class)->run(2, fn () => $this->notice());
        DB::flushQueryLog();
        DB::enableQueryLog();
        $answer = app(StaffPrivateNotices::class)->inbox($this->staff);
        foreach (DB::getQueryLog() as $query) {
            if (str_contains($query['query'], 'from `notifications`')) {
                $this->assertStringNotContainsString('`message`', $query['query']);
                $this->assertStringNotContainsString('select *', strtolower($query['query']));
            }
        }
        DB::disableQueryLog();
        $this->assertSame(26, $answer['unread_count']);
        $this->assertCount(25, $answer['items']);
        $this->assertSame(2, $answer['last_page']);
        $this->assertSame(1, $answer['school_id']);
        $this->assertSame((int) $this->staff->id, $answer['reader_id']);
        $this->assertStringNotContainsString('PRIVATE', json_encode($answer));
        $this->assertSame(['id', 'feature', 'title', 'message', 'writable', 'read_at'], array_keys($answer['items'][0]));
        $this->assertCount(1, app(StaffPrivateNotices::class)->inbox($this->staff, 2)['items']);
    }

    public function test_read_is_owned_idempotent_utc_and_source_withdrawal_cannot_be_marked(): void
    {
        $notice = $this->notice();
        $service = app(StaffPrivateNotices::class);
        $first = $service->markRead($this->staff, (int) $notice->id);
        $this->assertStringEndsWith('Z', $first['read_at']);
        $this->assertSame($first, $service->markRead($this->staff, (int) $notice->id));
        $this->assertSame(0, $service->inbox($this->staff)['unread_count']);
        $this->assertSame($first['read_at'], $service->inbox($this->staff)['items'][0]['read_at']);
        $notice->title = 'withdrawn';
        $notice->save();
        $this->assertSame([], $service->inbox($this->staff)['items']);
        $this->expectException(ModelNotFoundException::class);
        $service->markRead($this->staff, (int) $notice->id);
    }

    public function test_http_cookie_account_headers_and_body_only_marker_refusals(): void
    {
        $notice = $this->notice();
        $this->assertTrue(app(FacilitatorAccess::class)->profile($this->staff)['features']['private_notices']);
        $base = '/api/v2/staff/private-notices';
        $this->withToken($this->schoolA->tokens['tara'])->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->staff->id]);
        $listed = $this->getJson($base)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.unread_count', 1);
        $this->postJson("$base/{$notice->id}/read?private=forbidden")->assertUnprocessable();
        $this->postJson("$base/{$notice->id}/read", ['private' => 'forbidden'])->assertUnprocessable();
        $read = $this->postJson("$base/{$notice->id}/read")->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->withHeader('X-Account-Id', (string) $this->schoolA->ids['farid_user'])->getJson($base)->assertForbidden();
        $this->withHeader('X-Account-Id', (string) $this->staff->id)->withHeader('X-School-Id', '2')->getJson($base)->assertForbidden();
    }

    public function test_family_wrong_owner_platform_and_stale_staff_membership_are_refused(): void
    {
        $own = $this->notice();
        $other = $this->notice('allowed', (int) $this->schoolA->ids['farid_user']);
        $base = '/api/v2/staff/private-notices';
        $this->withToken($this->schoolA->tokens['farid'])->getJson($base)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['tara'])->postJson("$base/{$other->id}/read")->assertNotFound();
        PlatformStaffIdentity::create(['user_id' => $this->staff->id]);
        $this->getJson($base)->assertForbidden();
        PlatformStaffIdentity::query()->delete();
        SchoolMembership::where('user_id', $this->staff->id)->where('role', 'Teacher')->update(['status' => 'ended']);
        $this->getJson($base)->assertForbidden();
        $this->assertNull(UserNotification::where('notification_id', $own->id)->value('private_read_at'));
    }

    public function test_read_only_module_keeps_private_reads_but_refuses_marker_writes(): void
    {
        $notice = $this->notice();
        $types = config('notification_types');
        $types['private.updated']['module'] = 'groups';
        config(['notification_types' => $types]);
        SchoolModule::updateOrCreate(['module' => 'groups'], ['in_package' => true, 'enabled' => true, 'read_only' => true]);
        $service = app(StaffPrivateNotices::class);
        $this->assertFalse($service->inbox($this->staff)['writable']);
        $this->expectException(HttpException::class);
        $service->markRead($this->staff, (int) $notice->id);
    }

    public function test_read_adapter_context_replacement_rolls_back_marker_and_restores_school(): void
    {
        $notice = $this->notice();
        config(['private_notices.read_states' => ['private.' => StaffInboxContextReplacement::class]]);
        try {
            app(StaffPrivateNotices::class)->markRead($this->staff, (int) $notice->id);
            $this->fail('Context replacement must refuse the read marker.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertNull(UserNotification::where('notification_id', $notice->id)->value('private_read_at'));
    }
}

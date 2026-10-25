<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Notifications\PrivateNoticeScopes;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class DeliveryFixtureScope implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->whereRaw(config('private_notices.fixture_allowed') ? '1 = 1' : '1 = 0');
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        if (config('private_notices.fixture_replace_context')) {
            app(CurrentSchool::class)->set(2);
        }

        return $type === 'private.updated' && config('private_notices.fixture_allowed') === true;
    }
}
class PrivateNoticeDeliveryTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        config(['private_notices.scopes' => ['private.' => DeliveryFixtureScope::class], 'private_notices.fixture_allowed' => true]);
        // Notification type keys contain literal dots.
        $types = config('notification_types');
        $types['private.updated'] = ['label' => 'Synthetic private update', 'module' => 'people', 'portals' => ['parent'], 'channels' => ['push' => 'off', 'email' => 'default_on', 'in_app' => 'default_on']];
        config(['notification_types' => $types]);
    }

    private function actor(): User
    {
        return User::findOrFail($this->schoolA->ids['farid_user']);
    }

    public function test_source_refusal_precedes_fanout_and_queued_transport_rechecks_later_withdrawal(): void
    {
        $actor = $this->actor();
        $actor->forceFill(['email_verified_at' => now()])->save();
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice('private.updated', 'Private update', 'Open the app to review.', '/notifications');
        config(['private_notices.fixture_allowed' => false]);
        $this->assertSame([], $notifier->send($actor, 'private.updated', $notice->title, $notice->message, '/notifications', $notice));
        $this->assertSame(0, UserNotification::where('notification_id', $notice->id)->count());
        config(['private_notices.fixture_allowed' => true]);
        $sent = $notifier->send($actor, 'private.updated', $notice->title, $notice->message, '/notifications', $notice);
        $this->assertContains('in_app', $sent);
        $key = DeliveryLog::queued('private.updated', 'email', (int) $actor->id, $actor->email);
        $row = DeliveryLog::attempt($key, 1);
        $guard = app(DeliveryGuard::class);
        $this->assertTrue($guard->allows($row, 'private.updated', 'email', $actor->email));
        config(['private_notices.fixture_allowed' => false]);
        $this->assertFalse($guard->allows($row, 'private.updated', 'email', $actor->email));
    }

    public function test_registered_staff_mail_types_use_the_staff_origin_without_changing_family_default(): void
    {
        $actor = $this->actor();
        $actor->forceFill(['email_verified_at' => now()])->save();
        config(['app.url' => 'https://staff.example.test', 'identity.portal_url' => 'https://family.example.test', 'private_notices.staff_types' => ['private.updated']]);
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice('private.updated', 'Private update', 'Open the app.', '/notifications');
        $notifier->send($actor, 'private.updated', $notice->title, $notice->message, '/notifications', $notice);
        Queue::assertPushed(SendMail::class, fn ($job) => $job->mailable->noticeUrl === 'https://staff.example.test/notifications');
    }

    public function test_fresh_password_platform_and_replaced_school_deny_registered_delivery(): void
    {
        $scope = app(PrivateNoticeScopes::class);
        $stale = $this->actor();
        $this->assertTrue($scope->permitsDelivery($stale, 'private.updated', []));
        $this->actor()->forceFill(['password_change_required' => true])->save();
        $this->assertFalse($scope->permitsDelivery($stale, 'private.updated', []));
        $this->actor()->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $stale->id]);
        $this->assertFalse($scope->permitsDelivery($stale, 'private.updated', []));
        PlatformStaffIdentity::query()->delete();
        config(['private_notices.fixture_replace_context' => true]);
        $this->assertFalse($scope->permitsDelivery($stale, 'private.updated', []));
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertTrue($scope->permitsDelivery($stale, 'comms.notice', []));
    }
}

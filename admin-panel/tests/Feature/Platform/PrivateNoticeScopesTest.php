<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Notification;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Platform\Notifications\NotificationVisibility;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class RestrictFixtureScope implements PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->where('title', 'Allowed synthetic private notice');
    }
}
class BroadFixtureScope implements PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->whereRaw('1 = 0')->orWhereRaw('1 = 1');
    }
}
class ReplacedContextFixtureScope implements PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        app(CurrentSchool::class)->set(2);
        $notifications->whereRaw('1 = 1')->orWhereRaw('1 = 1');
    }
}
class PrivateNoticeScopesTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        Notification::query()->delete();
        config(['private_notices.scopes' => ['private.' => RestrictFixtureScope::class, 'other.' => BroadFixtureScope::class]]);
    }

    private function actor(): User
    {
        return User::findOrFail($this->schoolA->ids['farid_user']);
    }

    private function notice(string $type, string $title, ?int $recipient = null): Notification
    {
        $notice = app(Notifier::class)->makeNotice($type, $title, 'Synthetic private body', '/notifications');
        UserNotification::create(['notification_id' => $notice->id, 'user_id' => $recipient ?? $this->actor()->id]);

        return $notice;
    }

    private function visible(): Builder
    {
        return NotificationVisibility::forUser(Notification::query(), (int) $this->actor()->id, 4);
    }

    public function test_registered_scopes_filter_pagination_total_count_and_lookup_before_private_body_load(): void
    {
        $allowed = $this->notice('private.updated', 'Allowed synthetic private notice');
        $hidden = $this->notice('private.updated', 'Denied synthetic private notice');
        $other = $this->notice('other.updated', 'Other private notice');
        $ordinary = $this->notice('custom', 'Ordinary linked notice');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->assertSame(3, $this->visible()->count());
        $this->assertNull($this->visible()->find($hidden->id));
        foreach (DB::getQueryLog() as $query) {
            $this->assertStringNotContainsString('`message`', $query['query']);
            $this->assertStringNotContainsString('`delivery_payload`', $query['query']);
        }
        DB::disableQueryLog();
        $page = $this->visible()->orderBy('id')->paginate(1);
        $this->assertSame(3, $page->total());
        $this->assertSame((int) $allowed->id, (int) $page->items()[0]->id);
        $this->assertEqualsCanonicalizing([$allowed->id, $other->id, $ordinary->id], $this->visible()->pluck('id')->all());
    }

    public function test_provider_or_cannot_broaden_another_prefix_recipient_or_school(): void
    {
        $own = $this->notice('other.updated', 'Own allowed');
        $this->notice('private.updated', 'Denied sibling prefix');
        $this->notice('other.updated', 'Another recipient', (int) $this->schoolA->ids['mona_user']);
        app(CurrentSchool::class)->run(2, fn () => $this->notice('other.updated', 'Another school', (int) $this->schoolA->ids['farid_user']));
        $this->assertSame([(int) $own->id], $this->visible()->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_fresh_membership_password_and_platform_identity_refusals_hide_registered_private_types(): void
    {
        $private = $this->notice('other.updated', 'Owned private');
        $ordinary = $this->notice('custom', 'Ordinary linked');
        $this->assertNotNull($this->visible()->find($private->id));
        $this->actor()->forceFill(['password_change_required' => true])->save();
        $this->assertSame([$ordinary->id], $this->visible()->pluck('id')->all());
        $this->actor()->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->actor()->id]);
        $this->assertNull($this->visible()->find($private->id));
        PlatformStaffIdentity::query()->delete();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->actor()->id)->update(['status' => 'ended']);
        $this->assertNull($this->visible()->find($private->id));
    }

    public function test_context_replacement_during_provider_scope_refuses_that_prefix_and_restores_school(): void
    {
        config(['private_notices.scopes' => ['private.' => ReplacedContextFixtureScope::class]]);
        $private = $this->notice('private.updated', 'No changed context');
        $this->assertNull($this->visible()->find($private->id));
        $this->assertSame(1, app(CurrentSchool::class)->id());
    }

    public function test_prefix_underscores_are_literal_and_do_not_hide_a_sibling_type(): void
    {
        config(['private_notices.scopes' => ['private_' => RestrictFixtureScope::class]]);
        $allowed = $this->notice('privateXupdated', 'Ordinary sibling with same characters');
        $hidden = $this->notice('private_updated', 'Private prefix denied by its own source');
        $this->assertNotNull($this->visible()->find($allowed->id));
        $this->assertNull($this->visible()->find($hidden->id));
    }

    public function test_suspended_school_and_impersonation_cannot_retain_private_inbox_rows(): void
    {
        $notice = $this->notice('other.updated', 'Owned private');
        School::whereKey(1)->update(['status' => School::SUSPENDED]);
        $this->assertNull($this->visible()->find($notice->id));
        School::whereKey(1)->update(['status' => School::ACTIVE]);
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->assertNull($this->visible()->find($notice->id));
        app(CurrentSchool::class)->set(1);
        $this->assertNotNull($this->visible()->find($notice->id));
    }

    public function test_prepared_query_cannot_follow_a_later_school_context(): void
    {
        $notice = $this->notice('other.updated', 'Owned private');
        $query = $this->visible();
        app(CurrentSchool::class)->set(2);
        $this->assertNull($query->find($notice->id));
        app(CurrentSchool::class)->set(1);
    }

    public function test_real_parent_inbox_total_and_rows_use_the_private_source_scope(): void
    {
        $visible = $this->notice('private.updated', 'Allowed synthetic private notice');
        $hidden = $this->notice('private.updated', 'Denied synthetic private notice');
        $answer = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')->assertOk();
        $ids = array_column($answer->json('data.data'), 'id');
        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
        $this->assertSame(1, $answer->json('data.total'));
    }
}

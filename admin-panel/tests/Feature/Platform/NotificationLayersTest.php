<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Platform\Notifications\NotificationInbox;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class LayersFixtureScope implements PrivateNoticeScope
{
    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->where('title', 'Private update');
    }
}

/**
 * Three-layer notifications (Action Plan 1.4):
 * engineering/specs/notifications/notification-layers.md.
 */
class NotificationLayersTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private const BASE = '/api/v2/me/notifications';

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['password_change_required' => false])->save();
    }

    private function deliver(Notification $notice, int $userId): Notification
    {
        UserNotification::create(['notification_id' => $notice->id, 'user_id' => $userId]);

        return $notice;
    }

    private function asParent(): static
    {
        return $this->withToken($this->schoolA->tokens['farid'])->withHeaders(['X-School-Id' => '1']);
    }

    private function examNotice(): Notification
    {
        return app(Notifier::class)->makeNotice('academics.exam_changed', 'Exam changed: Mathematics', 'Mathematics is now on 3 Nov 2026, 08:00–10:00.', '/calendar',
            excerpt: 'Mathematics is now on 3 Nov 2026, 08:00–10:00, Hall A.', body: 'Mathematics is now on 3 Nov 2026, 08:00–10:00, Hall A. It was on 2 Nov 2026.', target: '/calendar');
    }

    public function test_make_notice_stores_three_layers_and_a_target(): void
    {
        $notice = $this->examNotice()->fresh();

        $this->assertSame('Exam changed: Mathematics', $notice->title);
        $this->assertSame('Mathematics is now on 3 Nov 2026, 08:00–10:00, Hall A.', $notice->excerpt);
        $this->assertStringContainsString('It was on 2 Nov 2026.', $notice->body);
        $this->assertSame('/calendar', $notice->target);
        $this->assertSame('/calendar', $notice->delivery_payload['link']);
        $long = app(Notifier::class)->makeNotice('comms.notice', 'Long', 'Short', '/notifications', excerpt: str_repeat('e', 200), body: str_repeat('b', 6000));
        $this->assertSame(140, mb_strlen((string) $long->fresh()->excerpt));
        $this->assertSame(Notifier::BODY_LIMIT, mb_strlen((string) $long->fresh()->body));
    }

    public function test_legacy_producers_still_work_with_defaults(): void
    {
        $notice = app(Notifier::class)->makeNotice('comms.notice', 'Sports day', 'Sports day is on Friday.', '/notifications')->fresh();
        $this->assertSame('Sports day is on Friday.', $notice->excerpt);
        $this->assertNull($notice->body);
        $this->assertSame('/notifications', $notice->target);

        // The legacy helper still builds its notice through makeNotice.
        $before = Notification::count();
        sendSimpleNotification([$this->parent->id], 'Homework set', 'A new assignment is ready.', 'assignment', '', null);
        $legacy = Notification::query()->latest('id')->firstOrFail();
        $this->assertSame($before + 1, Notification::count());
        $this->assertSame('A new assignment is ready.', $legacy->excerpt);
        $this->assertSame('/notifications', $legacy->target);
        $this->assertNull($legacy->body);

        // Installed apps (v1 lists) see exactly the fields they saw before.
        $this->assertArrayNotHasKey('body', $notice->toArray());
        $this->assertArrayNotHasKey('excerpt', $notice->toArray());
        $this->assertArrayNotHasKey('target', $notice->toArray());
        $this->deliver($this->examNotice(), (int) $this->parent->id);
        $rows = $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/get-notification')->assertOk()->json('data.data');
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertArrayNotHasKey('body', $row);
            $this->assertArrayNotHasKey('excerpt', $row);
            $this->assertArrayNotHasKey('target', $row);
        }
    }

    public function test_targets_must_be_app_paths(): void
    {
        $before = Notification::count();
        foreach (['https://evil.example/login', '//evil.example', 'calendar', "/calendar\r\nX-Evil: 1", '/a\\b'] as $target) {
            try {
                app(Notifier::class)->makeNotice('comms.notice', 'Notice', 'Message', '/notifications', target: $target);
                $this->fail("$target was accepted as a target.");
            } catch (RuntimeException) {
            }
        }
        $this->assertSame($before, Notification::count());
    }

    public function test_happy_path_list_then_detail(): void
    {
        $notice = $this->deliver($this->examNotice(), (int) $this->parent->id);
        $plain = $this->deliver(app(Notifier::class)->makeNotice('comms.notice', 'Sports day', 'Sports day is on Friday.', '/notifications'), (int) $this->parent->id);

        $list = $this->asParent()->getJson(self::BASE)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $items = collect($list->json('data.items'))->keyBy('id');
        $this->assertSame(['id' => $notice->id, 'type' => 'academics.exam_changed', 'title' => 'Exam changed: Mathematics',
            'excerpt' => 'Mathematics is now on 3 Nov 2026, 08:00–10:00, Hall A.', 'target' => '/calendar', 'has_body' => true],
            collect($items[$notice->id])->except('date')->all());
        $this->assertFalse($items[$plain->id]['has_body']);
        $this->assertStringNotContainsString('It was on', $list->getContent());
        $this->assertSame((int) $this->parent->id, $list->json('data.reader_id'));

        $this->getJson(self::BASE.'/'.$notice->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.body', 'Mathematics is now on 3 Nov 2026, 08:00–10:00, Hall A. It was on 2 Nov 2026.')
            ->assertJsonPath('data.target', '/calendar')->assertJsonPath('data.has_body', true);
        $this->getJson(self::BASE.'/'.$plain->id)->assertOk()->assertJsonPath('data.body', 'Sports day is on Friday.');
    }

    public function test_wrong_owner_another_users_notice_is_not_found(): void
    {
        $learner = (int) $this->schoolA->ids['ben_user'];
        $notice = $this->deliver($this->examNotice(), $learner);
        $links = UserNotification::count();

        $list = $this->asParent()->getJson(self::BASE)->assertOk();
        $this->assertNotContains($notice->id, array_column($list->json('data.items'), 'id'));
        $this->getJson(self::BASE.'/'.$notice->id)->assertNotFound()->assertJsonMissingPath('data.body');
        $this->assertSame($links, UserNotification::count());
        $this->assertSame(1, UserNotification::where('notification_id', $notice->id)->where('user_id', $learner)->count());
    }

    public function test_wrong_school_another_schools_notice_is_not_found(): void
    {
        $other = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            $notice = app(Notifier::class)->makeNotice('comms.notice', 'Baobab notice', 'Baobab message', '/notifications', body: 'Baobab private body');
            // Even a forged link for School A's parent stays in School B.
            UserNotification::create(['notification_id' => $notice->id, 'user_id' => $this->parent->id]);

            return $notice;
        });
        $row = Notification::withoutGlobalScopes()->findOrFail($other->id)->getAttributes();

        $list = $this->asParent()->getJson(self::BASE)->assertOk();
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, $list->getContent());
        $detail = $this->getJson(self::BASE.'/'.$other->id)->assertNotFound();
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, $detail->getContent());
        // Asking in School B's context: the parent has no membership there.
        $this->withHeader('X-School-Id', (string) self::SCHOOL_B_ID)->getJson(self::BASE.'/'.$other->id)->assertForbidden();
        $this->assertSame($row, Notification::withoutGlobalScopes()->findOrFail($other->id)->getAttributes());
    }

    public function test_wrong_role_an_account_without_a_role_is_refused(): void
    {
        $user = User::forceCreate(['first_name' => 'No', 'last_name' => 'Role', 'email' => 'norole'.uniqid().'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789']);
        $notice = $this->deliver($this->examNotice(), (int) $user->id);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson(self::BASE)->assertForbidden();
        $this->withToken($token)->getJson(self::BASE.'/'.$notice->id)->assertForbidden()->assertJsonMissingPath('data.body');
    }

    public function test_a_private_type_never_exposes_content_in_excerpt_or_body(): void
    {
        config(['private_notices.scopes' => array_replace(config('private_notices.scopes'), ['private.' => LayersFixtureScope::class])]);
        $notifier = app(Notifier::class);
        $private = $this->deliver($notifier->makeNotice('private.updated', 'Private update', 'Open the app to review an update.', '/private-source',
            excerpt: 'SECRET excerpt about a learner', body: 'SECRET body about a learner'), (int) $this->parent->id);
        $this->assertSame('Open the app to review an update.', $private->fresh()->excerpt);
        $this->assertNull($private->fresh()->body);

        // Notice-only types (config notice_only) and real private scopes alike.
        foreach (['pastoral.cover_assigned', 'wellbeing.report_reply', 'transfer.consent_requested'] as $type) {
            $notice = $notifier->makeNotice($type, 'Update', 'Open NavigateEd.', '/notifications', excerpt: 'SECRET', body: 'SECRET');
            $this->assertSame('Open NavigateEd.', $notice->fresh()->excerpt, $type);
            $this->assertNull($notice->fresh()->body, $type);
        }

        // A row written some other way still shows only its generic message.
        $forced = $this->deliver($notifier->makeNotice('private.updated', 'Private update', 'Open the app.', '/private-source'), (int) $this->parent->id);
        $forced->forceFill(['excerpt' => 'SECRET forced excerpt', 'body' => 'SECRET forced body'])->save();
        $denied = $this->deliver($notifier->makeNotice('private.updated', 'Withdrawn source', 'Open the app.', '/private-source'), (int) $this->parent->id);

        $list = $this->asParent()->getJson(self::BASE)->assertOk();
        $this->assertStringNotContainsString('SECRET', $list->getContent());
        $items = collect($list->json('data.items'))->keyBy('id');
        $this->assertFalse($items[$private->id]['has_body']);
        $this->assertSame('Open the app.', $items[$forced->id]['excerpt']);
        $this->assertFalse($items[$forced->id]['has_body']);
        $this->assertArrayNotHasKey($denied->id, $items->all());

        $this->getJson(self::BASE.'/'.$private->id)->assertOk()->assertJsonPath('data.body', 'Open the app to review an update.');
        $forcedDetail = $this->getJson(self::BASE.'/'.$forced->id)->assertOk()->assertJsonPath('data.body', 'Open the app.');
        $this->assertStringNotContainsString('SECRET', $forcedDetail->getContent());
        $this->getJson(self::BASE.'/'.$denied->id)->assertNotFound();
    }

    public function test_detail_rechecks_the_source_freshly_before_the_body(): void
    {
        // A report notice whose report card no longer exists is still listed
        // (the row is generic) but its body is refused by the delivery check.
        $notice = app(Notifier::class)->makeNotice('academics.report_published', 'Report published', 'A report is ready.', '/academics/reports', body: 'Full report text');
        $notice->delivery_payload = ['link' => '/academics/reports', 'report_card_id' => 999999, 'report_learner_id' => (int) $this->schoolA->ids['asha']];
        $notice->save();
        $this->deliver($notice, (int) $this->parent->id);

        $list = $this->asParent()->getJson(self::BASE)->assertOk();
        $this->assertContains($notice->id, array_column($list->json('data.items'), 'id'));
        $this->getJson(self::BASE.'/'.$notice->id)->assertNotFound();
        $this->assertStringNotContainsString('Full report text', $this->getJson(self::BASE.'/'.$notice->id)->getContent());

        // The service refuses the same way when called directly.
        $this->expectException(HttpException::class);
        app(NotificationInbox::class)->show($this->parent, (int) $notice->id);
    }

    public function test_page_validation(): void
    {
        $this->asParent()->getJson(self::BASE.'?page=0')->assertUnprocessable();
        $this->getJson(self::BASE.'?page=2')->assertOk()->assertJsonPath('data.page', 2);
    }
}

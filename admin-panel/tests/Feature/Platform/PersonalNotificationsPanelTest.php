<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\NotificationPreference;
use App\Models\Platform\NotificationQuietHours;
use App\Models\SessionYear;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class PersonalNotificationsPanelTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Queue::fake();
    }

    private function context(): array
    {
        return ['expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => 1];
    }

    private function notice(int $recipient, string $title): int
    {
        $notice = app(Notifier::class)->makeNotice('comms.notice', $title, 'A school update', '/home', body: 'Saved full text <script>unsafe()</script>');
        UserNotification::create(['notification_id' => $notice->id, 'user_id' => $recipient]);

        return (int) $notice->id;
    }

    public function test_inbox_reads_only_current_school_recipient_and_escapes_the_body(): void
    {
        $admin = $this->superAdmin();
        $own = $this->notice((int) $admin->id, 'Own school notice');
        $other = $this->notice((int) $this->schoolA->ids['tara_user'], 'Other recipient secret');
        $foreign = app(CurrentSchool::class)->run(2, fn () => $this->notice((int) $admin->id, 'Foreign school secret'));
        $this->actingAs($admin)->get('/my-school/inbox')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Own school notice')->assertDontSee('Other recipient secret')->assertDontSee('Foreign school secret');
        $this->get('/my-school/inbox/'.$own)->assertOk()->assertSee('Saved full text &lt;script&gt;', false)->assertDontSee('<script>unsafe()', false);
        $this->get('/my-school/inbox/'.$other)->assertNotFound();
        $this->get('/my-school/inbox/'.$foreign)->assertNotFound();
    }

    public function test_personal_preferences_render_and_ignore_another_users_identifier(): void
    {
        $admin = $this->superAdmin();
        $other = User::findOrFail($this->schoolA->ids['tara_user']);
        foreach (['notifications', 'quiet-hours', 'texts'] as $screen) {
            $this->actingAs($admin)->get('/my-school/preferences/'.$screen)->assertOk()->assertSee('My notifications');
        }
        $this->postJson('/my-school/preferences/notifications', $this->context() + [
            'type' => 'comms.notice', 'channel' => 'push', 'enabled' => false, 'user_id' => $other->id,
        ])->assertOk()->assertJsonPath('error', false);
        $this->assertFalse((bool) NotificationPreference::where('user_id', $admin->id)->where('type', 'comms.notice')->where('channel', 'push')->sole()->enabled);
        $this->assertFalse(NotificationPreference::where('user_id', $other->id)->exists());
    }

    public function test_stale_account_or_school_is_refused_before_any_write(): void
    {
        $this->actingAs($this->superAdmin());
        $value = ['enabled' => true, 'starts_at' => '22:00', 'ends_at' => '06:00', 'timezone' => 'Africa/Johannesburg'];
        foreach ([['expected_school_id' => 2], ['expected_account_id' => $this->schoolA->ids['tara_user']]] as $stale) {
            $this->postJson('/my-school/preferences/quiet-hours', $stale + $this->context() + $value)->assertConflict();
        }
        $this->assertSame(0, NotificationQuietHours::count());
        $this->postJson('/my-school/preferences/quiet-hours', $this->context() + $value)->assertOk()->assertJsonPath('data.starts_at', '22:00');
        $this->assertSame((int) $this->superAdmin()->id, (int) NotificationQuietHours::sole()->user_id);
    }

    public function test_quiet_hours_validate_and_required_channels_cannot_be_disabled(): void
    {
        $this->actingAs($this->superAdmin());
        $this->postJson('/my-school/preferences/quiet-hours', $this->context() + ['enabled' => true, 'starts_at' => '22:00', 'ends_at' => '22:00'])->assertUnprocessable()->assertJsonValidationErrors('ends_at');
        $this->postJson('/my-school/preferences/notifications', $this->context() + ['type' => 'account.sign_in_link', 'channel' => 'email', 'enabled' => false])->assertUnprocessable();
    }

    public function test_personal_preferences_remain_writable_while_viewing_a_past_year(): void
    {
        $past = SessionYear::create(['name' => 'Past preference review', 'start_date' => '2021-01-01', 'end_date' => '2021-12-31']);
        $this->actingAs($this->superAdmin())->withSession(['session_year' => $past->id]);
        $this->get('/my-school/preferences/quiet-hours')->assertOk()->assertSee('data-year-safe', false);
        $this->postJson('/my-school/preferences/quiet-hours', $this->context() + [
            'enabled' => true, 'starts_at' => '22:00', 'ends_at' => '06:00', 'timezone' => 'Africa/Johannesburg',
        ])->assertOk()->assertJsonPath('data.enabled', true);
    }

    public function test_staff_only_private_inbox_cannot_be_read_or_marked_by_a_parent(): void
    {
        $parent = User::role('Parent')->firstOrFail();
        $this->actingAs($parent)->get('/my-school/inbox/private')->assertRedirect(route('login'));
        $this->actingAs($parent)->postJson('/my-school/inbox/private/1/read', ['expected_account_id' => $parent->id, 'expected_school_id' => 1])->assertRedirect(route('login'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\NotificationPreference;
use App\Models\Platform\NotificationRule;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class NotificationRulesTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    public function test_guardian_absence_notice_stays_mandatory_even_if_database_choices_say_off(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        NotificationRule::query()->create(['portal' => 'parent', 'type' => 'attendance.absence', 'channel' => 'email', 'mode' => 'off']);
        NotificationPreference::query()->create(['user_id' => $parent->id, 'type' => 'attendance.absence', 'channel' => 'email', 'enabled' => false]);
        $rules = app(NotificationRules::class);
        $this->assertTrue($rules->allows($parent, 'attendance.absence', 'email'));
        $this->expectException(ValidationException::class);
        $rules->choose($parent, 'attendance.absence', 'email', false);
    }

    public function test_optional_defaults_and_explicit_choices_are_respected(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $rules = app(NotificationRules::class);
        $this->assertTrue($rules->allows($parent, 'comms.notice', 'push'));
        $this->assertFalse($rules->allows($parent, 'comms.notice', 'email'));
        $rules->choose($parent, 'comms.notice', 'email', true);
        $this->assertTrue($rules->allows($parent, 'comms.notice', 'email'));
        $rules->choose($parent, 'comms.notice', 'push', false);
        $this->assertFalse($rules->allows($parent, 'comms.notice', 'push'));
    }

    public function test_not_offered_channels_are_absent_and_cannot_be_enabled(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $rules = app(NotificationRules::class);
        $rules->change($this->superAdmin(), 'parent', 'comms.notice', 'email', 'off');
        $row = collect($rules->preferences($parent))->firstWhere('type', 'comms.notice');
        $this->assertArrayNotHasKey('email', $row['channels']);
        $this->expectException(ValidationException::class);
        $rules->choose($parent, 'comms.notice', 'email', true);
    }

    public function test_school_mandatory_mode_overrides_an_earlier_optional_choice(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $rules = app(NotificationRules::class);
        $rules->choose($parent, 'comms.notice', 'email', false);
        $rules->change($this->superAdmin(), 'parent', 'comms.notice', 'email', 'mandatory');
        $this->assertTrue($rules->allows($parent, 'comms.notice', 'email'));
        $row = collect($rules->preferences($parent))->firstWhere('type', 'comms.notice');
        $this->assertTrue($row['channels']['email']['locked']);
        $this->assertNotEmpty($row['channels']['email']['reason']);
    }

    public function test_off_modules_disappear_from_context_and_settings(): void
    {
        app(ModuleGate::class)->change('chat', false, $this->superAdmin());
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $rules = app(NotificationRules::class);
        $this->assertFalse($rules->allows($parent, 'comms.message', 'push'));
        $this->assertNotContains('comms.message', array_column($rules->preferences($parent), 'type'));
        $this->assertNotContains('comms.message', array_column($rules->context($parent), 'type'));
    }

    public function test_school_a_settings_do_not_change_school_b(): void
    {
        $rules = app(NotificationRules::class);
        $rules->change($this->superAdmin(), 'parent', 'comms.notice', 'push', 'off');
        $this->assertFalse($rules->allows(User::findOrFail($this->schoolA->ids['farid_user']), 'comms.notice', 'push'));
        app(CurrentSchool::class)->run(2, fn () => $this->assertTrue($rules->allows(User::findOrFail($this->schoolB->ids['farid_user']), 'comms.notice', 'push')));
        $this->assertSame(1, DB::table('notification_rules')->count());
    }

    public function test_preferences_endpoint_controls_only_the_authenticated_human(): void
    {
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/v2/me/notification-preferences')->assertOk();
        $this->putJson('/api/v2/me/notification-preferences', ['type' => 'comms.notice', 'channel' => 'push', 'enabled' => false, 'user_id' => $this->schoolA->ids['gina_user']])->assertOk();
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $this->schoolA->ids['farid_user'], 'enabled' => false]);
        $this->assertDatabaseMissing('notification_preferences', ['user_id' => $this->schoolA->ids['gina_user']]);
    }

    public function test_locked_preferences_wrong_school_and_unknown_channel_are_refused(): void
    {
        $this->withToken($this->schoolA->tokens['farid'])->putJson('/api/v2/me/notification-preferences', ['type' => 'attendance.absence', 'channel' => 'email', 'enabled' => false])->assertUnprocessable();
        $this->putJson('/api/v2/me/notification-preferences', ['type' => 'comms.notice', 'channel' => 'fax', 'enabled' => false])->assertUnprocessable();
        $this->app['auth']->forgetGuards();
        $this->withHeader('X-School-Id', '2')->getJson('/api/v2/me/notification-preferences')->assertForbidden();
        $this->assertSame(0, NotificationPreference::query()->count());
    }

    public function test_school_admin_can_view_the_grid_and_change_an_optional_rule(): void
    {
        $this->actingAs($this->superAdmin())->get('/notification-rules?portal=parent')->assertOk()->assertSee('Required by NavigateEd');
        $this->post('/notification-rules', ['portal' => 'parent', 'type' => 'comms.notice', 'channel' => 'email', 'mode' => 'default_on'])->assertRedirect('/notification-rules?portal=parent');
        $this->assertDatabaseHas('notification_rules', ['school_id' => 1, 'portal' => 'parent', 'type' => 'comms.notice', 'mode' => 'default_on']);
        $this->post('/notification-rules', ['portal' => 'parent', 'type' => 'account.password_reset', 'channel' => 'email', 'mode' => 'off'])->assertSessionHasErrors('mode');
    }

    public function test_parent_and_teacher_cannot_configure_the_school_grid(): void
    {
        foreach (['farid_user', 'tara_user'] as $person) {
            $this->actingAs(User::findOrFail($this->schoolA->ids[$person]))->get('/notification-rules')->assertForbidden();
        }
    }

    public function test_school_membership_roles_are_used_even_when_a_user_object_crosses_contexts(): void
    {
        $user = User::findOrFail($this->schoolA->ids['farid_user']);
        $user->load('roles');
        app(CurrentSchool::class)->run(2, function () use ($user) {
            $user->assignRole('Teacher');
            $this->assertSame(['teacher'], app(NotificationRules::class)->portals($user));
            $this->assertFalse(app(NotificationRules::class)->allows($user, 'attendance.absence', 'email'));
        });
    }
}

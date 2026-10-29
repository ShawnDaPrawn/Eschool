<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendPush;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\School;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Money\WalletSettings;
use App\Services\Panel\Navigation;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Modules\ModuleRoutes;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ModuleControlsTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    public function test_upgraded_schools_keep_their_seed_features(): void
    {
        foreach (['chat', 'online_exams', 'fees', 'cards', 'intake'] as $module) {
            $this->assertTrue(app(ModuleGate::class)->allows($module));
        }
    }

    public function test_explicit_packages_start_fees_off_and_cannot_grant_their_own_entitlement(): void
    {
        School::query()->whereKey(2)->update(['package_key' => 'core']);
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, function () use ($admin) {
            $this->assertFalse(app(ModuleGate::class)->allows('fees'));
            SchoolModule::query()->create(['module' => 'fees', 'enabled' => true, 'in_package' => true]);
            app(ModuleGate::class)->forget();
            $this->assertFalse(app(ModuleGate::class)->allows('fees'));
            $this->expectException(ValidationException::class);
            app(ModuleGate::class)->change('fees', true, $admin);
        });
    }

    public function test_required_features_cannot_be_disabled(): void
    {
        $this->actingAs($this->superAdmin())->put('/school-modules/people', ['enabled' => false])->assertSessionHasErrors('module');
        $this->assertTrue(app(ModuleGate::class)->allows('people'));
    }

    public function test_school_admin_switch_hides_routes_navigation_and_context_together(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->put('/school-modules/chat', ['enabled' => false])->assertRedirect('/school-modules');
        $this->get('/chat-settings')->assertNotFound();
        $this->assertFalse(app(Navigation::class)->moduleEnabled('chat'));
        $this->assertSame('', trim(Blade::render("@module('chat') visible @endmodule")));
        $this->app['auth']->forgetGuards();
        $answer = $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/v2/me/context')->assertOk();
        $this->assertNotContains('chat', array_column($answer->json('data.modules'), 'key'));
        $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/student/get-user-list')->assertNotFound();
        $this->assertDatabaseHas('school_modules', ['school_id' => 1, 'module' => 'chat', 'enabled' => false, 'changed_by' => $admin->id]);
        $this->assertDatabaseHas('audit_log', ['subject_type' => SchoolModule::class, 'action' => 'updated']);
    }

    public function test_a_school_switch_never_reuses_another_schools_module_state(): void
    {
        app(ModuleGate::class)->change('chat', false, $this->superAdmin());
        $this->assertFalse(app(ModuleGate::class)->allows('chat'));
        app(CurrentSchool::class)->run(2, fn () => $this->assertTrue(app(ModuleGate::class)->allows('chat')));
        $this->assertFalse(app(ModuleGate::class)->allows('chat'));
    }

    public function test_financial_records_become_read_only_without_being_deleted(): void
    {
        $count = DB::table('fees_paids')->where('school_id', 1)->count();
        $state = app(ModuleGate::class)->change('fees', false, $this->superAdmin());
        $this->assertTrue($state['enabled']);
        $this->assertTrue($state['read_only']);
        $this->assertSame($count, DB::table('fees_paids')->where('school_id', 1)->count());
        $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/student/fees-paid-list')->assertOk();
        $this->postJson('/api/student/store-fees', [])->assertForbidden();
        $this->getJson('/api/student/send-fee-notification')->assertForbidden();
        $this->assertTrue(app(ModuleGate::class)->change('fees', true, $this->superAdmin())['enabled']);
        $this->assertFalse(app(ModuleGate::class)->state('fees')['read_only']);
    }

    public function test_paused_dependency_does_not_enable_unused_money_modules(): void
    {
        $admin = $this->superAdmin();
        $gate = app(ModuleGate::class);
        $gate->change('wallet', true, $admin);
        $gate->change('vendors', true, $admin);
        app(WalletSettings::class)->save($admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        $state = $gate->change('wallet', false, $admin);
        $this->assertTrue($state['enabled']);
        $this->assertTrue($state['read_only']);
        foreach (['wallet_payments', 'ticketing'] as $module) {
            $this->assertFalse($gate->allows($module));
            $this->assertFalse($gate->writable($module));
            $this->assertNotContains($module, array_column($gate->context(), 'key'));
        }
        $gate->change('vendors', false, $admin);
        $this->assertFalse($gate->allows('vendors'));
        app(CurrentSchool::class)->run(2, fn () => $this->assertFalse($gate->allows('wallet_payments')));
        // A retained module's own history remains readable even with a paused
        // dependency and removed entitlement. It never becomes writable.
        SchoolModule::query()->updateOrCreate(['module' => 'wallet_payments'], ['enabled' => true, 'in_package' => false, 'read_only' => true]);
        $gate->forget();
        $this->assertTrue($gate->allows('wallet_payments'));
        $this->assertFalse($gate->writable('wallet_payments'));
    }

    public function test_dependency_switches_fail_without_partial_changes(): void
    {
        config(['modules.online_exams.dependencies' => ['chat']]);
        try {
            app(ModuleGate::class)->change('chat', false, $this->superAdmin());
            $this->fail('An enabled dependent was ignored');
        } catch (ValidationException) {
            $this->assertTrue(app(ModuleGate::class)->allows('chat'));
        }
        app(ModuleGate::class)->change('online_exams', false, $this->superAdmin());
        app(ModuleGate::class)->change('chat', false, $this->superAdmin());
        $this->expectException(ValidationException::class);
        app(ModuleGate::class)->change('online_exams', true, $this->superAdmin());
    }

    public function test_a_cycle_or_unknown_module_fails_closed(): void
    {
        config(['modules.chat.dependencies' => ['online_exams'], 'modules.online_exams.dependencies' => ['chat']]);
        $this->assertFalse(app(ModuleGate::class)->allows('chat'));
        $this->assertFalse(app(ModuleGate::class)->allows('invented'));
    }

    public function test_parent_teacher_and_other_school_token_cannot_change_school_settings(): void
    {
        foreach (['farid_user', 'tara_user'] as $person) {
            $this->actingAs(User::findOrFail($this->schoolA->ids[$person]))
                ->putJson('/school-modules/chat', ['enabled' => false])->assertForbidden();
        }
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolA->tokens['asha'])->withHeader('X-School-Id', '2')
            ->getJson('/api/v2/me/context')->assertForbidden();
    }

    public function test_only_the_chosen_school_is_changed_and_panel_renders(): void
    {
        $this->actingAs($this->superAdmin())->get('/school-modules')->assertOk()->assertSee('Direct messages')->assertSee('Required');
        $this->put('/school-modules/online_exams', ['enabled' => false])->assertRedirect();
        $this->assertFalse(app(ModuleGate::class)->allows('online_exams'));
        app(CurrentSchool::class)->run(2, fn () => $this->assertTrue(app(ModuleGate::class)->allows('online_exams')));
    }

    public function test_every_mapped_route_has_the_gate_including_legacy_direct_urls(): void
    {
        $count = 0;
        foreach (Route::getRoutes() as $route) {
            $module = ModuleRoutes::module($route);
            if ($module !== null) {
                $count++;
                $this->assertNotEmpty(array_filter($route->gatherMiddleware(), fn ($name) => str_starts_with($name, 'module:'.$module.',')), $route->uri());
            }
        }
        $this->assertGreaterThan(70, $count);
    }

    public function test_module_changes_queue_a_silent_refresh_for_only_the_changed_school(): void
    {
        Queue::fake([SendPush::class]);
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, function () use ($admin) {
            app(ModuleGate::class)->change('chat', false, $admin);
            $event = OutboxEvent::query()->where('type', 'context.changed')->latest('id')->firstOrFail();
            $this->assertSame('dispatched', Outbox::dispatchOne($event->id));
        });
        Queue::assertPushed(SendPush::class, 2);
        Queue::assertPushed(SendPush::class, fn ($job) => $job->message['message']['topic'] === 'school_2_all_android'
            && $job->message['message']['data'] === ['type' => 'context.changed', 'school_id' => '2']);
        $this->assertSame(0, DB::table('notification_deliveries')->where('school_id', 1)->count());
    }

    public function test_disabling_a_web_portal_leaves_native_access_available(): void
    {
        app(ModuleGate::class)->change('web_learner', false, $this->superAdmin());
        config(['sanctum.stateful' => ['portal.example.test']]);
        $this->withToken($this->schoolA->tokens['asha'])->getJson('/api/student/dashboard')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->actingAs(User::findOrFail($this->schoolA->ids['asha_user']))->withHeader('Origin', 'https://portal.example.test')
            ->getJson('/api/student/dashboard')->assertNotFound();
    }
}

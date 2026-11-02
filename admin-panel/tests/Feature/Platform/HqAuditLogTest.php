<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Saas\PlatformAuditLog;
use App\Services\Platform\Saas\PlatformSchools;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The HQ audit log (SA.3): platform actions only, filterable, exportable,
 * and closed to anyone who is not platform staff.
 * engineering/specs/platform/hq-audit-log.md.
 */
class HqAuditLogTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $email = 'hq.audit@platform.example'): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => 'Nomsa', 'last_name' => 'Operator', 'email' => $email, 'mobile' => '', 'password' => 'Synthetic-hq-audit-428!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** One platform change and one ordinary school change, so the filter has something to leave out. */
    private function history(User $staff): void
    {
        Auth::setUser($staff);
        app(PlatformSchools::class)->changePackage(self::SCHOOL_B_ID, 'standard', $staff);
        app(PlatformSchools::class)->changeStatus(self::SCHOOL_B_ID, School::SUSPENDED, '=Overdue contract, suspended after notice', $staff);
        Auth::setUser($this->superAdmin());
        app(CurrentSchool::class)->run(1, fn () => Audit::record('school.information_updated', null, ['motto' => 'Old'], ['motto' => 'New'], 1));
        Auth::forgetUser();
    }

    public function test_staff_see_platform_actions_with_reasons_and_never_a_schools_own_history(): void
    {
        $staff = $this->staff();
        $this->history($staff);

        $response = $this->actingAsHq($staff)->get('/saas/audit')->assertOk()
            ->assertSee('Package changed')->assertSee('School status changed')
            ->assertSee('Overdue contract, suspended after notice')
            ->assertSee('→ standard', false)
            ->assertDontSee('motto');
        $rows = $response->viewData('rows');
        // Newest first; the staff identity and grant rows (created) count as platform actions too.
        $this->assertSame(['platform_school_status_changed', 'platform_school_package_changed', 'created', 'created'], collect($rows->items())->pluck('action')->all());
        $subjects = collect($rows->items())->slice(2)->map(fn ($row) => explode(' #', (string) $row['subject'])[0])->sort()->values()->all();
        $this->assertSame(['PlatformAdminGrant', 'PlatformStaffIdentity'], $subjects);
        $this->assertSame($staff->email, $rows->items()[0]['actor_email']);
    }

    public function test_filters_narrow_by_school_action_staff_member_and_date(): void
    {
        $staff = $this->staff();
        $other = $this->staff('hq.audit.other@platform.example');
        $this->history($staff);
        Auth::setUser($other);
        app(PlatformSchools::class)->changeModule(1, 'gallery', false, $other);
        Auth::forgetUser();

        $this->actingAsHq($staff);
        // Only the named platform actions: the module row change (updated) and staff set-up (created) ride along.
        $actions = fn (array $query) => collect($this->get('/saas/audit?'.http_build_query($query))->assertOk()->viewData('rows')->items())->pluck('action')->filter(fn ($a) => str_starts_with($a, 'platform_'))->values()->all();

        $this->assertSame(['platform_school_module_changed'], $actions(['school' => 1]));
        $this->assertSame(['platform_school_package_changed'], $actions(['action' => 'platform_school_package_changed']));
        $this->assertSame(['platform_school_module_changed'], $actions(['actor' => $other->id]));
        $this->assertCount(3, $actions(['from' => now(PlatformAuditLog::TIMEZONE)->toDateString(), 'to' => now(PlatformAuditLog::TIMEZONE)->toDateString()]));
        $this->assertSame([], $actions(['to' => now(PlatformAuditLog::TIMEZONE)->subDay()->toDateString()]));
        // Unusable filters are ignored rather than failing the page.
        $this->assertCount(3, $actions(['from' => '2026-02-31', 'action' => "x'; drop", 'school' => 'abc']));
    }

    public function test_export_is_csv_safe_from_formulas_and_is_itself_audited(): void
    {
        $staff = $this->staff();
        $this->history($staff);

        $response = $this->actingAsHq($staff)->get('/saas/audit/export?school='.self::SCHOOL_B_ID)->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $lines = array_values(array_filter(explode("\n", $csv)));
        $this->assertStringStartsWith('when,school,who,email,action', $lines[0]);
        $this->assertCount(3, $lines);
        $this->assertStringContainsString("'=Overdue contract", $csv);

        $export = app(CurrentSchool::class)->acrossSchools(fn () => AuditLog::query()->where('action', 'platform_audit_exported')->sole());
        $this->assertSame($staff->id, $export->actor_id);
        $this->assertSame(['school' => self::SCHOOL_B_ID], $export->after['filters']);
        $this->assertSame(2, $export->after['rows']);
        // The export now shows in the log too.
        $this->get('/saas/audit')->assertSee('Audit log exported');
    }

    public function test_secret_looking_values_are_never_shown(): void
    {
        $staff = $this->staff();
        Auth::setUser($staff);
        app(CurrentSchool::class)->acrossSchools(fn () => Audit::record('platform_staff_password_set', null, [], ['password_hash' => 'synthetic-hash-value', 'api_token' => 'synthetic-token-value', 'note' => 'visible']));
        Auth::forgetUser();

        $this->actingAsHq($staff)->get('/saas/audit')->assertOk()
            ->assertDontSee('synthetic-hash-value')->assertDontSee('synthetic-token-value')
            ->assertSee('note: visible');
    }

    public function test_only_platform_staff_open_it(): void
    {
        $this->staff();
        $schoolAdmin = $this->superAdmin();

        foreach (['/saas/audit', '/saas/audit/export'] as $url) {
            $this->assertNotSame(200, $this->actingAs($schoolAdmin)->get($url)->getStatusCode(), $url);
        }
        $this->assertSame(0, app(CurrentSchool::class)->acrossSchools(fn () => AuditLog::query()->where('action', 'platform_audit_exported')->count()));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/** The product catalogue page in /saas (Action Plan 1.16). */
class ProductCatalogueTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    public function test_platform_staff_see_the_product_modules_and_packages(): void
    {
        $user = $this->superAdmin();
        PlatformStaffIdentity::query()->updateOrCreate(['user_id' => $user->id], ['active' => true]);
        PlatformAdminGrant::query()->updateOrCreate(['user_id' => $user->id], ['active' => true, 'expires_at' => now()->addHour()]);

        $this->actingAsHq($user)->get('/saas/packages')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Product modules and packages')->assertSee('Pastoral care')->assertSee('Level 3')->assertSee('Not priced yet')->assertSee('pilot');
    }

    public function test_school_staff_without_the_platform_grant_are_refused(): void
    {
        $this->actingAs($this->superAdmin())->get('/saas/packages')->assertForbidden();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/saas/packages')->assertForbidden();
    }

    public function test_a_school_can_be_put_on_the_pilot_package(): void
    {
        $user = $this->superAdmin();
        PlatformStaffIdentity::query()->updateOrCreate(['user_id' => $user->id], ['active' => true]);
        PlatformAdminGrant::query()->updateOrCreate(['user_id' => $user->id], ['active' => true, 'expires_at' => now()->addHour()]);
        $this->actingAsHq($user)->put('/saas/schools/2/package', ['package_key' => 'pilot'])->assertRedirect('/saas/schools/2');
        $this->assertSame('pilot', School::query()->findOrFail(2)->package_key);
    }
}

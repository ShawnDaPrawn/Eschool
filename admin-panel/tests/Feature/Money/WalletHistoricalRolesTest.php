<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class WalletHistoricalRolesTest extends TestCase
{
    use UsesTwoSchools;

    private User $parent;

    private User $teacher;

    private int $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $historical = Students::findOrFail($this->schoolA->ids['asha'])->replicate();
        // Existing guardian identities are retained by the model's normal hook.
        $historical->forceFill(['user_id' => $this->teacher->id, 'admission_no' => 'HISTORICAL-ADULT'])->save();
        app(ModuleGate::class)->change('wallet', true, $admin);
        app(WalletSettings::class)->save($admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        $this->wallet = app(Wallets::class)->open($this->teacher, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $this->teacher->id])->id;
    }

    public function test_current_adult_controls_are_not_inherited_from_a_historical_student_row(): void
    {
        $summary = app(Wallets::class)->statement($this->teacher, $this->wallet);
        $this->assertFalse($summary['is_child']);
        $this->assertTrue($summary['can_manage_limits']);
        app(Wallets::class)->controls($this->teacher, $this->wallet, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 1000, 'all_categories' => true, 'categories' => []]);
        $this->assertSame(1000, app(Wallets::class)->statement($this->teacher, $this->wallet)['limits']['effective_daily_spend_cents']);
    }

    public function test_historical_guardian_links_do_not_expose_an_active_or_departed_adults_wallet(): void
    {
        foreach ([true, false] as $active) {
            if (! $active) {
                SchoolMembership::where('user_id', $this->teacher->id)->where('role', 'Teacher')->update(['status' => 'inactive']);
            }
            try {
                app(Wallets::class)->statement($this->parent, $this->wallet);
                $this->fail('A historical guardian link cannot expose an adult wallet.');
            } catch (HttpExceptionInterface $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
            $owners = app(Wallets::class)->accessible($this->parent)['owners'];
            $this->assertNotContains($this->teacher->id, array_column($owners, 'owner_user_id'));
            $this->assertContains($this->parent->id, array_column($owners, 'owner_user_id'));
        }
    }
}

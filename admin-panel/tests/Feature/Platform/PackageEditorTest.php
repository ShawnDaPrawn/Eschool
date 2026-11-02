<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Saas\PlatformPackages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The HQ package and price editor (Action Plan SA.7): packages and their
 * effective-dated prices in the database, edited only with packages.manage,
 * and a package change that reaches existing schools only when re-applied.
 * engineering/specs/platform/package-editor.md.
 */
class PackageEditorTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    private const TABLES = ['platform_packages', 'platform_package_modules', 'platform_prices'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function staff(string $role): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $role.'@platform.example', 'mobile' => '', 'password' => 'Synthetic-packages-password-418!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addHour()]);

        return $user;
    }

    private function packageId(string $key): int
    {
        return (int) DB::table('platform_packages')->where('package_key', $key)->value('id');
    }

    private function day(int $offset): string
    {
        return CarbonImmutable::parse(PlatformPackages::today())->addDays($offset)->toDateString();
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return array_combine(self::TABLES, array_map(fn (string $table): int => DB::table($table)->count(), self::TABLES))
            + ['audit' => AuditLog::query()->where('action', 'like', 'platform_package_%')->count()];
    }

    private function moduleRows(int $school): array
    {
        return DB::table('school_modules')->where('school_id', $school)->orderBy('module')->get()->map(fn ($row) => (array) $row)->all();
    }

    private function packages(): PlatformPackages
    {
        return app(PlatformPackages::class);
    }

    public function test_the_seeded_packages_match_the_old_config(): void
    {
        $config = (array) config('school_packages');
        $this->assertSame(array_keys($config), $this->packages()->keys());
        $this->assertSame(array_keys($config), array_keys($this->packages()->assignable()));
        foreach ($config as $key => $modules) {
            $package = DB::table('platform_packages')->where('package_key', $key)->first();
            $this->assertSame(['active', 1, 0], [$package->status, (int) $package->modules_version, (int) $package->trial_days], $key);
            $this->assertSame($this->sorted($modules), $this->sorted($this->packages()->technicalModules($key)), "{$key} grants exactly the modules it did");
            $this->assertSame($this->sorted($modules), $this->sorted($this->packages()->technicalModules($key, 1)));
        }
        $this->assertSame(0, DB::table('platform_prices')->count(), 'no price was decided, so none is seeded');
        $this->assertSame(0, School::query()->whereNotNull('package_version')->count());

        // Every school is entitled to exactly what the config gave it, and the gate agrees.
        School::query()->whereKey(2)->update(['package_key' => 'core']);
        foreach ([1 => 'legacy', 2 => 'core'] as $school => $key) {
            $this->assertSame($this->sorted($config[$key]), $this->sorted($this->packages()->forSchool(School::query()->findOrFail($school))));
            app(CurrentSchool::class)->run($school, function () use ($config, $key): void {
                app(ModuleGate::class)->forget();
                $this->assertTrue(app(ModuleGate::class)->state('online_exams')['in_package']);
                $this->assertSame(in_array('fees', $config[$key], true), app(ModuleGate::class)->state('fees')['in_package']);
            });
        }
    }

    /** @param  list<string>  $values */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    public function test_rolling_back_refuses_once_hq_changed_a_seeded_package(): void
    {
        $migration = require database_path('migrations/2026_11_01_140000_create_platform_packages.php');
        $refuses = function (string $why) use ($migration): void {
            $refusal = null;
            try {
                $migration->down();
            } catch (\RuntimeException $e) {
                $refusal = $e->getMessage();
            }
            $this->assertStringContainsString('Restore a tested backup', (string) $refusal, "down() refuses after {$why}");
            foreach (self::TABLES as $table) {
                $this->assertTrue(Schema::hasTable($table), "{$table} kept after {$why}");
            }
            $this->assertTrue(Schema::hasColumn('schools', 'package_version'));
        };

        // HQ renames a seeded package through the editor.
        $id = $this->packageId('standard');
        $this->packages()->update($id, ['name' => 'Standard (2027)', 'description' => 'Every product module.', 'trial_days' => 0], $this->staff('finance'));
        $refuses('a name change');

        // Every other column the seed set counts too, as does a later save.
        $seeded = (array) DB::table('platform_packages')->where('package_key', 'core')->first();
        foreach ([
            'a retirement' => ['status' => 'retired', 'retired_at' => now()],
            'a description change' => ['description' => 'Changed'],
            'a trial change' => ['trial_days' => 30],
            'a version change' => ['modules_version' => 2],
            'a later save' => ['updated_at' => CarbonImmutable::parse($seeded['created_at'])->addMinute()],
        ] as $why => $change) {
            DB::table('platform_packages')->where('package_key', 'core')->update($change);
            $refuses($why);
            DB::table('platform_packages')->where('package_key', 'core')->update($seeded);
        }
    }

    public function test_finance_creates_edits_and_activates_a_draft_package(): void
    {
        $finance = $this->staff('finance');
        $this->actingAsHq($finance)->post('/saas/packages', ['package_key' => 'starter', 'name' => 'Starter', 'description' => 'Core and learning', 'trial_days' => 30, 'product_modules' => ['learning']])
            ->assertRedirect();
        $id = $this->packageId('starter');
        $this->assertSame(['draft', 30, 1], [DB::table('platform_packages')->where('id', $id)->value('status'), (int) DB::table('platform_packages')->where('id', $id)->value('trial_days'), (int) DB::table('platform_packages')->where('id', $id)->value('modules_version')]);
        $this->assertSame(['core', 'learning'], $this->packages()->productModules($id, 1), 'Core is always included');
        $created = AuditLog::query()->where('action', 'platform_package_created')->sole();
        $this->assertSame([$finance->id, 'starter', ['core', 'learning']], [$created->actor_id, $created->after['package_key'], $created->after['product_modules']]);

        // A draft cannot be given to a school.
        $this->put('/saas/schools/2/package', ['package_key' => 'starter'])->assertSessionHasErrors('package_key');
        $this->assertNull(School::query()->findOrFail(2)->package_key);

        // While a draft, modules change in place.
        $this->put('/saas/packages/'.$id.'/modules', ['product_modules' => ['learning', 'pastoral']])->assertRedirect('/saas/packages/'.$id);
        $this->assertSame(1, (int) DB::table('platform_packages')->where('id', $id)->value('modules_version'));
        $this->assertSame(['core', 'learning', 'pastoral'], $this->packages()->productModules($id, 1));
        $changed = AuditLog::query()->where('action', 'platform_package_modules_changed')->sole();
        $this->assertSame([['core', 'learning'], ['core', 'learning', 'pastoral']], [$changed->before['product_modules'], $changed->after['product_modules']]);

        $this->put('/saas/packages/'.$id, ['name' => 'Starter plus', 'description' => '', 'trial_days' => 14])->assertRedirect();
        $updated = AuditLog::query()->where('action', 'platform_package_updated')->sole();
        $this->assertSame(['Starter', 30], [$updated->before['name'], $updated->before['trial_days']]);
        $this->assertSame(['Starter plus', 14], [$updated->after['name'], $updated->after['trial_days']]);

        $this->put('/saas/packages/'.$id.'/status', ['status' => 'active'])->assertRedirect();
        $this->assertSame(['draft', 'active'], [AuditLog::query()->where('action', 'platform_package_activated')->sole()->before['status'], AuditLog::query()->where('action', 'platform_package_activated')->sole()->after['status']]);
        $this->put('/saas/schools/2/package', ['package_key' => 'starter'])->assertRedirect('/saas/schools/2');
        $this->assertSame(['starter', 1], [School::query()->findOrFail(2)->package_key, School::query()->findOrFail(2)->package_version]);

        $this->get('/saas/packages')->assertOk()->assertSee('Starter plus')->assertSee('New package');
        $this->get('/saas/packages/'.$id)->assertOk()->assertSee('Add a price')->assertSee('Retire');

        // Retired: kept, no new school, nothing changes; the school on it keeps it.
        $this->put('/saas/packages/'.$id.'/status', ['status' => 'retired'])->assertRedirect();
        $this->put('/saas/packages/'.$id.'/modules', ['product_modules' => ['learning']])->assertSessionHasErrors('package');
        $this->put('/saas/packages/'.$id.'/status', ['status' => 'active'])->assertSessionHasErrors('status');
        $this->put('/saas/schools/1/package', ['package_key' => 'starter'])->assertSessionHasErrors('package_key');
        $this->assertSame('starter', School::query()->findOrFail(2)->package_key);
        $this->assertSame(['core', 'learning', 'pastoral'], $this->packages()->productModules($id, 1));
    }

    public function test_prices_are_effective_dated_never_overlap_and_are_never_edited_once_in_force(): void
    {
        $owner = $this->staff('owner');
        $id = $this->packageId('core');
        $this->actingAsHq($owner);
        $price = fn (array $input) => $this->post('/saas/packages/'.$id.'/prices', $input + ['note' => '']);

        $price(['kind' => 'base', 'amount' => '120', 'effective_from' => $this->day(10)])->assertRedirect('/saas/packages/'.$id);
        $this->assertNull($this->packages()->priceOn('core', $this->day(9)));
        $this->assertSame(12000, $this->packages()->priceOn('core', $this->day(10)));
        $this->assertSame(12000, $this->packages()->priceOn('core', CarbonImmutable::parse($this->day(400))));

        // A new price ends the open-ended one the day before it starts.
        $price(['kind' => 'base', 'amount' => '130.50', 'effective_from' => $this->day(40)])->assertRedirect();
        $first = DB::table('platform_prices')->where('package_id', $id)->orderBy('id')->first();
        $this->assertSame($this->day(39), $first->effective_to);
        $this->assertSame(12000, $this->packages()->priceOn('core', $this->day(39)));
        $this->assertSame(13050, $this->packages()->priceOn('core', $this->day(40)));

        // A discount off the base, and an add-on product module.
        $price(['kind' => 'discount', 'amount' => '10', 'effective_from' => $this->day(40), 'effective_to' => $this->day(70)])->assertRedirect();
        $price(['kind' => 'module', 'product_module' => 'finance', 'amount' => '15.5', 'effective_from' => $this->day(10)])->assertRedirect();
        $this->assertSame(12050, $this->packages()->priceOn('core', $this->day(70)));
        $this->assertSame(13050, $this->packages()->priceOn('core', $this->day(71)));
        $this->assertSame(1550, $this->packages()->priceOn('core', $this->day(71), 'finance'));
        $this->assertNull($this->packages()->priceOn('core', $this->day(9), 'finance'));
        $this->assertSame(['date' => $this->day(50), 'base_cents' => 13050, 'discount_cents' => 1000, 'net_cents' => 12050, 'modules' => ['finance' => 1550]], $this->packages()->quote('core', $this->day(50)));
        $this->get('/saas/packages/'.$id.'?on='.$this->day(50))->assertOk()->assertSee('R 120.50')->assertSee('R 15.50');
        $this->assertNull($this->packages()->priceOn('standard', $this->day(50)), 'prices belong to one package');

        // Overlaps are refused and write nothing.
        $before = $this->counts();
        $price(['kind' => 'discount', 'amount' => '5', 'effective_from' => $this->day(60), 'effective_to' => $this->day(80)])->assertSessionHasErrors('effective_from');
        $price(['kind' => 'base', 'amount' => '99', 'effective_from' => $this->day(50), 'effective_to' => $this->day(60)])->assertSessionHasErrors('effective_to');
        $price(['kind' => 'base', 'amount' => '99', 'effective_from' => $this->day(40)])->assertSessionHasErrors('effective_from');
        // Not in the past, not a module price without its module, not a zero discount.
        $price(['kind' => 'base', 'amount' => '99', 'effective_from' => $this->day(-1)])->assertSessionHasErrors('effective_from');
        $price(['kind' => 'module', 'amount' => '99', 'effective_from' => $this->day(90)])->assertSessionHasErrors('product_module');
        $price(['kind' => 'module', 'product_module' => 'core', 'amount' => '99', 'effective_from' => $this->day(90)])->assertSessionHasErrors('product_module');
        $price(['kind' => 'base', 'product_module' => 'finance', 'amount' => '99', 'effective_from' => $this->day(90)])->assertSessionHasErrors('product_module');
        $price(['kind' => 'discount', 'amount' => '0', 'effective_from' => $this->day(90)])->assertSessionHasErrors('amount');
        $this->assertSame($before, $this->counts());

        // A price not started yet can be withdrawn (the latest first); the price it ended is open-ended again.
        $second = DB::table('platform_prices')->where('package_id', $id)->where('kind', 'base')->orderByDesc('id')->value('id');
        $this->post('/saas/packages/'.$id.'/prices/'.$first->id.'/withdraw')->assertSessionHasErrors('price');
        $this->post('/saas/packages/'.$id.'/prices/'.$second.'/withdraw')->assertRedirect();
        $this->assertNull(DB::table('platform_prices')->where('id', $first->id)->value('effective_to'));
        $this->assertSame(11000, $this->packages()->priceOn('core', $this->day(50)), 'R 120 less the R 10 discount');
        $this->assertSame([false, true], [AuditLog::query()->where('action', 'platform_package_price_withdrawn')->sole()->before['withdrawn'], AuditLog::query()->where('action', 'platform_package_price_withdrawn')->sole()->after['withdrawn']]);
        $added = AuditLog::query()->where('action', 'platform_package_price_added')->orderBy('id')->first();
        $this->assertSame([12000, $this->day(10), true], [$added->after['amount_cents'], $added->after['effective_from'], $added->after['vat_excluded']]);

        // Once in force, a price stays as it is: it cannot be withdrawn, and nothing can start before today.
        $this->travelTo(CarbonImmutable::parse($this->day(20), PlatformPackages::TIMEZONE)->setTime(9, 0));
        PlatformAdminGrant::query()->where('user_id', $owner->id)->update(['expires_at' => now()->addHour()]);
        $before = $this->counts();
        $row = (array) DB::table('platform_prices')->where('id', $first->id)->first();
        $this->post('/saas/packages/'.$id.'/prices/'.$first->id.'/withdraw')->assertSessionHasErrors('price');
        $price(['kind' => 'base', 'amount' => '99', 'effective_from' => $this->day(-5)])->assertSessionHasErrors('effective_from');
        $this->assertSame($before, $this->counts());
        $this->assertSame($row, (array) DB::table('platform_prices')->where('id', $first->id)->first());
        // A new price from today ends the one in force yesterday; its past stays.
        $price(['kind' => 'base', 'amount' => '125', 'effective_from' => $this->day(0)])->assertRedirect();
        $this->assertSame([$this->day(-10), $this->day(-1), 12000], [DB::table('platform_prices')->where('id', $first->id)->value('effective_from'), DB::table('platform_prices')->where('id', $first->id)->value('effective_to'), (int) DB::table('platform_prices')->where('id', $first->id)->value('amount_cents')]);
        $this->assertSame(12000, $this->packages()->priceOn('core', $this->day(-1)));
        $this->assertSame(12500, $this->packages()->priceOn('core', $this->day(0)));
    }

    public function test_money_is_parsed_from_rands_text_without_floats(): void
    {
        foreach (['120' => 12000, '89.5' => 8950, '89.50' => 8950, '0.07' => 7, '0.1' => 10, '1234.56' => 123456, '999999.99' => 99999999, '0' => 0, '19.99' => 1999] as $rands => $cents) {
            $this->assertSame($cents, PlatformPackages::cents((string) $rands), (string) $rands);
        }
        foreach (['12,50', '1e3', '-5', '12.345', ' 12', '0x1A', '1234567', '.5', '5.', ''] as $bad) {
            try {
                PlatformPackages::cents($bad);
                $this->fail("{$bad} was accepted");
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $id = $this->packageId('standard');
        $this->actingAsHq($this->staff('finance'))->post('/saas/packages/'.$id.'/prices', ['kind' => 'base', 'amount' => '0.29', 'effective_from' => $this->day(1)])->assertRedirect();
        $this->assertSame(29, (int) DB::table('platform_prices')->where('package_id', $id)->value('amount_cents'));
        $this->post('/saas/packages/'.$id.'/prices', ['kind' => 'base', 'amount' => '12,50', 'effective_from' => $this->day(5)])->assertSessionHasErrors('amount');
        $this->assertSame(1, DB::table('platform_prices')->count());
    }

    public function test_sales_content_support_and_operations_cannot_change_packages_or_prices(): void
    {
        $id = $this->packageId('core');
        DB::table('platform_prices')->insert(['package_id' => $id, 'kind' => 'base', 'amount_cents' => 5000, 'effective_from' => $this->day(30), 'created_at' => now(), 'updated_at' => now()]);
        $priceId = (int) DB::table('platform_prices')->value('id');
        $before = $this->counts();
        $rows = [DB::table('platform_packages')->orderBy('id')->get()->all(), DB::table('platform_prices')->get()->all()];
        foreach (['sales', 'content', 'support', 'operations'] as $role) {
            $user = $this->staff($role);
            $this->actingAsHq($user);
            $this->post('/saas/packages', ['package_key' => 'sneaky-'.$role, 'name' => 'Sneaky', 'trial_days' => 0])->assertForbidden();
            $this->put('/saas/packages/'.$id, ['name' => 'Renamed', 'trial_days' => 0])->assertForbidden();
            $this->put('/saas/packages/'.$id.'/modules', ['product_modules' => []])->assertForbidden();
            $this->put('/saas/packages/'.$id.'/status', ['status' => 'retired'])->assertForbidden();
            $this->post('/saas/packages/'.$id.'/prices', ['kind' => 'base', 'amount' => '1', 'effective_from' => $this->day(90)])->assertForbidden();
            $this->post('/saas/packages/'.$id.'/prices/'.$priceId.'/withdraw')->assertForbidden();
            $this->post('/saas/packages/'.$id.'/reapply', ['reason' => 'Not allowed to do this'])->assertForbidden();
            if ($role === 'support') {
                $this->get('/saas/packages')->assertForbidden();
                $this->get('/saas/packages/'.$id)->assertForbidden();
            } else {
                // packages.view stays read-only: the page shows no form that changes anything.
                $this->get('/saas/packages')->assertOk()->assertDontSee('New package');
                $this->get('/saas/packages/'.$id)->assertOk()->assertSee('R 50.00')->assertDontSee('Add a price')->assertDontSee('Save modules')->assertDontSee('Withdraw');
            }
            // The service refuses too, whatever calls it.
            foreach ([
                fn () => $this->packages()->create(['package_key' => 'direct', 'name' => 'Direct', 'trial_days' => 0], $user),
                fn () => $this->packages()->addPrice($id, ['kind' => 'base', 'amount' => '1', 'effective_from' => $this->day(90)], $user),
                fn () => $this->packages()->setModules($id, ['product_modules' => []], $user),
                fn () => $this->packages()->reapply($id, ['reason' => 'Not allowed to do this'], $user),
            ] as $call) {
                try {
                    $call();
                    $this->fail($role.' was allowed');
                } catch (HttpException $e) {
                    $this->assertSame(403, $e->getStatusCode());
                }
            }
        }
        $this->assertSame($before, $this->counts());
        $this->assertEquals($rows, [DB::table('platform_packages')->orderBy('id')->get()->all(), DB::table('platform_prices')->get()->all()]);
    }

    public function test_school_accounts_are_refused(): void
    {
        $id = $this->packageId('core');
        $before = $this->counts();
        foreach ([$this->superAdmin(), User::findOrFail($this->schoolA->ids['tara_user'])] as $user) {
            $this->actingAs($user)->get('/saas/packages/'.$id)->assertForbidden();
            $this->post('/saas/packages', ['package_key' => 'school-made', 'name' => 'School made', 'trial_days' => 0])->assertForbidden();
            $this->post('/saas/packages/'.$id.'/prices', ['kind' => 'base', 'amount' => '1', 'effective_from' => $this->day(1)])->assertForbidden();
            $this->put('/saas/packages/'.$id.'/modules', ['product_modules' => []])->assertForbidden();
        }
        $this->assertSame($before, $this->counts());
    }

    public function test_an_unknown_package_or_another_packages_price_is_not_found(): void
    {
        $owner = $this->staff('owner');
        $core = $this->packageId('core');
        $standard = $this->packageId('standard');
        DB::table('platform_prices')->insert(['package_id' => $standard, 'kind' => 'base', 'amount_cents' => 5000, 'effective_from' => $this->day(30), 'created_at' => now(), 'updated_at' => now()]);
        $priceId = (int) DB::table('platform_prices')->value('id');
        $before = $this->counts();
        $this->actingAsHq($owner)->post('/saas/packages/'.$core.'/prices/'.$priceId.'/withdraw')->assertNotFound();
        $this->get('/saas/packages/987654')->assertNotFound();
        $this->put('/saas/packages/987654/modules', ['product_modules' => []])->assertNotFound();
        $this->post('/saas/packages/987654/prices', ['kind' => 'base', 'amount' => '1', 'effective_from' => $this->day(1)])->assertNotFound();
        $this->assertSame($before, $this->counts());
        $this->assertNull(DB::table('platform_prices')->where('id', $priceId)->value('withdrawn_at'));
    }

    public function test_changing_a_packages_modules_reaches_existing_schools_only_when_reapplied(): void
    {
        $finance = $this->staff('finance');
        $id = $this->packageId('standard');
        app(CurrentSchool::class)->run(2, function (): void {
            $ledger = app(LedgerService::class);
            $cash = $ledger->openAccount(AccountType::CashDesk, 'synthetic:packages:cash', 'ZAR');
            $family = $ledger->openAccount(AccountType::Family, 'synthetic:packages:family', 'ZAR');
            $ledger->post('synthetic:packages:receipt', 'synthetic:packages:receipt', 'Synthetic retained receipt', [
                Posting::debit($cash, Money::ofCents(100)), Posting::credit($family, Money::ofCents(100)),
            ]);
        });
        School::query()->whereKey(2)->update(['package_key' => 'standard']);
        $state = fn (int $school): array => app(CurrentSchool::class)->run($school, function (): array {
            app(ModuleGate::class)->forget();

            return ['fees' => app(ModuleGate::class)->state('fees'), 'writable' => app(ModuleGate::class)->writable('fees'), 'context' => app(ModuleGate::class)->context()];
        });
        $schoolTwo = $state(2);
        $schoolOne = $state(1);
        $this->assertTrue($schoolTwo['fees']['in_package']);
        $rows = [1 => $this->moduleRows(1), 2 => $this->moduleRows(2)];
        $outbox = OutboxEvent::query()->count();

        // Finance leaves Finance out of Standard: a new version, nothing changes for school 2.
        $this->actingAsHq($finance)->put('/saas/packages/'.$id.'/modules', ['product_modules' => ['learning', 'ai_tutor', 'pastoral', 'family', 'activities', 'futures', 'leadership', 'access']])->assertRedirect();
        $this->assertSame(2, (int) DB::table('platform_packages')->where('id', $id)->value('modules_version'));
        $this->assertSame(1, AuditLog::query()->where('action', 'platform_package_modules_changed')->sole()->after['schools_on_earlier_version']);
        $this->assertSame($rows, [1 => $this->moduleRows(1), 2 => $this->moduleRows(2)]);
        $this->assertSame($schoolTwo, $state(2));
        $this->assertSame($schoolOne, $state(1));
        $this->assertSame($outbox, OutboxEvent::query()->count());
        $this->assertContains('fees', $this->packages()->technicalModules('standard', 1));
        $this->assertNotContains('fees', $this->packages()->technicalModules('standard'));
        $this->get('/saas/schools/2')->assertOk()->assertSee('This school has version 1');
        $this->get('/saas/packages/'.$id)->assertOk()->assertSee('One school is on an earlier version')->assertSee('Re-apply to these schools');
        // A school put on the package from now gets the new version.
        $this->assertSame(2, $this->packages()->versionToAssign('standard'));

        // Re-applying is explicit, needs a reason, and is audited per school.
        $this->post('/saas/packages/'.$id.'/reapply', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame($rows, [1 => $this->moduleRows(1), 2 => $this->moduleRows(2)]);
        $this->post('/saas/packages/'.$id.'/reapply', ['reason' => 'Finance moves to an add-on'])->assertRedirect('/saas/packages/'.$id);
        $this->assertSame(['standard', 2], [School::query()->findOrFail(2)->package_key, School::query()->findOrFail(2)->package_version]);
        $audit = AuditLog::query()->where('action', 'platform_package_reapplied')->sole();
        $this->assertSame([2, $finance->id, 1, 2, 'Finance moves to an add-on'], [$audit->school_id, $audit->actor_id, $audit->before['package_version'], $audit->after['package_version'], $audit->after['reason']]);
        $after = $state(2);
        $this->assertFalse($after['fees']['in_package']);
        // Retained fee history stays readable, never writable, as for a package change.
        $this->assertTrue($after['fees']['enabled']);
        $this->assertFalse($after['writable']);
        $this->assertGreaterThan($outbox, OutboxEvent::query()->where('school_id', 2)->count());
        // School 1 (legacy) is untouched.
        $this->assertSame($rows[1], $this->moduleRows(1));
        $this->assertSame($schoolOne, $state(1));
        $this->assertNull(School::query()->findOrFail(1)->package_version);
        // Nothing left to re-apply.
        $this->post('/saas/packages/'.$id.'/reapply', ['reason' => 'Run it once more'])->assertRedirect();
        $this->assertSame(1, AuditLog::query()->where('action', 'platform_package_reapplied')->count());
    }
}

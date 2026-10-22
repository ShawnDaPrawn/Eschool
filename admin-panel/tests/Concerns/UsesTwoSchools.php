<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Platform\School;
use App\Models\Settings;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Database\Seeders\EmailSeeder;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DemoSchool;

/**
 * Two complete schools for the cross-school suite, built once per test
 * class: School A (school 1) and School B (school 2), each a DemoSchool
 * with every kind of record the apps and the panel show. Everything of
 * School B carries the word "Baobab" (names, titles, sign-ins), so a
 * response that contains it to a School A user is a leak.
 *
 * Each test runs inside a transaction that is rolled back, as in
 * UsesDemoSchool. Call setUpTwoSchools() in setUp(), after parent::setUp().
 */
trait UsesTwoSchools
{
    public const SCHOOL_B_ID = 2;

    /** Every School B name, title and sign-in contains it. */
    public const SCHOOL_B_MARKER = 'Baobab';

    private static ?DemoSchool $twoSchoolsA = null;

    private static ?DemoSchool $twoSchoolsB = null;

    protected DemoSchool $schoolA;

    protected DemoSchool $schoolB;

    protected function setUpTwoSchools(): void
    {
        if (self::$twoSchoolsA === null) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->seedLikeInstaller();
            self::$twoSchoolsA = DemoSchool::build();
            School::query()->forceCreate(['id' => self::SCHOOL_B_ID, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
            app(SchoolRoles::class)->seed(self::SCHOOL_B_ID);
            self::$twoSchoolsB = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
                // The school's own settings, so nothing of School A is borrowed.
                $this->seed(EmailSeeder::class);

                return DemoSchool::build(self::SCHOOL_B_MARKER);
            });
            Settings::query()->updateOrCreate(['type' => 'school_name'], ['message' => 'Jacaranda Demo School']);
            app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => Settings::query()->updateOrCreate(['type' => 'school_name'], ['message' => 'Baobab High']));
        }
        $this->schoolA = self::$twoSchoolsA;
        $this->schoolB = self::$twoSchoolsB;

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(fn () => DB::rollBack());
    }

    public static function tearDownAfterClass(): void
    {
        self::$twoSchoolsA = null;
        self::$twoSchoolsB = null;
        RefreshDatabaseState::$migrated = false;

        parent::tearDownAfterClass();
    }
}

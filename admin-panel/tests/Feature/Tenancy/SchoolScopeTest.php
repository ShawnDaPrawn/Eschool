<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\FormField;
use App\Models\Mediums;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\NoCurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * BelongsToSchool and CurrentSchool: queries see the current school's rows
 * only, new rows (one at a time or in bulk) get its id, and with no school
 * chosen isolation never fails open.
 */
class SchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private CurrentSchool $current;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        $this->current = app(CurrentSchool::class);
    }

    public function test_queries_see_the_current_schools_rows_and_new_rows_get_its_id(): void
    {
        $a = Mediums::query()->forceCreate(['name' => 'English']);
        $b = $this->current->run(2, fn () => Mediums::query()->forceCreate(['name' => 'Afrikaans']));

        $this->assertSame(1, (int) $a->school_id);
        $this->assertSame(2, (int) $b->school_id);
        $this->assertSame(['English'], Mediums::query()->pluck('name')->all());
        $this->assertNull(Mediums::query()->find($b->id));
        $this->assertSame(['Afrikaans'], $this->current->run(2, fn () => Mediums::query()->pluck('name')->all()));
        // run() puts the school back.
        $this->assertSame(1, $this->current->id());
        $this->assertEqualsCanonicalizing(['English', 'Afrikaans'], Mediums::acrossSchools()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['English', 'Afrikaans'], $this->current->acrossSchools(fn () => Mediums::query()->pluck('name')->all()));
        // A relation reaches no further than the scope.
        $this->assertSame(2, $b->school->id);
    }

    public function test_bulk_inserts_and_upserts_get_the_current_schools_id(): void
    {
        $this->current->run(2, function () {
            Mediums::query()->insert([['name' => 'isiZulu'], ['name' => 'Sesotho']]);
            Mediums::query()->insertGetId(['name' => 'Setswana']);
            FormField::query()->upsert([['name' => 'Transport', 'type' => 'text', 'for' => 1, 'rank' => 1]], ['id'], ['rank']);
            SchoolTable::query('sections')->insert(['name' => 'Z']);
            SchoolTable::query('streams')->updateOrInsert(['name' => 'Science']);
        });

        $this->assertSame(['isiZulu', 'Sesotho', 'Setswana'], DB::table('mediums')->where('school_id', 2)->orderBy('id')->pluck('name')->all());
        $this->assertSame(1, DB::table('form_fields')->where('school_id', 2)->count());
        $this->assertSame(1, DB::table('sections')->where('school_id', 2)->where('name', 'Z')->count());
        $this->assertSame(1, DB::table('streams')->where('school_id', 2)->where('name', 'Science')->count());
        // SchoolTable reads like the scope.
        $this->assertSame(0, SchoolTable::query('sections')->where('name', 'Z')->count());
    }

    public function test_console_work_without_a_school_reads_across_schools_and_cannot_write(): void
    {
        Mediums::query()->forceCreate(['name' => 'English']);
        $this->current->run(2, fn () => Mediums::query()->forceCreate(['name' => 'Afrikaans']));
        $this->current->forget();

        $this->assertSame(2, Mediums::query()->count());
        $this->expectException(NoCurrentSchool::class);
        Mediums::query()->forceCreate(['name' => 'Xitsonga']);
    }

    public function test_a_signed_in_request_without_a_school_is_refused_and_a_public_one_uses_the_default_school(): void
    {
        Mediums::query()->forceCreate(['name' => 'English']);
        $this->current->run(2, fn () => Mediums::query()->forceCreate(['name' => 'Afrikaans']));
        Route::middleware(['web', 'auth'])->get('/_tenancy/no-context', fn () => Mediums::query()->pluck('name'));
        Route::middleware(['web'])->get('/_tenancy/public', fn () => Mediums::query()->pluck('name'));

        $response = $this->actingAs($this->superAdmin())->get('/_tenancy/no-context');
        $response->assertStatus(500);
        $this->assertInstanceOf(NoCurrentSchool::class, $response->exception);

        $this->app['auth']->forgetGuards();
        $this->get('/_tenancy/public')->assertOk()->assertExactJson(['English']);
        // The test's own school is back once the requests are done.
        $this->assertSame(1, $this->current->id());
    }

    public function test_a_queued_job_runs_in_the_school_it_was_queued_in(): void
    {
        config(['queue.default' => 'database']);
        $this->current->run(2, function () {
            dispatch(function () {
                Cache::put('tenancy-job-school', app(CurrentSchool::class)->idOrNull());
            });
        });
        dispatch(function () {
            Cache::put('tenancy-job-school-1', app(CurrentSchool::class)->idOrNull());
        });
        $this->current->forget();
        Context::flush();

        Artisan::call('queue:work', ['--once' => true, '--stop-when-empty' => true]);
        Artisan::call('queue:work', ['--once' => true, '--stop-when-empty' => true]);

        $this->assertSame(2, Cache::get('tenancy-job-school'));
        $this->assertSame(1, Cache::get('tenancy-job-school-1'));
    }

    public function test_exists_and_unique_rules_hold_within_the_school(): void
    {
        $year = SessionYear::query()->forceCreate(['name' => '2027', 'start_date' => '2027-01-10', 'end_date' => '2027-12-05']);
        $otherYear = $this->current->run(2, fn () => SessionYear::query()->forceCreate(['name' => '2028', 'start_date' => '2028-01-10', 'end_date' => '2028-12-05']));

        $this->assertTrue(Validator::make(['id' => $year->id], ['id' => 'exists:session_years,id'])->passes());
        $this->assertFalse(Validator::make(['id' => $otherYear->id], ['id' => 'exists:session_years,id'])->passes(), "another school's year is not there");
        $this->assertTrue(Validator::make(['name' => '2028'], ['name' => 'unique:session_years,name'])->passes(), 'another school may use the name');
        $this->assertFalse(Validator::make(['name' => '2027'], ['name' => 'unique:session_years,name'])->passes());
        $this->assertFalse($this->current->run(2, fn () => Validator::make(['name' => '2028'], ['name' => 'unique:session_years,name'])->passes()));
    }

    public function test_settings_are_per_school_over_the_platforms(): void
    {
        Settings::query()->updateOrCreate(['type' => 'school_name'], ['message' => 'Jacaranda Primary']);
        DB::table('settings')->insert(['school_id' => null, 'type' => 'school_tagline', 'message' => 'Platform default tagline']);
        $this->current->run(2, function () {
            Settings::query()->create(['type' => 'school_name', 'message' => 'Baobab High']);
            // A platform key is shared, whichever school writes it.
            Settings::query()->updateOrCreate(['type' => 'app_version'], ['message' => '9.9.9']);
        });

        $this->assertSame('Jacaranda Primary', getSettings('school_name')['school_name']);
        $this->assertSame('Baobab High', $this->current->run(2, fn () => schoolSetting('school_name')));
        $this->assertSame('Platform default tagline', $this->current->run(2, fn () => schoolSetting('school_tagline')));
        $this->assertSame('9.9.9', schoolSetting('app_version'));
        $this->assertNull(DB::table('settings')->where('type', 'app_version')->value('school_id'));
        $this->assertSame(['Jacaranda Primary'], Settings::query()->where('type', 'school_name')->pluck('message')->all());
    }

    public function test_the_school_is_in_every_log_line_and_follows_the_spatie_team(): void
    {
        $this->current->set(2);
        $this->assertSame(2, Context::get('school_id'));
        $this->assertSame(2, getPermissionsTeamId());

        $this->current->forget();
        $this->assertNull(Context::get('school_id'));
    }
}

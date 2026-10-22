<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Phase 1 tenancy migrations: each down() undoes its up(), and running
 * them over a single-school install creates school 1 from its settings and
 * gives it every existing row.
 *
 * Schema changes commit at once in MySQL, so this class works on the real
 * test schema (no RefreshDatabase transaction), puts it back as it found it
 * and deletes the rows it adds.
 */
class TenancyMigrationsTest extends TestCase
{
    /** In the order they run. */
    public const MIGRATIONS = [
        '2026_10_23_100000_create_schools_table',
        '2026_10_23_100100_add_school_id_to_school_owned_tables',
        '2026_10_23_100200_backfill_school_id',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_the_migrations_roll_back_and_back_fill_an_existing_school(): void
    {
        $rows = [];
        try {
            $this->down();

            $this->assertFalse(Schema::hasTable('schools'));
            $this->assertFalse(Schema::hasColumn('classes', 'school_id'));
            $this->assertFalse(Schema::hasColumn('students', 'school_id'));
            $this->assertFalse(Schema::hasColumn('idempotency_keys', 'school_id'));
            // Phase 0 columns stay.
            $this->assertTrue(Schema::hasColumn('settings', 'school_id'));

            // A single-school install with its settings and some data.
            DB::table('settings')->whereIn('type', ['school_name', 'time_zone', 'logo1', 'project_id'])->delete();
            foreach (['school_name' => 'Jacaranda Primary', 'time_zone' => 'Africa/Johannesburg', 'logo1' => 'logo/crest.png', 'project_id' => 'fcm-project'] as $type => $message) {
                $rows['settings'][] = DB::table('settings')->insertGetId(['type' => $type, 'message' => $message]);
            }
            $rows['mediums'][] = DB::table('mediums')->insertGetId(['name' => 'Tenancy medium']);
            $rows['session_years'][] = DB::table('session_years')->insertGetId(['name' => 'Tenancy year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-01']);

            $this->up();

            $school = DB::table('schools')->find(1);
            $this->assertSame('Jacaranda Primary', $school->name);
            $this->assertSame('jacaranda-primary', $school->slug);
            $this->assertSame('Africa/Johannesburg', $school->timezone);
            $this->assertSame('logo/crest.png', $school->crest_path);

            $this->assertSame([1], DB::table('mediums')->whereIn('id', $rows['mediums'])->pluck('school_id')->map(fn ($id) => (int) $id)->all());
            $this->assertSame([1], DB::table('session_years')->whereIn('id', $rows['session_years'])->pluck('school_id')->map(fn ($id) => (int) $id)->all());
            $this->assertSame(1, (int) DB::table('settings')->where('type', 'school_name')->value('school_id'));
            // Platform keys stay with no school.
            $this->assertNull(DB::table('settings')->where('type', 'project_id')->value('school_id'));
        } finally {
            foreach ($rows as $table => $ids) {
                DB::table($table)->whereIn('id', $ids)->delete();
            }
            if (! Schema::hasTable('schools')) {
                $this->up();
            }
        }

        foreach (SchoolOwnedTables::withSchoolId() as $table) {
            if (! in_array($table, SchoolOwnedTables::TEAMS, true)) {
                $this->assertTrue(Schema::hasColumn($table, 'school_id'), "{$table}.school_id");
            }
        }
    }

    private function down(): void
    {
        foreach (array_reverse(self::MIGRATIONS) as $name) {
            $this->migration($name)->down();
        }
    }

    private function up(): void
    {
        foreach (self::MIGRATIONS as $name) {
            $this->migration($name)->up();
        }
    }

    private function migration(string $name): object
    {
        return require database_path("migrations/{$name}.php");
    }
}

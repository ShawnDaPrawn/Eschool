<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Services\Academics\TermCalendar;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class SchoolTermsMigrationTest extends TestCase
{
    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_an_existing_calendar_and_its_links_and_permission_grants_survive_retirement(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        $school = DemoSchool::build();
        $id = $school->ids['term'];
        $this->superAdmin()->givePermissionTo('term-edit');
        $permission = DB::table('permissions')->where('name', 'term-edit')->sole();
        $roleGrants = DB::table('role_has_permissions')->where('permission_id', $permission->id)->get()->toArray();
        $directGrants = DB::table('model_has_permissions')->where('permission_id', $permission->id)->get()->toArray();
        $calendar = (array) DB::table('terms')->where('id', $id)->sole();
        $links = [];
        foreach (TermCalendar::LINKS as $table) {
            $record = DB::table($table)->where('school_id', 1)->first();
            if ($record !== null) {
                DB::table($table)->where('id', $record->id)->update(['term_id' => $id]);
                $links[$table] = $record->id;
            }
        }
        $migration = require database_path('migrations/2026_10_23_200000_replace_semesters_with_school_terms.php');
        try {
            $migration->down();
            $this->assertTrue(Schema::hasTable('semesters'));
            $this->assertFalse(Schema::hasTable('terms'));
            $this->assertSame($id, (int) DB::table('semesters')->where('id', $id)->value('id'));
            $this->assertSame('semester-edit', DB::table('permissions')->where('id', $permission->id)->value('name'));
            foreach ($links as $table => $recordId) {
                $this->assertSame($id, (int) DB::table($table)->where('id', $recordId)->value('semester_id'));
            }
            $migration->up();
            $this->assertFalse(Schema::hasTable('semesters'));
            $this->assertTrue(Schema::hasTable('term_templates'));
            $this->assertSame($calendar, (array) DB::table('terms')->where('id', $id)->sole());
            $this->assertSame('term-edit', DB::table('permissions')->where('id', $permission->id)->value('name'));
            $this->assertEquals($roleGrants, DB::table('role_has_permissions')->where('permission_id', $permission->id)->get()->toArray());
            $this->assertEquals($directGrants, DB::table('model_has_permissions')->where('permission_id', $permission->id)->get()->toArray());
            foreach (TermCalendar::LINKS as $table) {
                $this->assertTrue(Schema::hasColumn($table, 'term_id'));
                $this->assertFalse(Schema::hasColumn($table, 'semester_id'));
            }
            foreach ($links as $table => $recordId) {
                $this->assertSame($id, (int) DB::table($table)->where('id', $recordId)->value('term_id'));
            }
            // SQL cannot cascade-delete academic records even if an operator bypasses the service.
            try {
                DB::table('terms')->where('id', $id)->delete();
                $this->fail('A referenced term was physically deleted.');
            } catch (QueryException $error) {
                $this->assertSame('23000', $error->getCode());
            }
        } finally {
            if (Schema::hasTable('semesters')) {
                $migration->up();
            }
        }
    }
}

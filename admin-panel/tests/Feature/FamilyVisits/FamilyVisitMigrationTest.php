<?php

declare(strict_types=1);

namespace Tests\Feature\FamilyVisits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class FamilyVisitMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_visit_tables_can_roll_back_and_recreate_with_school_foreign_keys(): void
    {
        // Roll back later migrations whose tables reference gate passes first,
        // just as the migration runner does, and reapply them afterwards.
        $dependents = [];
        foreach (['2026_11_08_150000_create_emergency_management.php'] as $file) {
            $path = database_path('migrations/'.$file);
            if (is_file($path)) {
                $dependents[] = require $path;
            }
        }
        foreach ($dependents as $dependent) {
            $dependent->down();
        }
        $migration = require database_path('migrations/2026_10_25_241000_create_family_visits.php');
        $tables = ['meeting_slots', 'gate_passes', 'family_visit_rules', 'family_visit_operations', 'family_visit_events'];
        $migration->down();
        foreach ($tables as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $migration->up();
        foreach (array_reverse($dependents) as $dependent) {
            $dependent->up();
        }
        $this->assertTrue(Schema::hasTable('emergency_releases'));
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $foreignKeys = Schema::getForeignKeys($table);
            $this->assertTrue(collect($foreignKeys)->contains(fn ($key) => $key['columns'] === ['school_id'] && $key['foreign_table'] === 'schools'));
        }
    }

    public function test_even_rule_only_audit_history_prevents_destructive_rollback(): void
    {
        DB::table('family_visit_events')->insert(['id' => (string) Str::uuid(), 'school_id' => 1, 'actor_type' => 'user', 'actor_id' => 'synthetic',
            'kind' => 'gate.rules', 'source_id' => (string) Str::uuid(), 'snapshot' => encrypt(['synthetic' => true]), 'created_at' => now(), 'updated_at' => now()]);
        $migration = require database_path('migrations/2026_10_25_241000_create_family_visits.php');
        try {
            $migration->down();
            $this->fail('Retained collection rules audit was deleted.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('retain', $error->getMessage());
            $this->assertTrue(Schema::hasTable('family_visit_events'));
            $this->assertSame(1, DB::table('family_visit_events')->count());
        }
    }
}

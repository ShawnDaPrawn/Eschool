<?php

declare(strict_types=1);

namespace Tests\Feature\Pathways;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PathwayMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_pathway_tables_can_roll_back_and_recreate_with_tenant_foreign_keys(): void
    {
        $migration = require database_path('migrations/2026_10_25_242000_create_pathway_references.php');
        $tables = ['admission_requirements', 'admission_requirement_versions', 'pathway_operations'];
        $migration->down();
        foreach ($tables as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $migration->up();
        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertTrue(collect(Schema::getForeignKeys($table))->contains(fn ($fk) => $fk['columns'] === ['school_id'] && $fk['foreign_table'] === 'schools'));
        }
        $current = collect(Schema::getForeignKeys('admission_requirements'))->firstWhere('name', 'pathway_current_version');
        $this->assertSame(['school_id', 'id', 'current_version_id'], $current['columns']);
        $this->assertSame(['school_id', 'requirement_id', 'id'], $current['foreign_columns']);
    }
}

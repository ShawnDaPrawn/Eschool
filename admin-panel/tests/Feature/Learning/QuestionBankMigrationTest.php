<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuestionBankMigrationTest extends TestCase
{
    public function test_fresh_and_reapplied_question_banks_keep_required_subject_and_grade_foreign_keys(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $migration = require database_path('migrations/2026_11_05_100000_scope_practice_items_to_subject_and_grade.php');
        $this->assertRequiredScope();

        $migration->down();
        $this->assertFalse(Schema::hasTable('practice_item_groups'));
        $this->assertFalse(Schema::hasColumn('practice_items', 'subject_id'));
        $migration->up();

        $this->assertTrue(Schema::hasTable('practice_item_groups'));
        $this->assertRequiredScope();
    }

    private function assertRequiredScope(): void
    {
        $columns = collect(Schema::getColumns('practice_items'))->keyBy('name');
        $keys = collect(Schema::getForeignKeys('practice_items'))
            ->keyBy(fn (array $key): string => implode(',', $key['columns']));

        foreach (['subject_id' => 'subjects', 'grade_level_id' => 'grade_levels'] as $column => $table) {
            $this->assertFalse($columns[$column]['nullable']);
            $this->assertSame($table, $keys[$column]['foreign_table']);
            $this->assertSame(['id'], $keys[$column]['foreign_columns']);
            $this->assertContains(strtolower($keys[$column]['on_delete']), ['restrict', 'no action']);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Platform\LearnerInvitation;
use App\Services\Platform\Tenancy\BelongsToSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

/**
 * Every table is sorted (SchoolOwnedTables), and each one's columns, indexes
 * and model match its decision. A new table fails here until it is sorted:
 * add it to SchoolOwnedTables and to engineering/specs/platform/tenancy.md.
 */
class SchoolOwnedTablesTest extends TestCase
{
    use RefreshDatabase;

    /** School-owned models read across schools on purpose, with the reason. */
    private const UNSCOPED_MODELS = [
        LearnerInvitation::class => 'found by its secret token or code on the public set-up page, before anyone knows the school; created with the school set explicitly',
    ];

    public function test_every_table_is_sorted_exactly_once(): void
    {
        $sorted = [
            ...SchoolOwnedTables::REQUIRED,
            ...SchoolOwnedTables::NULLABLE,
            ...SchoolOwnedTables::TEAMS,
            ...array_keys(SchoolOwnedTables::FOLLOWS_PARENT),
            ...array_keys(SchoolOwnedTables::GLOBAL),
        ];

        $this->assertSame([], array_keys(array_filter(array_count_values($sorted), fn (int $count) => $count > 1)), 'Sorted twice');
        $this->assertSame([], array_values(array_diff($this->tables(), $sorted)), 'Not sorted: add these to SchoolOwnedTables and the tenancy spec');
        $this->assertSame([], array_values(array_diff($sorted, $this->tables())), 'Sorted but not in the database');
    }

    public function test_school_owned_tables_have_a_required_indexed_school_id_with_a_foreign_key(): void
    {
        $columns = $this->schoolIdColumns();
        $indexed = $this->indexedOnSchoolId();
        $foreign = $this->foreignKeysToSchools();

        foreach (SchoolOwnedTables::REQUIRED as $table) {
            $this->assertSame('NO', $columns[$table] ?? null, "{$table}.school_id is missing or nullable");
            $this->assertContains($table, $indexed, "{$table}.school_id has no index");
            $this->assertContains($table, $foreign, "{$table}.school_id has no foreign key to schools");
        }
        foreach (SchoolOwnedTables::NULLABLE as $table) {
            $this->assertSame('YES', $columns[$table] ?? null, "{$table}.school_id is missing or required");
            $this->assertContains($table, $indexed, "{$table}.school_id has no index");
        }
        foreach (array_keys(SchoolOwnedTables::GLOBAL) as $table) {
            if ($table !== 'school_memberships') {
                $this->assertArrayNotHasKey($table, $columns, "{$table} is global but has a school_id");
            }
        }
        $this->assertSame('NO', $columns['school_memberships']);
        $this->assertArrayNotHasKey('users', $columns, 'users belong to schools through memberships');
    }

    public function test_every_model_of_a_school_owned_table_belongs_to_a_school(): void
    {
        $missing = [];
        foreach ($this->models() as $class) {
            /** @var Model $model */
            $model = new $class;
            if (! in_array($model->getTable(), SchoolOwnedTables::REQUIRED, true) || isset(self::UNSCOPED_MODELS[$class])) {
                continue;
            }
            if (! in_array(BelongsToSchool::class, class_uses_recursive($class), true)) {
                $missing[] = $class;
            }
        }

        $this->assertSame([], $missing, 'These models need the BelongsToSchool trait');
    }

    /** @return list<string> */
    private function tables(): array
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_type', 'BASE TABLE')
            ->orderBy('table_name')
            ->selectRaw('table_name AS name')
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    /** @return array<string, string> table => IS_NULLABLE */
    private function schoolIdColumns(): array
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('column_name', 'school_id')
            ->selectRaw('table_name AS name, is_nullable AS nullable')
            ->pluck('nullable', 'name')
            ->all();
    }

    /** @return list<string> tables with an index whose first column is school_id */
    private function indexedOnSchoolId(): array
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('column_name', 'school_id')
            ->where('seq_in_index', 1)
            ->selectRaw('DISTINCT table_name AS name')
            ->pluck('name')
            ->all();
    }

    /** @return list<string> */
    private function foreignKeysToSchools(): array
    {
        return DB::table('information_schema.key_column_usage')
            ->where('table_schema', DB::getDatabaseName())
            ->where('column_name', 'school_id')
            ->where('referenced_table_name', 'schools')
            ->selectRaw('table_name AS name')
            ->pluck('name')
            ->all();
    }

    /** @return list<class-string<Model>> */
    private function models(): array
    {
        $models = [];
        foreach ([...glob(app_path('Models/*.php')), ...glob(app_path('Models/*/*.php'))] as $file) {
            $class = 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], substr($file, strlen(app_path('Models/'))));
            if (class_exists($class) && is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $models[] = $class;
            }
        }

        return $models;
    }
}

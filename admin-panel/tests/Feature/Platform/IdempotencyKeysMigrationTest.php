<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The idempotency_keys migration rolls back and runs again. Not in a
 * transaction: MySQL commits around schema changes, so this leaves the schema
 * exactly as it found it instead.
 */
class IdempotencyKeysMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_10_22_090000_create_idempotency_keys_table.php';

    public function test_the_migration_rolls_back_and_runs_again(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('idempotency_keys'));

        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION, '--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('idempotency_keys'));

        $this->artisan('migrate', ['--path' => self::MIGRATION, '--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('idempotency_keys'));
        $this->assertTrue(Schema::hasColumns('idempotency_keys', [
            'user_id', 'key', 'key_hash', 'method', 'route', 'request_hash', 'response_status', 'response_headers', 'response_body', 'expires_at',
        ]));
    }
}

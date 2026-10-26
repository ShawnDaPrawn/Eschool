<?php

declare(strict_types=1);

namespace Tests\Feature\Classroom;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationMigrationTest extends TestCase
{
    public function test_empty_classroom_authority_roundtrip_preserves_identity_constraints(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $migration = require database_path('migrations/2026_10_26_004000_create_foundation_classroom_authority.php');
        $this->assertTrue(Schema::hasColumn('card_classroom_sessions', 'classroom_lease_id'));
        $migration->down();
        $this->assertFalse(Schema::hasTable('foundation_classroom_leases'));
        $this->assertFalse(Schema::hasColumn('card_classroom_sessions', 'classroom_lease_id'));
        $scan = collect(Schema::getColumns('card_classroom_sessions'))->firstWhere('name', 'scan_id');
        $this->assertFalse($scan['nullable']);
        $migration->up();
        $this->assertTrue(Schema::hasTable('foundation_classroom_groups'));
        $this->assertTrue(Schema::hasColumn('card_classroom_sessions', 'approval_revision'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Consultations;

use App\Models\Consultations\Operation;
use App\Models\Platform\Role;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ConsultationsMigrationTest extends TestCase
{
    public function test_empty_domain_roundtrip_keeps_existing_domains_and_custom_permissions(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        $custom = Role::create(['name' => 'Synthetic custom organiser', 'guard_name' => 'web', 'custom_role' => 1]);
        $custom->givePermissionTo('profile-person-view');
        $before = $custom->permissions()->pluck('name')->all();
        $migration = require database_path('migrations/2026_10_25_190000_create_consultations.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('consultations'));
        $this->assertTrue(Schema::hasTable('messages'));
        $migration->up();
        app(SchoolRoles::class)->seed(1);
        $this->assertTrue(Schema::hasTable('consultation_responses'));
        $this->assertSame($before, $custom->fresh()->permissions()->pluck('name')->all());
        $this->assertTrue(Role::where('name', 'Super Admin')->firstOrFail()->hasPermissionTo('consultations-manage'));
        $this->assertFalse(Role::where('name', 'Teacher')->firstOrFail()->hasPermissionTo('consultations-manage'));
        $this->assertFalse(Role::where('name', 'Facilitator')->firstOrFail()->hasPermissionTo('consultations-manage'));
    }

    public function test_retained_receipts_block_destructive_rollback(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        Operation::create(['actor_id' => $this->superAdmin()->id, 'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'receipt' => ['id' => 1]]);
        $migration = require database_path('migrations/2026_10_25_190000_create_consultations.php');
        try {
            $migration->down();
            $this->fail('Retained receipts were deleted.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Retained consultation', $e->getMessage());
        }
        $this->assertSame(1, Operation::count());
    }
}

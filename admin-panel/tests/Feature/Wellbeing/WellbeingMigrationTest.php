<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Models\Wellbeing\Category;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class WellbeingMigrationTest extends TestCase
{
    public function test_empty_wellbeing_schema_can_roll_back_and_reapply_without_affecting_other_domains(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $migration = require database_path('migrations/2026_10_25_050000_create_wellbeing.php');
        $photos = require database_path('migrations/2026_10_25_170000_create_incident_photos.php');
        $notices = require database_path('migrations/2026_10_25_180000_create_wellbeing_notice_intents.php');
        $reads = require database_path('migrations/2026_10_25_185000_add_wellbeing_notice_reads.php');
        $reads->down();
        $notices->down();
        $photos->down();
        $migration->down();
        $this->assertFalse(Schema::hasTable('incident_reports'));
        $this->assertFalse(Schema::hasTable('support_cases'));
        $this->assertTrue(Schema::hasTable('activity_groups'));
        $this->assertTrue(Schema::hasTable('mastery_records'));
        $migration->up();
        $photos->up();
        $notices->up();
        $reads->up();
        $this->assertTrue(Schema::hasTable('incident_reports'));
        $this->assertTrue(Schema::hasTable('wellbeing_operations'));
        $this->assertFalse(config('modules.wellbeing.default'));
    }

    public function test_retained_wellbeing_records_refuse_destructive_rollback(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        Category::create(['name' => 'Retained category', 'emergency_guidance' => 'Speak to a trusted adult if unsafe.', 'active' => true, 'revision' => 1]);
        $migration = require database_path('migrations/2026_10_25_050000_create_wellbeing.php');
        try {
            $migration->down();
            $this->fail('Retained wellbeing records were deleted.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('Retained wellbeing records', $error->getMessage());
        }
        $this->assertSame(1, Category::count());
        $this->assertTrue(Schema::hasTable('support_case_entries'));
    }
}

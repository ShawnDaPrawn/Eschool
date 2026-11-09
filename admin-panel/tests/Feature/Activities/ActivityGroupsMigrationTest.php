<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityOperation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ActivityGroupsMigrationTest extends TestCase
{
    public function test_empty_schema_can_roll_back_and_reapply_without_removing_other_domains(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $migration = require database_path('migrations/2026_10_25_010000_create_activity_groups.php');
        // Later feature tables may legitimately reference groups. Reverse
        // those migrations first, then restore them after this domain.
        $later = glob(database_path('migrations/*.php')) ?: [];
        $later = array_values(array_filter($later, fn ($path) => basename($path) > '2026_10_25_010000_create_activity_groups.php'));
        sort($later);
        foreach (array_reverse($later) as $path) {
            (require $path)->down();
            if (basename($path) === '2026_11_05_140000_add_activity_skills_to_peer_tutoring.php') {
                $this->assertPeerTopicConstraint(false);
            }
        }
        $migration->down();
        $this->assertFalse(Schema::hasTable('activity_groups'));
        $this->assertTrue(Schema::hasTable('teaching_groups'));
        $this->assertTrue(Schema::hasTable('messages'));
        $migration->up();
        foreach ($later as $path) {
            (require $path)->up();
            if (basename($path) === '2026_11_05_140000_add_activity_skills_to_peer_tutoring.php') {
                $this->assertPeerTopicConstraint(true);
            }
        }
        $this->assertTrue(Schema::hasTable('activity_groups'));
        $this->assertTrue(Schema::hasTable('activity_operations'));
    }

    public function test_retained_operation_history_blocks_destructive_rollback(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        ActivityOperation::create(['actor_id' => $this->superAdmin()->id, 'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'receipt' => ['id' => 1]]);
        $migration = require database_path('migrations/2026_10_25_010000_create_activity_groups.php');
        try {
            $migration->down();
            $this->fail('Rollback deleted retained activity history.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Retained activity', $e->getMessage());
        }
        $this->assertTrue(Schema::hasTable('activity_groups'));
        $this->assertSame(1, ActivityOperation::count());
    }

    private function assertPeerTopicConstraint(bool $nullable): void
    {
        $topic = collect(Schema::getColumns('peer_tutoring_sessions'))->firstWhere('name', 'topic_id');
        $this->assertNotNull($topic);
        $this->assertSame($nullable, $topic['nullable']);
        $foreign = collect(Schema::getForeignKeys('peer_tutoring_sessions'))->firstWhere('columns', ['topic_id']);
        $this->assertNotNull($foreign);
        $this->assertSame('mastery_topics', $foreign['foreign_table']);
        $this->assertSame(['id'], $foreign['foreign_columns']);
    }
}

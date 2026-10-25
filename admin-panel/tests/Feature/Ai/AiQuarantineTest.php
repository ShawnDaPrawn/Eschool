<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Ai\AiFlag;
use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Ai\Quarantine\QuarantineParticipant;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class AiQuarantineTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->set(1);
        PurgeProbe::$calls = [];
        PurgeProbe::$fail = false;
        PurgeProbe::$swap = false;
    }

    private function usage(): AiUsage
    {
        return AiUsage::create(['user_id' => $this->schoolA->ids['tara_user'], 'feature' => 'synthetic_quarantine',
            'provider' => 'synthetic', 'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'synthetic intent'),
            'usage_date' => now()->toDateString(), 'unit' => 'tokens', 'state' => 'reserved', 'reserved_units' => 50, 'reserved_cost_cents' => 2]);
    }

    public function test_flagged_input_is_encrypted_once_and_exact_capture_is_idempotent(): void
    {
        $usage = $this->usage();
        $service = app(AiQuarantine::class);
        $service->capture($usage, 'input', ['self_harm', 'safety'], 'Synthetic private disclosure.');
        $service->capture($usage, 'input', ['self_harm', 'safety'], 'Synthetic private disclosure.');
        $this->assertSame('quarantined', $usage->fresh()->state);
        $this->assertSame(2, AiFlag::count());
        $this->assertSame(1, AiQuarantineSnapshot::count());
        $snapshot = AiQuarantineSnapshot::firstOrFail();
        $this->assertSame(['text' => 'Synthetic private disclosure.'], $snapshot->private_content);
        $this->assertStringNotContainsString('Synthetic private disclosure.', DB::table('ai_quarantine_snapshots')->value('private_content'));
        $this->assertArrayNotHasKey('private_content', $snapshot->toArray());
        $this->assertArrayNotHasKey('content_hash', $snapshot->toArray());
        $this->assertSame(50, $usage->fresh()->reserved_units);
        $this->expectException(AiUnavailable::class);
        $service->capture($usage, 'input', ['safety'], 'Changed content must not replace a receipt.');
    }

    public function test_invalid_flags_and_impersonation_cannot_capture_or_change_usage(): void
    {
        $usage = $this->usage();
        try {
            app(AiQuarantine::class)->capture($usage, 'input', ['safe_code', []], 'Synthetic private text');
            $this->fail('Expected invalid category refusal.');
        } catch (AiUnavailable $error) {
            $this->assertSame('moderation_invalid', $error->reason);
        }
        $this->assertSame(0, AiFlag::count());
        $this->assertSame(0, AiQuarantineSnapshot::count());
        $this->assertSame('reserved', $usage->fresh()->state);
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        try {
            app(AiQuarantine::class)->capture($usage, 'output', ['safety'], 'Impersonated body');
            $this->fail('Expected impersonation refusal.');
        } catch (AiUnavailable $error) {
            $this->assertSame('inference_context_changed', $error->reason);
        } finally {
            app(CurrentSchool::class)->set(1);
        }
    }

    public function test_vision_retains_only_bounded_opaque_server_owned_references(): void
    {
        $payload = ['text' => 'Synthetic handwritten working', 'media' => [
            ['id' => 'synthetic-private-photo-1', 'sha256' => str_repeat('a', 64), 'mime' => 'image/jpeg'],
        ]];
        app(AiQuarantine::class)->capture($this->usage(), 'input', ['safety'], $payload);
        $this->assertSame($payload, AiQuarantineSnapshot::firstOrFail()->private_content);
        $this->expectException(AiUnavailable::class);
        app(AiQuarantine::class)->capture($this->usage(), 'input', ['safety'], [
            ...$payload, 'media' => [['url' => 'https://private-source.invalid/person.jpg']],
        ]);
    }

    public function test_other_school_cannot_capture_into_a_known_usage(): void
    {
        $usage = $this->usage();
        try {
            app(CurrentSchool::class)->run(2, fn () => app(AiQuarantine::class)->capture($usage, 'output', ['safety'], 'Other school'));
            $this->fail('Expected cross-school refusal.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, AiQuarantineSnapshot::count());
        }
        $this->assertSame('reserved', $usage->fresh()->state);
    }

    public function test_expiry_scrubs_only_derived_copies_and_preserves_the_charged_compute_ledger(): void
    {
        $usage = $this->usage();
        app(AiQuarantine::class)->capture($usage, 'output', ['safety'], 'Synthetic flagged output');
        $usage->update(['reserved_units' => 0, 'reserved_cost_cents' => 0, 'used_units' => 42, 'cost_cents' => 2,
            'inference_output' => ['output' => 'Synthetic derived result'], 'inference_sources' => ['approved-curriculum:17']]);
        config(['ai.quarantine.participants.synthetic_quarantine' => PurgeProbe::class, 'ai.inference.enabled' => false]);
        $this->travel(31)->days();
        $this->assertSame(1, app(AiQuarantine::class)->prune());
        $this->assertSame(0, AiFlag::count());
        $this->assertSame(0, AiQuarantineSnapshot::count());
        $fresh = $usage->fresh();
        $this->assertSame(42, $fresh->used_units);
        $this->assertSame(2, $fresh->cost_cents);
        $this->assertNull($fresh->inference_output);
        $this->assertNull($fresh->inference_sources);
        $this->assertNotNull($fresh->quarantine_purged_at);
        $this->assertSame([[(int) $usage->id, (int) $usage->school_id, $usage->operation_id]], PurgeProbe::$calls);
        $this->assertSame(0, app(AiQuarantine::class)->prune());
        $this->expectException(AiUnavailable::class);
        app(AiQuarantine::class)->capture($fresh, 'output', ['safety'], 'Late body after purge');
    }

    public function test_failed_participant_rolls_back_deletion_and_keeps_ledger_intact_for_retry(): void
    {
        $usage = $this->usage();
        app(AiQuarantine::class)->capture($usage, 'output', ['safety'], 'Synthetic flagged output');
        config(['ai.quarantine.participants.synthetic_quarantine' => PurgeProbe::class]);
        PurgeProbe::$fail = true;
        $this->travel(31)->days();
        try {
            app(AiQuarantine::class)->prune();
            $this->fail('Expected participant failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic purge failure', $error->getMessage());
        }
        $this->assertSame(1, AiFlag::count());
        $this->assertSame(1, AiQuarantineSnapshot::count());
        $this->assertNull($usage->fresh()->quarantine_purged_at);
        PurgeProbe::$fail = false;
        $this->assertSame(1, app(AiQuarantine::class)->prune());
    }

    public function test_retention_command_expires_older_code_only_flags_without_deleting_usage(): void
    {
        $usage = $this->usage();
        AiFlag::create(['ai_usage_id' => $usage->id, 'code' => 'legacy_safety', 'created_at' => now()->subDays(31)]);
        $this->artisan('ai:prune-quarantine')->assertSuccessful();
        $this->assertSame(0, AiFlag::count());
        $this->assertSame(1, AiUsage::count());
    }

    public function test_participant_tenant_replacement_rolls_back_and_restores_the_original_context(): void
    {
        $usage = $this->usage();
        app(AiQuarantine::class)->capture($usage, 'output', ['safety'], 'Synthetic flagged output');
        config(['ai.quarantine.participants.synthetic_quarantine' => PurgeProbe::class]);
        PurgeProbe::$swap = true;
        $this->travel(31)->days();
        try {
            app(AiQuarantine::class)->prune();
            $this->fail('Expected participant context refusal.');
        } catch (AiUnavailable $error) {
            $this->assertSame('inference_context_changed', $error->reason);
        }
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(1, AiFlag::count());
        $this->assertSame(1, AiQuarantineSnapshot::count());
        $this->assertNull($usage->fresh()->quarantine_purged_at);
    }
}

class PurgeProbe implements QuarantineParticipant
{
    public static array $calls = [];

    public static bool $fail = false;

    public static bool $swap = false;

    public function purgeForUsage(AiUsage $usage): void
    {
        self::$calls[] = [(int) $usage->id, (int) $usage->school_id, $usage->operation_id];
        if (self::$fail) {
            throw new \RuntimeException('Synthetic purge failure');
        }
        if (self::$swap) {
            app(CurrentSchool::class)->set(2);
        }
    }
}

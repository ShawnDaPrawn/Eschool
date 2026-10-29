<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Students;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The LURITS number is the learner's primary identifier, one per human across
 * schools. Specification: engineering/specs/platform/lurits.md (ADR 0007).
 */
class LuritsNumbersTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function learnerA(): Students
    {
        return Students::findOrFail($this->schoolA->ids['asha']);
    }

    private function learnerB(): Students
    {
        return app(CurrentSchool::class)->run(2, fn () => Students::findOrFail($this->schoolB->ids['asha']));
    }

    public function test_a_recorded_number_becomes_the_primary_identifier_and_is_encrypted(): void
    {
        $learner = $this->learnerA();

        $this->assertSame(LuritsNumbers::RECORDED, app(LuritsNumbers::class)->record($learner, '1234 5678-90', 'school_entry'));

        $this->assertSame(['type' => 'lurits', 'value' => '1234567890'], app(LuritsNumbers::class)->primary($learner));
        $raw = DB::table('learner_identifiers')->where('user_id', $learner->user_id)->first();
        $this->assertStringNotContainsString('1234567890', (string) $raw->lurits_number);
        $this->assertSame(LuritsNumbers::hash('1234567890'), $raw->lurits_hash);
        $this->assertSame('school_entry', $raw->lurits_source);
    }

    public function test_without_a_lurits_number_the_navigateed_number_is_the_temporary_identifier(): void
    {
        $learner = $this->learnerA();
        $number = app(StudentNumbers::class)->assign($learner);

        $this->assertSame(['type' => 'navigateed', 'value' => $number], app(LuritsNumbers::class)->primary($learner));
    }

    public function test_recording_the_same_number_again_changes_nothing(): void
    {
        $learner = $this->learnerA();
        app(LuritsNumbers::class)->record($learner, '1234567890', 'school_entry');

        $this->assertSame(LuritsNumbers::UNCHANGED, app(LuritsNumbers::class)->record($learner, '1234567890', 'sasams_import'));
        $this->assertSame('school_entry', DB::table('learner_identifiers')->where('user_id', $learner->user_id)->value('lurits_source'));
    }

    public function test_a_number_another_person_holds_opens_a_conflict_instead_of_being_written(): void
    {
        $holder = $this->learnerA();
        app(LuritsNumbers::class)->record($holder, '1234567890', 'school_entry');
        $claimant = $this->learnerB();

        $result = app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record($claimant, '1234567890', 'school_entry'));

        $this->assertSame(LuritsNumbers::CONFLICT, $result);
        $this->assertNull(DB::table('learner_identifiers')->where('user_id', $claimant->user_id)->value('lurits_hash'), 'never written to the claimant');
        $this->assertSame((int) $holder->user_id, app(LuritsNumbers::class)->holderUserId('1234567890'), 'the holder keeps it');
        $conflict = DB::table('lurits_conflicts')->first();
        $this->assertSame(2, (int) $conflict->school_id);
        $this->assertSame((int) $claimant->id, (int) $conflict->learner_id);
        $this->assertSame('open', $conflict->status);
    }

    public function test_a_repeated_claim_does_not_duplicate_the_open_conflict(): void
    {
        app(LuritsNumbers::class)->record($this->learnerA(), '1234567890', 'school_entry');
        $claimant = $this->learnerB();
        app(CurrentSchool::class)->run(2, function () use ($claimant) {
            app(LuritsNumbers::class)->record($claimant, '1234567890', 'school_entry');
            app(LuritsNumbers::class)->record($claimant, '1234567890', 'sasams_import');
        });

        $this->assertSame(1, DB::table('lurits_conflicts')->count());
    }

    public function test_a_school_sees_only_its_own_conflicts(): void
    {
        app(LuritsNumbers::class)->record($this->learnerA(), '1234567890', 'school_entry');
        $claimant = $this->learnerB();
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record($claimant, '1234567890', 'school_entry'));

        $this->assertSame(0, SchoolTable::query('lurits_conflicts')->count(), 'the holder school never learns of the claim');
        $this->assertSame(1, app(CurrentSchool::class)->run(2, fn () => SchoolTable::query('lurits_conflicts')->count()));
    }

    public function test_the_number_never_enters_the_audit_log(): void
    {
        app(LuritsNumbers::class)->record($this->learnerA(), '1234567890', 'school_entry');

        $rows = DB::table('audit_log')->where('action', 'identity.lurits_recorded')->get();
        $this->assertCount(1, $rows);
        $this->assertStringNotContainsString('1234567890', json_encode($rows->all()) ?: '');
    }

    public function test_invalid_numbers_and_sources_are_refused(): void
    {
        $learner = $this->learnerA();
        foreach (['', 'abc123', '12'] as $bad) {
            try {
                app(LuritsNumbers::class)->record($learner, $bad, 'school_entry');
                $this->fail("{$bad} was accepted");
            } catch (InvalidArgumentException) {
                $this->assertNull(app(LuritsNumbers::class)->holderUserId($bad));
            }
        }
        $this->expectException(InvalidArgumentException::class);
        app(LuritsNumbers::class)->record($learner, '1234567890', 'guess');
    }

    public function test_matching_finds_the_holder_across_schools(): void
    {
        $claimant = $this->learnerB();
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record($claimant, '9988776655', 'sasams_import'));

        $this->assertSame((int) $claimant->user_id, app(LuritsNumbers::class)->holderUserId('9988 7766 55'));
        $this->assertNull(app(LuritsNumbers::class)->holderUserId('1111111111'));
    }

    public function test_a_learner_of_another_school_cannot_be_read_or_written_from_this_school(): void
    {
        $foreign = $this->learnerB();
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record($foreign, '5566778899', 'school_entry'));

        foreach ([
            fn () => app(LuritsNumbers::class)->record($foreign, '1234567890', 'school_entry'),
            fn () => app(LuritsNumbers::class)->forLearner($foreign),
            fn () => app(LuritsNumbers::class)->primary($foreign),
        ] as $call) {
            try {
                $call();
                $this->fail('School A reached a School B learner');
            } catch (HttpException $e) {
                $this->assertSame(404, $e->getStatusCode());
            }
        }
        $this->assertSame(LuritsNumbers::hash('5566778899'), DB::table('learner_identifiers')->where('user_id', $foreign->user_id)->value('lurits_hash'), 'unchanged');
        $this->assertSame(0, DB::table('lurits_conflicts')->count());
    }
}

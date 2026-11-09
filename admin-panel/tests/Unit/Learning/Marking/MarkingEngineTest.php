<?php

declare(strict_types=1);

namespace Tests\Unit\Learning\Marking;

use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\InferenceGateway;
use App\Services\Learning\Marking\Accuracy\MarkingGate;
use App\Services\Learning\Marking\DisabledMarkingJudgement;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\MarkingEngine;
use App\Services\Learning\Marking\MarkingJudgement;
use App\Services\Learning\Marking\Outcome;
use App\Services\Learning\Marking\Profiles\GenericProfile;
use App\Services\Learning\Marking\Profiles\MathematicsProfile;
use App\Services\Learning\Marking\Profiles\PhysicalSciencesProfile;
use App\Services\Learning\Marking\SubjectProfile;
use App\Services\Learning\Marking\SubjectProfiles;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The rules engine and subject profiles (Action Plan 3.16): each rule, each
 * profile's conventions, the per-mark-point breakdown, the switched-off
 * judgement seam and each profile's learner marking gate.
 * engineering/specs/learning/marking-engine.md.
 */
class MarkingEngineTest extends TestCase
{
    private function point(string $type, ?array $rule, int $units = 100, string $earns = 'Synthetic mark point'): array
    {
        return ['earns' => $earns, 'type' => $type, 'mark_units' => $units, 'rule' => $rule];
    }

    private function value(string $value, string $tolerance = '0', ?string $unit = null): array
    {
        return ['kind' => 'exact_value', 'value' => $value, 'tolerance' => $tolerance, 'unit' => $unit];
    }

    private function mark(array $points, string $answer, ?SubjectProfile $profile = null): array
    {
        return app(MarkingEngine::class)->mark(['number' => '1.1', 'max_units' => array_sum(array_column($points, 'mark_units')), 'points' => $points],
            LearnerAnswer::fromText($answer), $profile ?? new MathematicsProfile);
    }

    /** The status of each point. */
    private function statuses(array $points, string $answer, ?SubjectProfile $profile = null): array
    {
        return array_column($this->mark($points, $answer, $profile)['points'], 'status');
    }

    private function decision(array $point, string $answer, ?SubjectProfile $profile = null): string
    {
        return $this->statuses([$point], $answer, $profile)[0];
    }

    public function test_the_breakdown_gives_each_mark_point_a_decision_rule_reason_and_confidence(): void
    {
        $result = $this->mark([
            $this->point('method', ['kind' => 'expression', 'value' => 'v = d/t'], earns: 'Uses v = d/t'),
            $this->point('answer', $this->value('12.5', '0', 'm/s'), earns: '12,5 m/s'),
            $this->point('explanation', null, 200, 'Speed is a scalar'),
        ], "v = d/t\nv = 100/8 = 12,5 m/s", new PhysicalSciencesProfile);
        $this->assertSame(['engine', 'profile', 'question', 'max_units', 'awarded_units', 'pending_units', 'status', 'confidence', 'points'], array_keys($result));
        $this->assertSame(['rules-v1', 'physical_sciences', '1.1', 400, 200, 200, 'needs_teacher'], array_slice(array_values($result), 0, 7));
        $this->assertSame(0.95, $result['confidence'], 'the lowest confidence of the decided marks');
        $this->assertSame(['index', 'earns', 'type', 'mark_units', 'awarded_units', 'status', 'rule', 'decided_by', 'reason', 'confidence', 'line'], array_keys($result['points'][0]));
        $this->assertSame([1, 'Uses v = d/t', 'method', 100, 100, 'awarded', 'expression', 'rule'], array_slice(array_values($result['points'][0]), 0, 8));
        $this->assertSame([1, 2, null], array_column($result['points'], 'line'), 'lines are 1-based');
        $this->assertSame(['needs_teacher', 'none', 'teacher', 0], [$result['points'][2]['status'], $result['points'][2]['rule'], $result['points'][2]['decided_by'], $result['points'][2]['awarded_units']]);
        foreach ($result['points'] as $row) {
            $this->assertIsFloat($row['confidence']);
            $this->assertGreaterThanOrEqual(0.0, $row['confidence']);
            $this->assertLessThanOrEqual(1.0, $row['confidence']);
            $this->assertNotSame('', $row['reason']);
            $this->assertStringNotContainsString('12.5', $row['reason'], 'reasons never quote the memo answer');
        }
        $learner = MarkingEngine::forLearner($result);
        $this->assertArrayNotHasKey('earns', $learner['points'][0]);
        $this->assertArrayNotHasKey('rule', $learner['points'][0]);
        $this->assertSame($result['awarded_units'], $learner['awarded_units']);

        $complete = $this->mark([$this->point('answer', $this->value('4'))], 'x = 4');
        $this->assertSame(['complete', 100, 0], [$complete['status'], $complete['awarded_units'], $complete['pending_units']]);
        $blank = $this->mark([$this->point('answer', $this->value('4')), $this->point('explanation', null)], " \n ");
        $this->assertSame(['not_awarded', 'not_awarded'], array_column($blank['points'], 'status'), 'a blank answer earns nothing, without judgement');
        $this->assertSame(0.99, $blank['confidence']);
    }

    public function test_exact_values_with_tolerance_on_the_final_line_first(): void
    {
        $point = $this->point('answer', $this->value('12.5', '0.05'));
        foreach (['v = 12,5', 'v = 12.5', '12,54', 'v = 100/8 = 12,46', 'Answer: 12,5', 'The speed is 12,5'] as $answer) {
            $this->assertSame('awarded', $this->decision($point, $answer), $answer);
        }
        foreach (['v = 12,6', 'v = 100/8', 'v = -12,5', 'I do not know'] as $answer) {
            $this->assertSame('not_awarded', $this->decision($point, $answer), $answer);
        }
        $earlier = $this->mark([$point], "v = 12,5\nso v = 13");
        $this->assertSame(['awarded', 0.8, 1], [$earlier['points'][0]['status'], $earlier['points'][0]['confidence'], $earlier['points'][0]['line']], 'an earlier line counts with lower confidence');
        $final = $this->mark([$point], "v = 13\nv = 12,5");
        $this->assertSame([0.95, 2], [$final['points'][0]['confidence'], $final['points'][0]['line']]);
        $this->assertSame(['awarded', 'awarded'], $this->statuses([$this->point('answer', $this->value('2')), $this->point('answer', $this->value('-3'))], 'x = 2 or x = -3'));
        $this->assertSame('awarded', $this->decision($this->point('answer', $this->value('300000000')), 'c = 3 × 10^8'));
    }

    public function test_small_exact_values_keep_their_tolerance(): void
    {
        // PR #455 review: no absolute floating-point floor that swamps small values.
        $point = $this->point('answer', $this->value('0.00000001'));
        foreach ([new MathematicsProfile, new GenericProfile] as $profile) {
            $this->assertSame('not_awarded', $this->decision($point, 'x = 0,0000000109', $profile));
            $this->assertSame('awarded', $this->decision($point, 'x = 0,00000001', $profile));
            $this->assertSame('awarded', $this->decision($point, 'x = 1 × 10^-8', $profile));
        }
    }

    public function test_method_values_count_anywhere_in_the_working(): void
    {
        $point = $this->point('method', $this->value('9.8'));
        $this->assertSame('awarded', $this->decision($point, "F = m g\nF = 2 × 9,8\nF = 19,6 N"));
        $this->assertSame('awarded', $this->decision($point, 'a = (-9,8)'));
        $this->assertSame('not_awarded', $this->decision($point, "F = 2 × 10\nF = 20 N"));
    }

    public function test_units_are_required_in_physical_sciences_and_optional_in_mathematics(): void
    {
        $point = $this->point('answer', $this->value('12.5', '0', 'm/s'));
        $this->assertSame('not_awarded', $this->decision($point, 'v = 12,5', new PhysicalSciencesProfile));
        $this->assertSame('awarded', $this->decision($point, 'v = 12,5', new MathematicsProfile));
        $this->assertSame('awarded', $this->decision($point, 'v = 12,5', new GenericProfile));
        foreach ([new PhysicalSciencesProfile, new MathematicsProfile] as $profile) {
            $this->assertSame('awarded', $this->decision($point, 'v = 12,5 m·s⁻¹', $profile));
            $this->assertSame('awarded', $this->decision($point, 'v = 45 km/h', $profile), 'converted from km/h');
            $this->assertSame('not_awarded', $this->decision($point, 'v = 12,5 m', $profile), 'a wrong unit loses the mark');
            $this->assertSame('not_awarded', $this->decision($point, 'v = 12,5 km/h', $profile), 'the value is checked after conversion');
            $this->assertSame('needs_teacher', $this->decision($point, 'v = 12,5 furlongs', $profile), 'an unreadable unit goes to a teacher');
        }
        $this->assertSame('awarded', $this->decision($this->point('answer', $this->value('2500', '0', 'm')), 'd = 2,5 km', new PhysicalSciencesProfile));
        $this->assertSame('needs_teacher', $this->decision($this->point('answer', $this->value('4', '0', 'blocks')), 'n = 4 bricks'), 'a memo unit marking does not know');
    }

    public function test_units_required_rule(): void
    {
        $point = $this->point('accuracy', ['kind' => 'units_required', 'unit' => 'N']);
        $this->assertSame('awarded', $this->decision($point, "F = 2 × 9,8\nF = 19,6 N"));
        $this->assertSame('awarded', $this->decision($point, 'F = 0,0196 kN'));
        $this->assertSame('awarded', $this->decision($point, 'F = 19,6 kg·m·s⁻²'));
        $this->assertSame('not_awarded', $this->decision($point, 'F = 19,6'));
        $this->assertSame('not_awarded', $this->decision($point, 'F = 19,6 J'));
        $this->assertSame('not_awarded', $this->decision($point, "F = 19,6 N\nF = 19,6"), 'the final answer decides');
        $this->assertSame('not_awarded', $this->decision($point, 'I am not sure'));
        $this->assertSame('needs_teacher', $this->decision($point, 'F = 19,6 newts'));
    }

    public function test_physical_sciences_rounding_convention_and_mathematics_tolerance_only(): void
    {
        $point = $this->point('answer', $this->value('3.2653', '0', 'm'));
        $this->assertSame('awarded', $this->decision($point, 'x = 3,27 m', new PhysicalSciencesProfile), 'rounded to two places');
        $this->assertSame('awarded', $this->decision($point, 'x = 3,265 m', new PhysicalSciencesProfile), 'rounded to three places');
        $this->assertSame('not_awarded', $this->decision($point, 'x = 3,3 m', new PhysicalSciencesProfile), 'fewer than two decimal places');
        $this->assertSame('not_awarded', $this->decision($point, 'x = 3,26 m', new PhysicalSciencesProfile), 'wrongly rounded');
        $this->assertSame('not_awarded', $this->decision($point, 'x = 3,27 m', new MathematicsProfile), 'mathematics keeps to the memo tolerance');
        $this->assertSame('awarded', $this->decision($this->point('answer', $this->value('3.27', '0', 'm')), 'x = 3,2653 m', new PhysicalSciencesProfile), 'more places than the memo, rounding to it');
        $rounded = $this->mark([$point], 'x = 3,27 m', new PhysicalSciencesProfile)['points'][0];
        $this->assertSame(0.85, $rounded['confidence']);
    }

    public function test_physical_sciences_direction(): void
    {
        $point = $this->point('answer', $this->value('12', '0', 'N'), earns: '12 N to the left');
        $ps = new PhysicalSciencesProfile;
        $this->assertSame('awarded', $this->decision($point, 'Fnet = 12 N to the left', $ps));
        $this->assertSame('awarded', $this->decision($point, 'Fnet = 12 N links', $ps), 'Afrikaans');
        $this->assertSame('not_awarded', $this->decision($point, 'Fnet = 12 N to the right', $ps));
        $this->assertSame('needs_teacher', $this->decision($point, 'Fnet = 12 N', $ps), 'no direction: a teacher checks');
        $this->assertSame('needs_teacher', $this->decision($point, 'Fnet = -12 N', $ps), 'a sign may stand for the direction');
        $this->assertSame('awarded', $this->decision($point, 'Fnet = 12 N', new MathematicsProfile), 'mathematics does not check directions');
    }

    public function test_significant_figures(): void
    {
        $point = $this->point('accuracy', ['kind' => 'significant_figures', 'value' => '3', 'figures' => 3]);
        foreach (['x = 0,00450', 'x = 12,5 m', 'x = 3,00 × 10^8', 'x = 1,20'] as $answer) {
            $this->assertSame('awarded', $this->decision($point, $answer), $answer);
        }
        foreach (['x = 12,50', 'x = 12', 'x = 0,0045', 'no value'] as $answer) {
            $this->assertSame('not_awarded', $this->decision($point, $answer), $answer);
        }
        $ambiguous = $this->mark([$point], 'x = 1200')['points'][0];
        $this->assertSame(['awarded', 0.6], [$ambiguous['status'], $ambiguous['confidence']], 'trailing zeros are ambiguous');
    }

    public function test_keywords(): void
    {
        $point = $this->point('answer', ['kind' => 'keywords', 'value' => 'oxygen/O2, catalyst', 'terms' => [['oxygen', 'O2'], ['catalyst']]]);
        $this->assertSame('awarded', $this->decision($point, 'Oxygen is released and MnO2 acts as a Catalyst.'));
        $this->assertSame('awarded', $this->decision($point, "O2 forms;\nthe catalysts speed it up"), 'alternatives and plurals');
        $this->assertSame('not_awarded', $this->decision($point, 'Oxygen is released.'));
        $this->assertSame('not_awarded', $this->decision($point, 'Oxygenation with a catalystic thing'), 'whole words only');
        $this->assertStringContainsString('1 of the 2', $this->mark([$point], 'Oxygen is released.')['points'][0]['reason']);
        $this->assertSame('awarded', $this->decision($this->point('answer', ['kind' => 'keywords', 'value' => 'suurstof', 'terms' => [['suurstof']]]), 'Die gas is SUURSTOF.'));
    }

    public function test_mathematics_accepts_equivalent_forms_and_the_generic_profile_only_the_written_form(): void
    {
        $point = $this->point('answer', ['kind' => 'expression', 'value' => '2x + 6']);
        $this->assertSame('awarded', $this->decision($point, 'y = 2(x + 3)', new MathematicsProfile));
        $this->assertSame('awarded', $this->decision($point, '= 6 + 2x', new MathematicsProfile));
        $this->assertSame('not_awarded', $this->decision($point, 'y = 2(x + 2)', new MathematicsProfile));
        $this->assertSame('not_awarded', $this->decision($point, 'y = 2(x + 3)', new GenericProfile));
        $this->assertSame('awarded', $this->decision($point, 'y = 2 x + 6', new GenericProfile), 'spaces aside');
        $form = $this->point('answer', ['kind' => 'expression', 'value' => '2(x + 3)'], earns: 'Factorises fully');
        $this->assertSame('awarded', $this->decision($form, '2(x+3)', new MathematicsProfile));
        $this->assertSame('needs_teacher', $this->decision($form, '2x + 6', new MathematicsProfile), 'an equivalent form when the question asks for one form');
        $equation = $this->point('answer', ['kind' => 'expression', 'value' => 'y = 2x + 3']);
        $this->assertSame('awarded', $this->decision($equation, 'y - 3 = 2x', new MathematicsProfile));
        $this->assertSame('not_awarded', $this->decision($equation, 'y = 3x + 2', new MathematicsProfile));
        $this->assertSame('needs_teacher', $this->decision($this->point('answer', ['kind' => 'expression', 'value' => '2x +']), 'y = 2x'), 'an unreadable memo expression');
    }

    public function test_written_forms_never_join_numbers(): void
    {
        // PR #455 review: 2*3 and 23 are different answers.
        $point = $this->point('answer', ['kind' => 'expression', 'value' => '2*3']);
        foreach ([new MathematicsProfile, new PhysicalSciencesProfile, new GenericProfile] as $profile) {
            $this->assertSame('not_awarded', $this->decision($point, 'x = 23', $profile), $profile->key());
            $this->assertSame('awarded', $this->decision($point, 'x = 2 × 3', $profile), $profile->key());
        }
        $this->assertSame('awarded', $this->decision($point, 'x = 6', new MathematicsProfile), 'equal by value');
        $this->assertSame('not_awarded', $this->decision($point, 'x = 6', new GenericProfile), 'generic keeps the written form');
        $this->assertSame('awarded', $this->decision($this->point('answer', ['kind' => 'expression', 'value' => '2*x']), 'y = 2x', new GenericProfile), 'implicit multiplication');
        $this->assertSame('not_awarded', $this->decision($this->point('method', ['kind' => 'expression', 'value' => 'v = 2*3']), 'v = 23', new GenericProfile));
    }

    public function test_formula_first_needs_a_real_calculation_not_the_formulas_own_numbers(): void
    {
        // PR #455 review: coefficients and powers in a formula are not a calculation.
        $ps = new PhysicalSciencesProfile;
        $point = [$this->point('method', ['kind' => 'expression', 'value' => 'vf^2 = vi^2 + 2 a Δx'])];
        $this->assertSame(['not_awarded'], $this->statuses($point, 'vf^2 = vi^2 + 2 a Δx', $ps));
        $this->assertSame(['not_awarded'], $this->statuses($point, 'vf² = vi² + 2aΔx
so vf is the final velocity', $ps));
        $this->assertSame(['awarded'], $this->statuses($point, 'vf^2 = vi^2 + 2 a Δx
vf^2 = 0^2 + 2(9,8)(5)', $ps), 'values substituted');
        $this->assertSame(['awarded'], $this->statuses($point, 'vf^2 = vi^2 + 2 a Δx
vf = 9,9 m/s', $ps), 'a numeric result');
        $this->assertSame(['awarded'], $this->statuses($point, 'vf^2 = vi^2 + 2 a Δx
Answer: 9,9 m/s down', $ps), 'a stated answer');
        $energy = [$this->point('method', ['kind' => 'expression', 'value' => 'Ek = 0,5 m v^2'])];
        $this->assertSame(['not_awarded'], $this->statuses($energy, 'Ek = 0,5 m v^2', $ps));
        $this->assertSame(['awarded'], $this->statuses($energy, 'Ek = 0,5 m v^2
Ek = 0,5 × 2 × 3^2 = 9 J', $ps));
        $this->assertSame(['awarded'], $this->statuses($energy, 'Ek = 0,5 m v^2
Ek = sqrt(81) J', $ps), 'functions are part of a calculation');
    }

    public function test_physical_sciences_formula_first(): void
    {
        $points = [
            $this->point('method', ['kind' => 'expression', 'value' => 'Fnet = m a'], earns: 'Fnet = ma'),
            $this->point('method', $this->value('2')),
            $this->point('answer', $this->value('19.6', '0', 'N')),
        ];
        $ps = new PhysicalSciencesProfile;
        $this->assertSame(['awarded', 'awarded', 'awarded'], $this->statuses($points, "Fnet = ma\nFnet = 2 × 9,8\nFnet = 19,6 N", $ps));
        $this->assertSame(['awarded', 'awarded', 'awarded'], $this->statuses($points, "a = Fnet/m\nFnet = 2 × 9,8 = 19,6 N", $ps), 'a rearranged formula');
        $this->assertSame(['not_awarded', 'not_awarded', 'not_awarded'], $this->statuses($points, 'Fnet = ma', $ps), 'a formula mark needs a calculation');
        $this->assertSame(['not_awarded', 'awarded', 'needs_teacher'], $this->statuses($points, "Fnet = m/a\nFnet = 2 × 9,8 = 19,6 N", $ps), 'a different formula sends the calculation to a teacher');
        $this->assertSame(['not_awarded', 'awarded', 'awarded'], $this->statuses($points, "Fnet = 2 × 9,8\nFnet = 19,6 N", $ps), 'no formula written: the calculation still counts');
        $this->assertSame(['not_awarded', 'awarded', 'awarded'], $this->statuses($points, "Fnet = m/a\nFnet = 2 × 9,8 = 19,6 N", new MathematicsProfile), 'formula first is a Physical Sciences convention');
        $this->assertSame(['awarded'], $this->statuses([$this->point('method', ['kind' => 'expression', 'value' => 'Ep = m g h'])], "Ep = mgh\nEp = 2 × 9,8 × 3", $ps));
    }

    public function test_consistent_accuracy_follows_through_from_the_learners_own_earlier_answer(): void
    {
        $points = [
            $this->point('answer', $this->value('12.5', '0', 'm/s'), earns: 'v = 12,5 m/s'),
            $this->point('consistent_accuracy', $this->value('25', '0', 'm/s'), earns: 'CA: doubles v'),
        ];
        $right = $this->mark($points, "v = 100/8 = 12,5 m/s\n2v = 2 × 12,5 = 25 m/s");
        $this->assertSame(['awarded', 'awarded'], array_column($right['points'], 'status'));
        $this->assertSame('rule', $right['points'][1]['decided_by']);

        $carried = $this->mark($points, "v = 100/8 = 12 m/s\n2v = 2 × 12 = 24 m/s");
        $this->assertSame(['not_awarded', 'awarded'], array_column($carried['points'], 'status'));
        $this->assertSame(['follow_through', 0.7, 2], [$carried['points'][1]['decided_by'], $carried['points'][1]['confidence'], $carried['points'][1]['line']]);
        $this->assertSame(['not_awarded', 'awarded'], $this->statuses($points, "v = 100/8 = 12 m/s\n2v = 2 × 12 = 24 m/s", new PhysicalSciencesProfile));

        $this->assertSame(['not_awarded', 'needs_teacher'], $this->statuses($points, "v = 100/8 = 12 m/s\n2v = 2 × 12 = 23 m/s"), 'wrong arithmetic: a teacher checks');
        $this->assertSame(['not_awarded', 'needs_teacher'], $this->statuses($points, "v = 100/8 = 12 m/s\n2v = 2 × 13 = 26 m/s"), 'not from the earlier answer');
        $this->assertSame(['not_awarded', 'not_awarded'], $this->statuses($points, "v = 100/8 = 12 m/s\n2v = 2 × 12 = 24", new PhysicalSciencesProfile), 'follow-through still needs the unit');
        $this->assertSame(['not_awarded', 'needs_teacher'], $this->statuses($points, "v = 100/8 = 12 m/s\n2v = 2 × 12 = 24 m/s", new GenericProfile), 'other subjects leave follow-through to a teacher');
        $this->assertSame(['awarded', 'not_awarded'], $this->statuses($points, "v = 100/8 = 12,5 m/s\n2v = 2 × 12,5 = 24 m/s"), 'no earlier mistake: nothing to follow through');
    }

    public function test_method_and_explanation_marks_go_to_a_teacher_and_the_ai_seam_is_never_called(): void
    {
        $this->mock(AiGateway::class)->shouldNotReceive('runFeature', 'complete', 'vision', 'embed');
        $this->mock(InferenceGateway::class)->shouldNotReceive('run', 'runFeature');
        $this->assertInstanceOf(DisabledMarkingJudgement::class, app(MarkingJudgement::class), 'judgement is switched off by default');
        $points = [
            $this->point('method', null, earns: 'Shows the working'),
            $this->point('explanation', null, earns: 'Explains why speed is a scalar'),
            $this->point('explanation', ['kind' => 'keywords', 'value' => 'magnitude', 'terms' => [['magnitude']]], earns: 'Magnitude only'),
            $this->point('answer', null, earns: 'States the speed'),
        ];
        foreach ([new MathematicsProfile, new PhysicalSciencesProfile, new GenericProfile] as $profile) {
            $result = $this->mark($points, "Speed has magnitude only, no direction.\nv = 12,5", $profile);
            $this->assertSame(array_fill(0, 4, 'needs_teacher'), array_column($result['points'], 'status'));
            $this->assertSame(array_fill(0, 4, 'teacher'), array_column($result['points'], 'decided_by'));
            $this->assertSame([0, 400, null], [$result['awarded_units'], $result['pending_units'], $result['confidence']]);
            foreach ($result['points'] as $row) {
                $this->assertLessThanOrEqual(0.2, $row['confidence'], 'needs a teacher, with low confidence');
            }
            $this->assertStringContainsString('1 of the 1 terms', $result['points'][2]['reason'], 'keywords are evidence for the teacher, never an award');
        }
    }

    public function test_the_engine_never_lets_judgement_award_or_refuse_a_mark_on_its_own(): void
    {
        $this->app->bind(MarkingJudgement::class, fn () => new class implements MarkingJudgement
        {
            public function judge(array $point, LearnerAnswer $answer, SubjectProfile $profile, array $evidence): Outcome
            {
                return $point['type'] === 'method' ? Outcome::awarded('A model said yes.', 0.99) : Outcome::notAwarded('A model said no.', 0.99);
            }
        });
        $result = $this->mark([$this->point('method', null), $this->point('explanation', null)], 'Some working');
        $this->assertSame(['needs_teacher', 'needs_teacher'], array_column($result['points'], 'status'));
        $this->assertSame([0, 200], [$result['awarded_units'], $result['pending_units']]);
        $this->assertLessThanOrEqual(0.4, $result['points'][0]['confidence']);
    }

    public function test_the_marking_code_has_no_route_to_a_model(): void
    {
        foreach (File::allFiles(app_path('Services/Learning/Marking')) as $file) {
            $this->assertStringNotContainsString('App\\Services\\Ai', $file->getContents(), $file->getFilename().' must not call the AI gateway while judgement is off');
            $this->assertStringNotContainsString('Http::', $file->getContents(), $file->getFilename());
        }
    }

    public function test_learner_marking_follows_the_accuracy_gate_for_each_profile(): void
    {
        // The gate itself is tested in MarkingAccuracyGateTest; each profile asks it about its own key only.
        $gate = $this->mock(MarkingGate::class);
        $gate->shouldReceive('open')->with('mathematics')->andReturn(true);
        $gate->shouldReceive('open')->with('physical_sciences')->andReturn(false);
        $gate->shouldReceive('open')->with('generic')->andReturn(false);
        $this->assertTrue((new MathematicsProfile)->learnerMarkingEnabled());
        $this->assertFalse((new PhysicalSciencesProfile)->learnerMarkingEnabled());
        $this->assertFalse((new GenericProfile)->learnerMarkingEnabled());
        $this->assertSame(['mathematics-v1', 'physical_sciences-v1', 'generic-v1'], [(new MathematicsProfile)->version(), (new PhysicalSciencesProfile)->version(), (new GenericProfile)->version()]);
    }

    public function test_subjects_choose_their_profile_by_name(): void
    {
        foreach (['Mathematics' => 'mathematics', 'Maths' => 'mathematics', 'WISKUNDE' => 'mathematics', 'Technical Mathematics' => 'mathematics',
            'Physical Sciences' => 'physical_sciences', 'Physical Science' => 'physical_sciences', 'Fisiese Wetenskappe' => 'physical_sciences',
            'Mathematical Literacy' => 'generic', 'Wiskundige Geletterdheid' => 'generic', 'Life Sciences' => 'generic', 'Natural Sciences' => 'generic', 'English Home Language' => 'generic'] as $name => $key) {
            $this->assertSame($key, SubjectProfiles::match($name)->key(), $name);
        }
        $this->assertSame('generic', SubjectProfiles::key('anything')->key());
        $this->assertNotEmpty((new PhysicalSciencesProfile)->conventions());
        $this->assertSame('symbols', (new PhysicalSciencesProfile)->identifiers());
        $this->assertSame('letters', (new MathematicsProfile)->identifiers());
    }

    public function test_an_unknown_rule_kind_goes_to_a_teacher(): void
    {
        $this->assertSame('needs_teacher', $this->decision($this->point('answer', ['kind' => 'range', 'value' => '1-2']), 'x = 1,5'));
    }
}

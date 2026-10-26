<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Pathways\PathwayScore;
use PHPUnit\Framework\TestCase;

class PathwayScoreTest extends TestCase
{
    private function rules(): array
    {
        return ['subjects' => [['subject_id' => 1, 'minimum_bp' => 4000, 'counts_for_score' => true],
            ['subject_id' => 2, 'minimum_bp' => null, 'counts_for_score' => true], ['subject_id' => 3, 'minimum_bp' => null, 'counts_for_score' => false]],
            'bands' => [['minimum_bp' => 8000, 'points' => 7], ['minimum_bp' => 4000, 'points' => 3], ['minimum_bp' => 0, 'points' => 0]], 'best_n' => 1, 'minimum_score' => 3];
    }

    public function test_integer_boundaries_best_subjects_and_explicit_exclusion(): void
    {
        $score = (new PathwayScore)->calculate($this->rules(), [['subject_id' => 1, 'percentage_bp' => 4000], ['subject_id' => 2, 'percentage_bp' => 7999], ['subject_id' => 3, 'percentage_bp' => 10000]]);
        $this->assertSame(3, $score['projected_score']);
        $this->assertSame([1], $score['counted_subject_ids']);
        $this->assertNull($score['subjects'][2]['points']);
        $score = (new PathwayScore)->calculate($this->rules(), [['subject_id' => 1, 'percentage_bp' => 3999], ['subject_id' => 2, 'percentage_bp' => 8000]]);
        $this->assertSame(7, $score['projected_score']);
        $this->assertSame('below_published_minimum', $score['status']);
        $this->assertSame([1], $score['below_minimum_subject_ids']);
    }

    public function test_unknown_candidate_cannot_be_treated_as_zero_or_replaced_with_a_known_best_score(): void
    {
        $score = (new PathwayScore)->calculate($this->rules(), [['subject_id' => 1, 'percentage_bp' => 8000]]);
        $this->assertNull($score['projected_score']);
        $this->assertSame(7, $score['known_subtotal']);
        $this->assertSame([2], $score['missing_subject_ids']);
        $this->assertSame('unknown', $score['status']);
        $this->assertNull($score['subjects'][1]['percentage_bp']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Learning\PracticeReview;
use App\Services\Learning\DailyPractice;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class PracticeQuestionTypesRollbackTest extends TestCase
{
    use UsesPracticeScenario;

    public function test_rollback_refuses_to_erase_automatically_marked_step_results(): void
    {
        $this->setUpPracticeScenario();
        $this->approve(['definition' => ['kind' => 'steps', 'prompt' => 'Work in two steps.', 'explanation' => 'Add, then double.',
            'options' => [], 'answers' => [], 'steps' => [
                ['kind' => 'number', 'prompt' => 'One plus two?', 'options' => [], 'answers' => ['3']],
                ['kind' => 'number', 'prompt' => 'Double that?', 'options' => [], 'answers' => ['6']],
            ]]]);
        $set = $this->issue();
        $result = app(DailyPractice::class)->review($this->actor('asha'), $this->schoolA->ids['asha'], $set['id'], $set['items'][0]['id'],
            ['operation_id' => (string) Str::uuid(), 'parts' => ['s1' => '3', 's2' => '5'], 'rating' => 3]);
        $this->assertSame(['marked', 'automatic', 1, 2, ['s1' => true, 's2' => false]],
            [$result['state'], $result['marked_how'], $result['achieved_units'], $result['maximum_units'], $result['parts']]);

        $migration = require database_path('migrations/2026_11_07_100000_add_practice_question_types.php');
        $refused = false;
        try {
            $migration->down();
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('export them before rolling back', $error->getMessage());
            $refused = true;
        }
        $this->assertTrue($refused, 'Rollback must retain the recorded marks for each step, including automatic marks.');
        $this->assertTrue(Schema::hasColumn('practice_reviews', 'parts'));
        $this->assertSame(['s1' => true, 's2' => false], PracticeReview::sole()->parts);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamQuestionOption;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class OnlineExamMarkupSecurityTest extends TestCase
{
    use UsesTwoSchools;

    private const EQUATION = '<p>Solve <span class="math-tex">\\(x+1=2\\)</span></p>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // Historical encoded markup is untrusted even if the editor accepted it.
        $html = safe_htmlspecialchars(self::EQUATION.'<img src=x onerror=alert(1)><script>alert(1)</script>');
        OnlineExamQuestion::whereKey($this->schoolA->ids['question'])->update(['question_type' => 1, 'question' => $html]);
        OnlineExamQuestionOption::where('question_id', $this->schoolA->ids['question'])->update(['option' => $html]);
    }

    public function test_panel_questions_options_answers_and_editor_values_are_inert(): void
    {
        $row = $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))
            ->getJson('/online-exam-question/1?session_year_id='.$this->schoolA->ids['session_year'])
            ->assertOk()->json('rows.0');

        $this->assertSame(self::EQUATION, $row['question_row']);
        $this->assertSame(self::EQUATION, $row['options'][0]['option_row']);
        foreach ([$row['question'], $row['options'][0]['option'], $row['answers'][0]['answer']] as $html) {
            $this->assertStringContainsString(self::EQUATION, $html);
            $this->assertStringNotContainsString('<img', $html);
            $this->assertStringNotContainsString('<script', $html);
        }
    }

    public function test_learner_api_returns_safe_equations_without_exposing_answers(): void
    {
        Sanctum::actingAs(User::findOrFail($this->schoolA->ids['asha_user']));
        $row = $this->getJson('/api/student/get-online-exam-questions?'.http_build_query([
            'exam_id' => $this->schoolA->ids['online_exam'], 'exam_key' => '1234',
        ]))->assertOk()->assertJson(['error' => false])->json('data.0');

        $this->assertSame(self::EQUATION, $row['question']);
        $this->assertSame(self::EQUATION, $row['options'][0]['option']);
        $this->assertSame([['id' => 0, 'option_id' => 0, 'answer' => '']], $row['answers']);
    }

    public function test_assigned_question_preview_contains_only_safe_editor_markup(): void
    {
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']));
        // This legacy controller reads the PHP query superglobal directly.
        $previousQuery = $_GET;
        $_GET['id'] = $this->schoolA->ids['online_exam'];
        try {
            $response = $this->get('/get-exam-question-index?id='.$this->schoolA->ids['online_exam'])->assertOk();
        } finally {
            $_GET = $previousQuery;
        }

        $document = new DOMDocument;
        @$document->loadHTML($response->getContent());
        $preview = (new DOMXPath($document))->query('//textarea[@name="qc'.$this->schoolA->ids['question'].'"]');
        $this->assertSame(1, $preview->length);
        $this->assertSame(self::EQUATION, $preview->item(0)->textContent);
    }

    public function test_another_schools_exam_is_not_returned(): void
    {
        Sanctum::actingAs(User::findOrFail($this->schoolA->ids['asha_user']));
        $this->getJson('/api/student/get-online-exam-questions?'.http_build_query([
            'exam_id' => $this->schoolB->ids['online_exam'], 'exam_key' => '1234',
        ]))->assertOk()->assertJson(['error' => true]);
    }

    public function test_parent_role_cannot_read_the_learner_exam_endpoint(): void
    {
        Sanctum::actingAs(User::findOrFail($this->schoolA->ids['farid_user']));
        $this->getJson('/api/student/get-online-exam-questions?exam_id='.$this->schoolA->ids['online_exam'].'&exam_key=1234')
            ->assertForbidden();
    }

    public function test_a_learner_cannot_read_another_class_exam(): void
    {
        Sanctum::actingAs(User::findOrFail($this->schoolA->ids['cara_user']));
        $this->getJson('/api/student/get-online-exam-questions?exam_id='.$this->schoolA->ids['online_exam'].'&exam_key=1234')
            ->assertOk()->assertJson(['error' => true]);
    }
}

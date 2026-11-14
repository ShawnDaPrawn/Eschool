<?php

declare(strict_types=1);

namespace Tests\Feature\SchoolBusiness;

use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SchoolBusiness\ReenrolmentContract;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ReenrolmentPanelTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private int $next;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        SessionYear::whereKey($this->schoolA->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->next = SessionYear::create(['name' => 'Next contract year', 'start_date' => '2027-01-15', 'end_date' => '2027-12-10'])->id;
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'reenrolment'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
    }

    private function pins(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
    }

    private function publish(): int
    {
        $response = $this->actingAs($this->admin)->postJson(route('reenrolment-panel.publish'), $this->pins() + [
            'session_year_id' => $this->next, 'title' => 'Next year family contract', 'body' => 'Exact immutable contract wording <script>not executable</script>.',
            'acceptance_rule' => 'any_guardian', 'respond_by' => '2026-09-30', 'confirm_publication' => 1,
        ])->assertOk()->assertJsonPath('error', false);

        return $response->json('data.id');
    }

    public function test_staff_publishes_reads_exact_wording_and_withdraws_without_changing_year(): void
    {
        $this->actingAs($this->admin)->get(route('reenrolment-panel.index'))->assertOk()->assertSee('No re-enrolment contracts yet');
        $this->get(route('reenrolment-panel.create'))->assertOk()->assertSee('Next contract year')->assertSee('Full contract wording');
        $id = $this->publish();
        $this->get(route('reenrolment-panel.show', ['contract' => $id, 'view' => 'wording']))->assertOk()->assertSee('Exact immutable contract wording')->assertDontSee('<script>not executable</script>', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('reenrolment-panel.show', ['contract' => $id, 'view' => 'outstanding']))->assertOk()->assertSee('Send a reminder batch');
        $this->get(route('reenrolment-panel.show', ['contract' => $id, 'view' => 'acceptances']))->assertOk()->assertSee('No acceptances have been recorded');
        $this->postJson(route('reenrolment-panel.withdraw', $id), $this->pins() + ['revision' => 1, 'confirm_withdrawal' => 1])->assertOk()->assertJsonPath('data.state', 'withdrawn');
        $this->assertSame($this->schoolA->ids['session_year'], (int) schoolSetting('session_year'));
    }

    public function test_role_school_and_stale_account_cannot_read_or_mutate_contract(): void
    {
        $id = $this->publish();
        $this->postJson(route('reenrolment-panel.remind', $id), array_replace($this->pins(), ['expected_account_id' => $this->schoolA->ids['tara_user'], 'confirm_reminders' => 1]))->assertForbidden();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get(route('reenrolment-panel.show', $id))->assertForbidden()->assertDontSee('Exact immutable');
        $foreign = app(CurrentSchool::class)->run(2, fn () => ReenrolmentContract::forceCreate(['session_year_id' => $this->schoolB->ids['session_year'], 'version' => 1, 'title' => 'Foreign private contract', 'body' => 'Foreign private wording', 'text_hash' => str_repeat('a', 64), 'acceptance_rule' => 'any_guardian', 'respond_by' => '2026-09-30', 'state' => 'open', 'revision' => 1, 'published_by' => $this->schoolB->ids['tara_user'], 'published_at' => now()]));
        $this->actingAs($this->admin)->get(route('reenrolment-panel.show', $foreign->id))->assertNotFound()->assertDontSee('Foreign private');
    }

    public function test_confirmation_is_required_and_historical_year_cannot_publish(): void
    {
        $this->actingAs($this->admin)->postJson(route('reenrolment-panel.publish'), $this->pins())->assertUnprocessable()->assertJsonValidationErrors(['confirm_publication', 'body']);
        $old = SessionYear::create(['name' => 'Historical year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->withSession(['session_year' => $old->id])->postJson(route('reenrolment-panel.publish'), $this->pins())->assertStatus(423);
        $this->assertSame(0, ReenrolmentContract::count());
    }
}

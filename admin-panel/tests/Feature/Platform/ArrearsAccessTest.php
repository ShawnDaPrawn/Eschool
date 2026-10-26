<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\GuardianLink;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\ApprovalService;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ArrearsAccessTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        config(['identity.approval_enforced' => true]);
        GuardianLink::query()->updateOrCreate([
            'learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user'],
        ], ['relationship' => 'father', 'status' => 'active', 'can_approve' => true]);
        DB::table('users')->where('id', $this->schoolA->ids['farid_user'])->update(['email_verified_at' => now()]);
        app(ApprovalService::class)->decide(User::findOrFail($this->schoolA->ids['farid_user']),
            Students::findOrFail($this->schoolA->ids['asha']), 'approve', config('identity.consent_version'));
        Settings::query()->updateOrCreate(['type' => 'compulsory_fee_payment_mode'], ['message' => '1']);
        SessionYear::query()->whereKey($this->schoolA->ids['session_year'])->update([
            'include_fee_installments' => 0, 'fee_due_date' => now()->subMonth()->toDateString(),
            'free_app_use_date' => now()->subMonths(2)->toDateString(),
        ]);
        DB::table('fees_paids')->where('school_id', 1)->where('student_id', $this->schoolA->ids['asha'])->update(['is_fully_paid' => 0]);
    }

    public function test_an_unpaid_approved_learner_keeps_home_marks_and_reports(): void
    {
        $this->getJson('/api/settings?type=app_settings')->assertOk()
            ->assertJsonPath('data.compulsory_fee_payment_mode', '0');
        $this->withToken($this->schoolA->tokens['asha'])
            ->getJson('/api/student/get-profile-data')->assertOk()->assertJsonPath('data.is_fee_payment_due', 1);
        foreach (['dashboard', 'subjects', 'exam-marks?exam_id='.$this->schoolA->ids['exam'], 'offline-exam-report?session_year_id='.$this->schoolA->ids['session_year']] as $path) {
            $this->app['auth']->forgetGuards();
            $answer = $this->getJson('/api/student/'.$path)->assertOk();
            $this->assertFalse($answer->json('error'), $path.': '.$answer->getContent());
        }
        $this->withHeader('X-School-Id', '2')->getJson('/api/student/dashboard')->assertForbidden();
    }

    public function test_unpaid_child_keeps_academics_when_guardian_cannot_view_fees(): void
    {
        GuardianLink::query()->updateOrCreate([
            'learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids['farid_user'],
        ], ['relationship' => 'father', 'status' => 'active', 'can_see_fees' => false]);
        $child = $this->schoolA->ids['asha'];
        $this->withToken($this->schoolA->tokens['farid'])->getJson('/api/parent/subjects?child_id='.$child)
            ->assertOk()->assertJsonPath('error', false);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/parent/fees-paid-list?child_id='.$child)->assertForbidden();
        $this->getJson('/api/parent/subjects?child_id='.$this->schoolB->ids['asha'])->assertJsonPath('error', true);
    }
}

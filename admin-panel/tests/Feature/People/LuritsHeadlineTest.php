<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\LearnerIdentifiers;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * The LURITS number as the headline number (Action Plan 1.0b):
 * engineering/specs/platform/lurits.md, "Headline number"; ADR 0007.
 */
class LuritsHeadlineTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private const ASHA = '8800000001';

    private const BAOBAB_ASHA = '8800000009';

    private string $caraNumber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // Farid may approve for Asha; Mona is linked but may not approve.
        foreach (['farid' => true, 'mona' => false] as $guardian => $approves) {
            DB::table('users')->where('id', $this->schoolA->ids[$guardian.'_user'])->update(['email_verified_at' => now()]);
            GuardianLink::query()->create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids[$guardian.'_user'],
                'relationship' => 'guardian', 'status' => 'active', 'can_approve' => $approves]);
        }
        app(LuritsNumbers::class)->record($this->learner('asha'), self::ASHA, 'school_entry');
        // Cara has only the NavigateEd number.
        app(StudentNumbers::class)->assign($this->learner('cara'));
        $this->caraNumber = (string) DB::table('learner_identifiers')->where('user_id', $this->schoolA->ids['cara_user'])->value('student_number');
        app(CurrentSchool::class)->run(2, fn () => app(LuritsNumbers::class)->record(Students::query()->findOrFail($this->schoolB->ids['asha']), self::BAOBAB_ASHA, 'sasams_import'));
    }

    private function learner(string $name): Students
    {
        return Students::query()->findOrFail($this->schoolA->ids[$name]);
    }

    private function api(string $who): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->schoolA->tokens[$who]);
    }

    private function asAdmin(): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->superAdmin()->createToken('lurits-headline')->plainTextToken);
    }

    private function panel(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->actingAs($user, 'web');
    }

    private function profile(string $who, string $learner): TestResponse
    {
        $request = $who === 'superadmin' ? $this->asAdmin() : $this->api($who);

        return $request->getJson('/api/v2/people/'.$this->schoolA->ids[$learner.'_user'].'/profile?profile_type=learner');
    }

    public function test_the_panel_profile_shows_lurits_first_then_the_admission_number(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/people-profiles/'.$this->schoolA->ids['asha_user'].'?profile_type=learner')->assertOk()
            ->assertSee('data-identifier-type="lurits"', false)->assertSeeInOrder(['LURITS number', self::ASHA, 'Admission number', 'GR-ASHA'])
            ->assertDontSee('NavigateEd number (temporary)')->assertDontSee(self::BAOBAB_ASHA);
        $this->get('/student-report/'.$this->schoolA->ids['asha'])->assertOk()
            ->assertSeeInOrder(['LURITS number', self::ASHA, 'Admission number', 'GR-ASHA']);
    }

    public function test_the_panel_labels_the_navigateed_number_as_temporary_without_lurits(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/people-profiles/'.$this->schoolA->ids['cara_user'].'?profile_type=learner')->assertOk()
            ->assertSeeInOrder(['NavigateEd number (temporary)', $this->caraNumber, 'Admission number', 'GR-CARA'])->assertDontSee('data-identifier-type="lurits"', false);
        $this->get('/student-report/'.$this->schoolA->ids['cara'])->assertOk()
            ->assertSeeInOrder(['NavigateEd number (temporary)', $this->caraNumber, 'Admission number', 'GR-CARA']);
    }

    public function test_a_teacher_without_profile_permission_sees_only_the_admission_number_in_the_panel(): void
    {
        $this->actingAs(User::query()->findOrFail($this->schoolA->ids['tara_user']));

        $this->get('/people-profiles/'.$this->schoolA->ids['asha_user'].'?profile_type=learner')->assertOk()
            ->assertSee('Admission number')->assertSee('GR-ASHA')->assertDontSee(self::ASHA)->assertDontSee('data-identifier-type', false);
    }

    public function test_the_learner_and_an_approving_guardian_get_the_number_in_the_api(): void
    {
        $lurits = ['type' => 'lurits', 'value' => self::ASHA];

        $this->api('asha')->getJson('/api/v2/me/profile')->assertOk()->assertJsonPath('data.primary_identifier', $lurits);
        $this->profile('farid', 'asha')->assertOk()->assertJsonPath('data.primary_identifier', $lurits);
        $this->api('farid')->getJson('/api/v2/guardian/approvals')->assertOk()
            ->assertJsonPath('data.0.learner.id', $this->schoolA->ids['asha'])->assertJsonPath('data.0.learner.primary_identifier', $lurits);

        $children = collect($this->api('farid')->getJson('/api/v2/me/context')->assertOk()->json('data.children'))->keyBy('id');
        $this->assertSame($lurits, $children[$this->schoolA->ids['asha']]['primary_identifier']);
        // Cara: Farid is her father on the learner record only, without an approving link.
        $this->assertNull($children[$this->schoolA->ids['cara']]['primary_identifier']);
    }

    public function test_a_guardian_without_approval_rights_gets_null(): void
    {
        $profile = $this->profile('mona', 'asha')->assertOk()->assertJsonPath('data.primary_identifier', null);
        $this->assertStringNotContainsString(self::ASHA, $profile->getContent());

        $context = $this->api('mona')->getJson('/api/v2/me/context')->assertOk();
        $asha = collect($context->json('data.children'))->firstWhere('id', $this->schoolA->ids['asha']);
        $this->assertNull($asha['primary_identifier']);
        $this->assertStringNotContainsString(self::ASHA, $context->getContent());
        $this->assertSame([], $this->api('mona')->getJson('/api/v2/guardian/approvals')->assertOk()->json('data'));
    }

    public function test_staff_with_profile_permission_get_it_and_a_teacher_without_it_gets_null(): void
    {
        $this->profile('superadmin', 'asha')->assertOk()->assertJsonPath('data.primary_identifier', ['type' => 'lurits', 'value' => self::ASHA]);
        $this->profile('superadmin', 'cara')->assertOk()->assertJsonPath('data.primary_identifier', ['type' => 'navigateed', 'value' => $this->caraNumber]);

        // Tara teaches Asha, so she may open the profile, but has no profile permission.
        $teacher = $this->profile('tara', 'asha')->assertOk()->assertJsonPath('data.profile_type', 'learner')->assertJsonPath('data.primary_identifier', null);
        $this->assertStringNotContainsString(self::ASHA, $teacher->getContent());
        // A staff profile never has one.
        $this->api('tara')->getJson('/api/v2/me/profile')->assertOk()->assertJsonPath('data.primary_identifier', null);

        // A staff list follows the same rule.
        $learners = Students::query()->whereIn('id', [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']])->get();
        $lists = app(LearnerIdentifiers::class);
        $this->assertSame([$this->schoolA->ids['asha'] => ['type' => 'lurits', 'value' => self::ASHA], $this->schoolA->ids['cara'] => ['type' => 'navigateed', 'value' => $this->caraNumber]],
            $lists->forStaffList($this->superAdmin(), $learners));
        $this->assertSame([$this->schoolA->ids['asha'] => null, $this->schoolA->ids['cara'] => null],
            $lists->forStaffList(User::query()->findOrFail($this->schoolA->ids['tara_user']), $learners));
    }

    public function test_another_schools_learner_is_not_found_and_its_number_never_appears(): void
    {
        $baobab = $this->schoolB->ids['asha_user'];
        $this->asAdmin()->getJson('/api/v2/people/'.$baobab.'/profile?profile_type=learner')->assertNotFound();
        $this->api('farid')->getJson('/api/v2/people/'.$baobab.'/profile?profile_type=learner')->assertNotFound();
        $this->panel($this->superAdmin())->get('/people-profiles/'.$baobab.'?profile_type=learner')->assertNotFound();
        $this->get('/student-report/'.$this->schoolB->ids['asha'])->assertNotFound();

        // School B's learner, loaded elsewhere, is still refused from School A.
        $other = app(CurrentSchool::class)->run(2, fn () => Students::query()->findOrFail($this->schoolB->ids['asha']));
        $this->assertNull(app(LearnerIdentifiers::class)->forLearner($this->superAdmin(), $other));
        $this->assertSame([(int) $other->id => null], app(LearnerIdentifiers::class)->forStaffList($this->superAdmin(), [$other]));

        foreach ([
            $this->api('asha')->getJson('/api/v2/me/profile'),
            $this->api('farid')->getJson('/api/v2/me/context'),
            $this->api('farid')->getJson('/api/v2/guardian/approvals'),
            $this->profile('superadmin', 'asha'),
            $this->panel($this->superAdmin())->get('/people-profiles/'.$this->schoolA->ids['asha_user'].'?profile_type=learner'),
        ] as $response) {
            $response->assertOk();
            $this->assertStringNotContainsString(self::BAOBAB_ASHA, (string) $response->getContent());
        }
    }

    public function test_the_number_stays_out_of_the_audit_log_and_the_application_log(): void
    {
        $this->profile('superadmin', 'asha')->assertOk();
        $this->api('farid')->getJson('/api/v2/me/context')->assertOk();

        $this->assertStringNotContainsString(self::ASHA, json_encode(DB::table('audit_log')->get()->all()) ?: '');
        $log = storage_path('logs/laravel.log');
        $this->assertFalse(is_file($log) && str_contains((string) file_get_contents($log), self::ASHA));
    }

    public function test_the_leaving_certificate_names_the_learner_by_lurits_first_or_the_temporary_number(): void
    {
        $pages = [];
        Pdf::shouldReceive('loadView')->twice()->andReturnUsing(function (string $view, array $data) use (&$pages) {
            $pages[] = view($view, $data)->render();
            $pdf = Mockery::mock(DomPdf::class);
            $pdf->shouldReceive('stream')->andReturn(response('%PDF-synthetic'));

            return $pdf;
        });
        $this->actingAs($this->superAdmin());

        $this->post('/leaving-certificate', ['id' => $this->schoolA->ids['asha'], 'reason' => 'Moving'])->assertOk();
        $this->post('/leaving-certificate', ['id' => $this->schoolA->ids['cara'], 'reason' => 'Moving'])->assertOk();

        $this->assertMatchesRegularExpression('/2\. LURITS number:.*'.self::ASHA.'.*GR Number \(admission number\):.*GR-ASHA/s', $pages[0]);
        $this->assertMatchesRegularExpression('/2\. NavigateEd number \(temporary\):.*'.$this->caraNumber.'.*GR-CARA/s', $pages[1]);
        $this->assertStringNotContainsString(self::BAOBAB_ASHA, implode('', $pages));
    }

    public function test_a_teacher_cannot_print_a_leaving_certificate(): void
    {
        Pdf::shouldReceive('loadView')->never();
        $this->actingAs(User::query()->findOrFail($this->schoolA->ids['tara_user']));

        $this->post('/leaving-certificate', ['id' => $this->schoolA->ids['asha'], 'reason' => 'Moving'])->assertRedirect();
    }
}

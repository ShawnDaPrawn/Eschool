<?php

declare(strict_types=1);

namespace Tests\Feature\SchoolBusiness;

use App\Models\Money\FeeDiscount;
use App\Models\People\Enrolment;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SchoolBusiness\BursaryApplication;
use App\Models\SchoolBusiness\BursaryDocument;
use App\Models\SchoolBusiness\BursaryEvent;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeDiscounts;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\Bursaries;
use App\Services\SchoolBusiness\BursaryDocuments;
use App\Services\SchoolBusiness\BursaryRetention;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\SyntheticCleanScanner;
use Tests\TestCase;

class BursaryApplicationsTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    private User $committee;

    private int $year;

    private int $next;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->app->instance(UploadScanner::class, new SyntheticCleanScanner);
        Storage::fake(BursaryDocuments::DISK);
        $this->admin = $this->superAdmin();
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->next = (int) DB::table('session_years')->insertGetId(['school_id' => 1, 'name' => 'Next year', 'start_date' => '2027-01-15', 'end_date' => '2027-12-10', 'default' => 0, 'created_at' => now(), 'updated_at' => now()]);
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        Enrolment::where('session_year_id', $this->year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic bursary fixture']);
        }
        foreach (['farid', 'mona'] as $parent) {
            GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->schoolA->ids[$parent.'_user']], ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        }
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['gina_user']], ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        User::whereIn('id', [$this->schoolA->ids['farid_user'], $this->schoolA->ids['mona_user'], $this->schoolA->ids['gina_user']])->update(['email_verified_at' => now()]);
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'bursaries'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
        // The committee member: a teacher given only the bursary ability.
        $this->committee = $this->user('tara');
        $this->committee->givePermissionTo(Bursaries::PERMISSION);
    }

    private function user(string $name): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    private function household(array $changes = []): array
    {
        return array_replace(['household_size' => 4, 'dependants' => 2, 'monthly_income_cents' => 1850000, 'monthly_expenses_cents' => 1620000,
            'other_support' => null, 'explanation' => 'Synthetic household with a reduced income this year.'], $changes);
    }

    private function apply(string $who = 'farid', ?int $year = null): array
    {
        return app(Bursaries::class)->create($this->user($who), ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids[$who === 'gina' ? 'ben' : 'asha'],
            'session_year_id' => $year ?? $this->year, 'household' => $this->household()]);
    }

    private function upload(array $application): array
    {
        return app(BursaryDocuments::class)->upload($this->user('farid'), $application['id'], ['operation_id' => (string) Str::uuid(), 'category' => 'income_proof'],
            UploadedFile::fake()->createWithContent('payslip.pdf', '%PDF-1.4 synthetic payslip'));
    }

    private function submit(array $application, string $who = 'farid'): array
    {
        $row = BursaryApplication::where('public_id', $application['id'])->firstOrFail();

        return app(Bursaries::class)->submit($this->user($who), $application['id'], ['operation_id' => (string) Str::uuid(), 'revision' => $row->revision, 'confirmed' => true]);
    }

    private function award(array $application, ?User $actor = null, array $changes = []): array
    {
        $row = BursaryApplication::where('public_id', $application['id'])->firstOrFail();

        return app(Bursaries::class)->decide($actor ?? $this->committee, $application['id'], array_replace(['operation_id' => (string) Str::uuid(), 'revision' => $row->revision,
            'outcome' => 'award', 'reasons' => 'Meets the published criteria for a partial bursary.', 'award_kind' => 'fixed', 'award_value' => 150050, 'currency' => 'ZAR'], $changes));
    }

    /** @param int|list<int> $status */
    private function refuses(callable $call, int|array $status): void
    {
        try {
            $call();
            $this->fail('Expected HTTP '.json_encode($status));
        } catch (HttpExceptionInterface $error) {
            $this->assertContains($error->getStatusCode(), (array) $status, $error->getMessage());
        } catch (ModelNotFoundException) {
            // Rendered as 404 over HTTP.
            $this->assertContains(404, (array) $status);
        }
    }

    public function test_guardian_applies_with_documents_committee_awards_and_finance_approves_the_discount(): void
    {
        $application = $this->apply();
        $document = $this->upload($application);
        $this->assertSame('application/pdf', $document['mime']);
        $stored = BursaryDocument::firstOrFail();
        $this->assertStringNotContainsString('synthetic payslip', Storage::disk(BursaryDocuments::DISK)->get($stored->path), 'Stored encrypted');
        $this->assertStringNotContainsString('reduced income', (string) DB::table('bursary_applications')->value('household'), 'Household encrypted at rest');
        $this->submit($application);
        $result = $this->award($application);
        $this->assertSame('awarded', $result['state']);
        $rule = FeeDiscount::findOrFail($result['fee_discount_id']);
        $this->assertSame(['bursary', 'fixed', 150050, $this->schoolA->ids['asha'], 'draft', true, $this->committee->id],
            [$rule->kind, $rule->amount_kind, (int) $rule->amount_value, (int) $rule->learner_id, $rule->state, (bool) $rule->independent_approval, (int) $rule->prepared_by]);
        // The committee member cannot also approve; Finance (someone else) can.
        $this->committee->givePermissionTo(FeeDiscounts::PERMISSION);
        $this->refuses(fn () => app(FeeDiscounts::class)->transition($this->committee, $rule->id, ['operation_id' => (string) Str::uuid(), 'revision' => $rule->revision, 'action' => 'approve', 'confirmed' => true]), 403);
        $active = app(FeeDiscounts::class)->transition($this->admin, $rule->id, ['operation_id' => (string) Str::uuid(), 'revision' => $rule->revision, 'action' => 'approve', 'confirmed' => true]);
        $this->assertSame('active', $active->state);
        $review = app(Bursaries::class)->review($this->committee, $application['id']);
        $this->assertSame(['created', 'document_added', 'submitted', 'awarded', 'discount_drafted'], array_column($review['events'], 'action'));
        $this->assertSame('Meets the published criteria for a partial bursary.', $review['events'][3]['reasons']);
        $read = app(Bursaries::class)->read($this->user('farid'), $application['id']);
        $this->assertSame(['awarded', 150050], [$read['state'], $read['award_value']]);
        $this->assertArrayNotHasKey('events', $read, 'Committee reasons stay with the committee');
        $bytes = app(BursaryDocuments::class)->read($this->committee, $application['id'], $document['id'], true);
        $this->assertSame('%PDF-1.4 synthetic payslip', $bytes['bytes']);
        foreach (AuditLog::where('action', 'like', 'bursary.%')->get() as $audit) {
            $this->assertStringNotContainsString('reduced income', json_encode($audit->after));
            $this->assertStringNotContainsString('criteria', json_encode($audit->after));
        }
    }

    public function test_only_the_applying_guardian_and_the_committee_can_see_an_application(): void
    {
        $application = $this->apply();
        // Another family, and even the child's other guardian, get 404.
        $this->refuses(fn () => app(Bursaries::class)->read($this->user('gina'), $application['id']), 404);
        $this->refuses(fn () => app(Bursaries::class)->read($this->user('mona'), $application['id']), 404);
        $this->assertSame([], app(Bursaries::class)->mine($this->user('mona'))['items']);
        $this->assertCount(1, app(Bursaries::class)->mine($this->user('farid'))['items']);
        // Wrong role: a teacher without the ability, and a parent, cannot reach the committee view.
        $this->refuses(fn () => app(Bursaries::class)->review($this->user('theo'), $application['id']), 403);
        $this->refuses(fn () => app(Bursaries::class)->queue($this->user('farid'), []), 403);
        $this->refuses(fn () => $this->submit($application, 'mona'), 404);
        // Wrong school: School B's staff cannot find School A's application.
        app(CurrentSchool::class)->run(2, function () use ($application) {
            SchoolModule::updateOrCreate(['school_id' => 2, 'module' => 'bursaries'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
            // School B has no such application, and School A's staff hold nothing there.
            $this->refuses(fn () => app(Bursaries::class)->review($this->admin, $application['id']), [403, 404]);
            $this->refuses(fn () => app(Bursaries::class)->read(User::findOrFail($this->schoolB->ids['farid_user']), $application['id']), [403, 404]);
            $this->assertSame(1, BursaryApplication::withoutGlobalScopes()->count(), 'Only School A holds an application');
            $this->assertSame(0, BursaryApplication::count());
        });
    }

    public function test_a_guardian_on_the_committee_cannot_decide_and_decisions_are_idempotent(): void
    {
        $application = $this->apply();
        $this->submit($application);
        GuardianLink::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->committee->id, 'status' => 'active', 'relationship' => 'other', 'can_see_fees' => false, 'can_approve' => false]);
        $this->refuses(fn () => $this->award($application), 403);
        GuardianLink::where('guardian_user_id', $this->committee->id)->delete();
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => BursaryApplication::firstOrFail()->revision, 'outcome' => 'award',
            'reasons' => 'Meets the published criteria for a partial bursary.', 'award_kind' => 'fixed', 'award_value' => 150050, 'currency' => 'ZAR'];
        $first = app(Bursaries::class)->decide($this->committee, $application['id'], $input);
        $this->assertSame($first, app(Bursaries::class)->decide($this->committee, $application['id'], $input));
        $this->refuses(fn () => app(Bursaries::class)->decide($this->committee, $application['id'], ['award_value' => 1] + $input), 409);
        $this->assertSame(1, FeeDiscount::count());
        $this->assertSame(1, BursaryEvent::where('action', 'awarded')->count());
    }

    public function test_awards_use_integer_cents_or_basis_points(): void
    {
        $application = $this->apply();
        $this->submit($application);
        foreach ([['award_value' => '1500.50'], ['award_kind' => 'percent', 'award_value' => 10001], ['award_value' => 0]] as $bad) {
            try {
                $this->award($application, null, $bad);
                $this->fail('Refused');
            } catch (ValidationException|HttpExceptionInterface $error) {
                $this->assertContains($error instanceof ValidationException ? 422 : $error->getStatusCode(), [422]);
            }
        }
        $result = $this->award($application, null, ['award_kind' => 'percent', 'award_value' => 2500]);
        $this->assertSame(2500, (int) FeeDiscount::findOrFail($result['fee_discount_id'])->amount_value);
        $this->assertSame('percent', BursaryApplication::firstOrFail()->award_kind);
    }

    public function test_next_year_award_waits_for_its_year_and_withdrawn_or_draft_cannot_be_decided(): void
    {
        $application = $this->apply('farid', $this->next);
        $this->submit($application);
        $result = $this->award($application);
        $this->assertNull($result['fee_discount_id']);
        $this->refuses(fn () => app(Bursaries::class)->issueDiscount($this->committee, $application['id'], ['operation_id' => (string) Str::uuid()]), 409);
        $draft = $this->apply('gina');
        $this->refuses(fn () => $this->award($draft), 409);
        $row = BursaryApplication::where('public_id', $draft['id'])->firstOrFail();
        app(Bursaries::class)->withdraw($this->user('gina'), $draft['id'], ['operation_id' => (string) Str::uuid(), 'revision' => $row->revision, 'confirmed' => true]);
        $this->refuses(fn () => $this->award($draft), 409);
    }

    public function test_retention_erases_household_reasons_and_documents_but_keeps_the_award(): void
    {
        $application = $this->apply();
        $this->upload($application);
        $this->submit($application);
        $result = $this->award($application);
        $path = BursaryDocument::firstOrFail()->path;
        $draft = $this->apply('gina');
        $this->travel(91)->days();
        $this->assertSame(1, app(BursaryRetention::class)->run(), 'Only the stale draft is due');
        $this->assertNull(BursaryApplication::where('public_id', $draft['id'])->firstOrFail()->household);
        $this->travel(730)->days();
        $this->assertSame(1, app(BursaryRetention::class)->run());
        $row = BursaryApplication::where('public_id', $application['id'])->firstOrFail();
        $this->assertNull($row->household);
        $this->assertNotNull($row->erased_at);
        $this->assertSame(0, BursaryDocument::count());
        Storage::disk(BursaryDocuments::DISK)->assertMissing($path);
        $this->assertSame(0, BursaryEvent::whereNotNull('reasons')->count());
        $this->assertSame($result['fee_discount_id'], $row->fee_discount_id);
        $this->assertSame(0, app(BursaryRetention::class)->run());
        try {
            DB::table('bursary_applications')->where('id', $row->id)->update(['household' => 'restored']);
            $this->fail('Erased household details cannot come back');
        } catch (QueryException) {
            $this->assertNull(DB::table('bursary_applications')->where('id', $row->id)->value('household'));
        }
        $this->artisan('bursaries:prune')->assertSuccessful();
    }

    public function test_an_erased_draft_expires_cannot_be_changed_or_sent_and_frees_a_new_application(): void
    {
        $draft = $this->apply();
        $this->upload($draft);
        $this->travel(91)->days();
        $this->assertSame(1, app(BursaryRetention::class)->run());
        $row = BursaryApplication::where('public_id', $draft['id'])->firstOrFail();
        $this->assertSame(['expired', null, 0], [$row->state, $row->household, BursaryDocument::count()]);
        $this->refuses(fn () => $this->submit($draft), 409);
        $this->refuses(fn () => app(Bursaries::class)->update($this->user('farid'), $draft['id'], ['operation_id' => (string) Str::uuid(), 'revision' => $row->revision, 'household' => $this->household()]), 409);
        $this->refuses(fn () => app(Bursaries::class)->withdraw($this->user('farid'), $draft['id'], ['operation_id' => (string) Str::uuid(), 'revision' => $row->revision, 'confirmed' => true]), 409);
        $this->assertSame('expired', $row->fresh()->state);
        try {
            DB::table('bursary_applications')->where('id', $row->id)->update(['state' => 'draft']);
            $this->fail('An expired application cannot reopen');
        } catch (QueryException) {
            $this->assertSame('expired', $row->fresh()->state);
        }
        // The guardian can start again for the same child and year.
        $fresh = $this->apply();
        $this->assertSame('draft', $fresh['state']);
        $this->assertNotSame($draft['id'], $fresh['id']);
        $this->assertSame('expired', collect(app(Bursaries::class)->mine($this->user('farid'))['items'])->firstWhere('id', $draft['id'])['state']);
    }

    public function test_api_application_journey_matches_the_contract(): void
    {
        $farid = $this->user('farid');
        Sanctum::actingAs($farid);
        $created = $this->postJson('/api/v2/bursaries/applications', ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year,
            'household' => $this->household()], ['X-School-Id' => '1'])->assertOk()->json('data');
        $id = $created['id'];
        $this->putJson("/api/v2/bursaries/applications/$id", ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'household' => $this->household(['dependants' => 3])], ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.revision', 2);
        $document = $this->post("/api/v2/bursaries/applications/$id/documents", ['operation_id' => (string) Str::uuid(), 'category' => 'bank_statement',
            'document' => UploadedFile::fake()->createWithContent('statement.pdf', '%PDF-1.4 synthetic statement')], ['X-School-Id' => '1', 'Accept' => 'application/json'])->assertOk()->json('data');
        $this->get("/api/v2/bursaries/applications/$id/documents/".$document['id'], ['X-School-Id' => '1'])->assertOk();
        $this->postJson("/api/v2/bursaries/applications/$id/submit", ['operation_id' => (string) Str::uuid(), 'revision' => 3, 'confirmed' => true], ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.state', 'submitted');
        $this->getJson('/api/v2/bursaries/applications', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.items.0.state', 'submitted');
        $this->getJson("/api/v2/bursaries/applications/$id", ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.household.dependants', 3);
        $this->getJson('/api/v2/bursaries/committee/applications', ['X-School-Id' => '1'])->assertStatus(403);
        Sanctum::actingAs($this->committee);
        $this->getJson('/api/v2/bursaries/committee/applications', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson("/api/v2/bursaries/committee/applications/$id", ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.household.household_size', 4);
        $this->get("/api/v2/bursaries/committee/applications/$id/documents/".$document['id'], ['X-School-Id' => '1'])->assertOk();
        $this->postJson("/api/v2/bursaries/committee/applications/$id/notes", ['operation_id' => (string) Str::uuid(), 'reasons' => 'Checked the statement.'], ['X-School-Id' => '1'])->assertOk();
        $this->postJson("/api/v2/bursaries/committee/applications/$id/decision", ['operation_id' => (string) Str::uuid(), 'revision' => 4, 'outcome' => 'decline',
            'reasons' => 'The household is above the published threshold.'], ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.state', 'declined');
        $this->postJson("/api/v2/bursaries/committee/applications/$id/discount", ['operation_id' => (string) Str::uuid()], ['X-School-Id' => '1'])->assertStatus(409);
        Sanctum::actingAs($this->user('gina'));
        $this->getJson("/api/v2/bursaries/applications/$id", ['X-School-Id' => '1'])->assertNotFound();
        $this->postJson("/api/v2/bursaries/applications/$id/withdraw", ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'confirmed' => true], ['X-School-Id' => '1'])->assertNotFound();
    }
}
